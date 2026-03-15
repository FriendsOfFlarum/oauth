<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Controllers;

use Flarum\Forum\Auth\Registration;
use Flarum\Forum\Auth\ResponseFactory;
use Flarum\Http\RequestUtil;
use Flarum\Http\UrlGenerator;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\LoginProvider;
use Flarum\User\User;
use FoF\OAuth\Errors\AuthenticationException;
use FoF\OAuth\Events\LinkingToProvider;
use FoF\OAuth\Events\OAuthLoginSuccessful;
use Illuminate\Contracts\Cache\Store as CacheStore;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Session\Store;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\RedirectResponse;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

abstract class AbstractOAuthController implements RequestHandlerInterface
{
    /**
     * Cache key for the OAuth2 state parameter.
     */
    public const SESSION_OAUTH2STATE = 'oauth2state';

    /**
     * Cache key for the PKCE code verifier.
     */
    public const SESSION_OAUTH2_PKCE_CODE = 'oauth2_pkce_code';

    /**
     * Cache key for the user ID being linked (account-linking flow).
     */
    public const SESSION_LINKTO = 'linkTo';

    /**
     * Cache key for the validated returnTo URL.
     */
    public const SESSION_RETURN_TO = 'oauth_returnTo';

    /**
     * Cache key for the provider name (used by logout handler, email sync etc.).
     */
    public const SESSION_OAUTH2PROVIDER = 'oauth2provider';

    /**
     * Cache key for the fast-track OAuth data (token + resource owner).
     * Used to resume the flow after an interrupting step such as 2FA.
     */
    public const SESSION_OAUTH_DATA = 'oauth_data';

    /**
     * How long to hold OAuth session data in the cache (seconds).
     */
    public static int $OAUTH_DATA_CACHE_LIFETIME = 60 * 5; // 5 minutes

    public function __construct(
        protected ResponseFactory $response,
        protected SettingsRepositoryInterface $settings,
        protected UrlGenerator $url,
        protected Dispatcher $events,
        protected CacheStore $cache
    ) {
    }

    /**
     * @throws \Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $provider = $this->buildProvider();

        /** @var Store $session */
        $session = $request->getAttribute('session');

        $this->putForever(self::SESSION_OAUTH2PROVIDER, $this->getProviderName(), $session);

        // Fast-track: resume a previously saved OAuth response (e.g. after 2FA).
        if ($this->get(self::SESSION_OAUTH_DATA, $session) !== null && $this->isFastTrack($session)) {
            $result = $this->fastTrack($session, $request);
            if ($result !== null) {
                return $result;
            }
        }

        if (!$this->hasAuthorizationCode($request)) {
            return $this->initiateAuthorization($provider, $session, $request);
        }

        $this->validateState($session, $request);

        // Restore the PKCE code verifier before exchanging the code.
        $pkceCode = $this->get(self::SESSION_OAUTH2_PKCE_CODE, $session);
        if (!empty($pkceCode)) {
            $provider->setPkceCode($pkceCode);
            $this->forget(self::SESSION_OAUTH2_PKCE_CODE, $session);
        }

        /** @var AccessToken $token */
        $token = $provider->getAccessToken('authorization_code', [
            'code' => Arr::get($request->getQueryParams(), 'code'),
        ]);

        $resourceOwner = $provider->getResourceOwner($token);

        return $this->handleOAuthResponse($request, $token, $resourceOwner, $session);
    }

    /**
     * Initiate the OAuth authorization redirect.
     * Stores state, PKCE verifier, linkTo, and returnTo in the session cache.
     */
    protected function initiateAuthorization(
        AbstractProvider $provider,
        Store $session,
        ServerRequestInterface $request
    ): RedirectResponse {
        $queryParams = $request->getQueryParams();

        // Store linkTo (account-linking flow) if present.
        if ($linkTo = Arr::get($queryParams, 'linkTo')) {
            $this->put(self::SESSION_LINKTO, (int) $linkTo, $session);
        }

        // Validate and store returnTo.
        $returnTo = $this->validateReturnTo(
            Arr::get($queryParams, 'returnTo', ''),
            $request
        );
        $this->put(self::SESSION_RETURN_TO, $returnTo, $session);

        $options = $this->getAuthorizationUrlOptions();

        // If PKCE is enabled for this provider, generate a verifier and inject the challenge manually.
        // (League's AbstractProvider only auto-generates PKCE when getPkceMethod() is overridden,
        // which none of the concrete provider packages do — so we drive it ourselves.)
        if ($this->isPkceEnabled()) {
            $verifier = rtrim(strtr(base64_encode(random_bytes(64)), '+/', '-_'), '=');
            $provider->setPkceCode($verifier);
            $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
            $options['code_challenge'] = $challenge;
            $options['code_challenge_method'] = 'S256';
        }

        $authUrl = $provider->getAuthorizationUrl($options);

        // Always store state.
        $this->put(self::SESSION_OAUTH2STATE, $provider->getState(), $session);

        // Store the PKCE code verifier if we generated one.
        $pkceCode = $provider->getPkceCode();
        if (!empty($pkceCode)) {
            $this->put(self::SESSION_OAUTH2_PKCE_CODE, $pkceCode, $session);
        }

        $session->save();

        return new RedirectResponse($authUrl);
    }

    /**
     * Fast-track: if we already have a valid token + resource owner in the session
     * (e.g. after a 2FA step), skip re-doing the OAuth exchange.
     */
    protected function isFastTrack(Store $session): bool
    {
        $data = $this->get(self::SESSION_OAUTH_DATA, $session);

        return $data !== null
            && Arr::get($data, 'token') instanceof AccessTokenInterface
            && Arr::get($data, 'resourceOwner') instanceof ResourceOwnerInterface;
    }

    protected function fastTrack(Store $session, ServerRequestInterface $request): ?ResponseInterface
    {
        $data = $this->get(self::SESSION_OAUTH_DATA, $session);
        $token = Arr::get($data, 'token');
        $resourceOwner = Arr::get($data, 'resourceOwner');

        if ($token instanceof AccessTokenInterface && $resourceOwner instanceof ResourceOwnerInterface) {
            return $this->handleOAuthResponse($request, $token, $resourceOwner, $session);
        }

        return null;
    }

    protected function hasAuthorizationCode(ServerRequestInterface $request): bool
    {
        return Arr::has($request->getQueryParams(), 'code');
    }

    /**
     * Validate the OAuth2 state parameter to prevent CSRF.
     */
    protected function validateState(Store $session, ServerRequestInterface $request): void
    {
        $returnedState = Arr::get($request->getQueryParams(), 'state');
        $savedState = $this->get(self::SESSION_OAUTH2STATE, $session);

        $this->forget(self::SESSION_OAUTH2STATE, $session);

        if (!$returnedState || !$savedState || !hash_equals($savedState, $returnedState)) {
            throw new \FoF\OAuth\Errors\AuthenticationException('invalid_state');
        }
    }

    /**
     * Handle the post-exchange OAuth response: link account or begin registration.
     */
    protected function handleOAuthResponse(
        ServerRequestInterface $request,
        AccessTokenInterface $token,
        ResourceOwnerInterface $resourceOwner,
        Store $session
    ): ResponseInterface {
        $actor = RequestUtil::getActor($request);
        $returnTo = $this->get(self::SESSION_RETURN_TO, $session) ?: '/';

        $this->forget(self::SESSION_RETURN_TO, $session);

        // Account-linking flow: an authenticated user is connecting an OAuth provider.
        if ($this->has(self::SESSION_LINKTO, $session) && $actor->exists) {
            $actor->assertRegistered();

            $linkToId = (int) $this->get(self::SESSION_LINKTO, $session);
            $this->forget(self::SESSION_LINKTO, $session);

            if ($actor->id !== $linkToId || $linkToId === 0) {
                throw new AuthenticationException('invalid_state');
            }

            $response = $this->linkAccount($actor, $resourceOwner, $returnTo);
        } else {
            // Normal login / registration flow.
            $response = $this->response->make(
                $this->getProviderName(),
                $this->getIdentifier($resourceOwner),
                function (Registration $registration) use ($resourceOwner, $token) {
                    $this->setSuggestions($registration, $resourceOwner, (string) $token);
                },
                $returnTo
            );
        }

        // Regenerate session ID after authentication to prevent session fixation.
        $session->regenerate(true);

        $this->dispatchSuccessEvent($token, $resourceOwner, $actor);
        $this->forget(self::SESSION_OAUTH2STATE, $session);

        return $response;
    }

    /**
     * Link the OAuth account to the authenticated user and redirect back.
     */
    protected function linkAccount(User $user, ResourceOwnerInterface $resourceOwner, string $returnTo): RedirectResponse
    {
        /** @var LoginProvider|null $existing */
        $existing = LoginProvider::where('identifier', $this->getIdentifier($resourceOwner))
            ->where('provider', $this->getProviderName())
            ->first();

        if ($existing && $existing->user_id !== $user->id) {
            throw new AuthenticationException('already_linked');
        }

        $this->events->dispatch(new LinkingToProvider(
            $this->getProviderName(),
            $this->getIdentifier($resourceOwner),
            $user
        ));

        $user->loginProviders()->firstOrCreate([
            'provider'   => $this->getProviderName(),
            'identifier' => $this->getIdentifier($resourceOwner),
        ])->touch();

        return new RedirectResponse($returnTo ?: '/');
    }

    /**
     * Dispatch OAuthLoginSuccessful after every successful OAuth callback.
     */
    protected function dispatchSuccessEvent(
        AccessTokenInterface $token,
        ResourceOwnerInterface $resourceOwner,
        ?User $actor
    ): void {
        $this->events->dispatch(new OAuthLoginSuccessful(
            $token,
            $resourceOwner,
            $this->getProviderName(),
            $this->getIdentifier($resourceOwner),
            $actor
        ));
    }

    /**
     * Validate a returnTo URL.
     *
     * Only relative paths (starting with /) are accepted. External URLs are
     * silently replaced with '/' to prevent open redirect attacks.
     */
    protected function validateReturnTo(string $returnTo, ServerRequestInterface $request): string
    {
        if (empty($returnTo)) {
            // Fall back to the Referer header, which is same-origin in normal usage.
            $referer = $request->getHeaderLine('Referer');
            if (!empty($referer)) {
                $returnTo = parse_url($referer, PHP_URL_PATH) ?? '/';
            }
        }

        // Reject anything that looks like an absolute URL (has a scheme or //host).
        if (!empty($returnTo) && (str_contains($returnTo, '://') || str_starts_with($returnTo, '//'))) {
            return '/';
        }

        // Must start with / to be a valid relative path.
        if (!empty($returnTo) && !str_starts_with($returnTo, '/')) {
            return '/';
        }

        return $returnTo ?: '/';
    }

    // -------------------------------------------------------------------------
    // Cache helpers (keyed by session ID)
    // -------------------------------------------------------------------------

    protected function put(string $key, mixed $value, Store $session): bool
    {
        return $this->cache->put($this->cacheKey($key, $session), $value, self::$OAUTH_DATA_CACHE_LIFETIME);
    }

    protected function putForever(string $key, mixed $value, Store $session): bool
    {
        return $this->cache->forever($this->cacheKey($key, $session), $value);
    }

    protected function get(string $key, Store $session): mixed
    {
        return $this->cache->get($this->cacheKey($key, $session));
    }

    protected function forget(string $key, Store $session): bool
    {
        return $this->cache->forget($this->cacheKey($key, $session));
    }

    protected function has(string $key, Store $session): bool
    {
        return $this->cache->get($this->cacheKey($key, $session)) !== null;
    }

    protected function cacheKey(string $key, Store $session): string
    {
        return "{$key}_{$session->getId()}";
    }

    // -------------------------------------------------------------------------
    // Abstract interface — implemented by concrete controllers / AuthController
    // -------------------------------------------------------------------------

    /**
     * Return the route name used to build the OAuth callback redirect URI.
     * Example: 'fof-oauth'.
     */
    abstract protected function getRouteName(): string;

    /**
     * Build and return the configured League OAuth2 provider instance.
     */
    abstract protected function getProvider(string $redirectUri): AbstractProvider;

    /**
     * Return the provider name string as stored in `login_providers.provider`.
     * Example: 'github', 'google'.
     */
    abstract protected function getProviderName(): string;

    /**
     * Return the options array passed to `getAuthorizationUrl()`.
     * Use this to set scopes, access type, etc.
     */
    abstract protected function getAuthorizationUrlOptions(): array;

    /**
     * Whether PKCE (Proof Key for Code Exchange) should be used for this provider.
     */
    abstract protected function isPkceEnabled(): bool;

    /**
     * Extract the unique identifier for this user from the resource owner.
     */
    abstract protected function getIdentifier(ResourceOwnerInterface $user): string;

    /**
     * Populate the Registration value object with data from the provider.
     * Called only for new users during registration.
     */
    abstract protected function setSuggestions(Registration $registration, ResourceOwnerInterface $user, string $token): void;

    /**
     * Build the provider using the correct redirect URI.
     */
    private function buildProvider(): AbstractProvider
    {
        $redirectUri = $this->url->to('forum')->route(
            $this->getRouteName(),
            ['provider' => $this->getProviderName()]
        );

        return $this->getProvider($redirectUri);
    }
}
