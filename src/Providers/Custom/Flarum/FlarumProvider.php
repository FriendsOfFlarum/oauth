<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Providers\Custom\Flarum;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use Illuminate\Contracts\Cache\Repository as Cache;
use League\OAuth2\Client\Grant\AbstractGrant;
use League\OAuth2\Client\Grant\AuthorizationCode;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Psr\Log\LoggerInterface;
use stdClass;
use Throwable;
use UnexpectedValueException;
use WeakMap;

/**
 * OpenID Connect client for a forum running flarum/oauth-provider.
 *
 * The ID token returned by the token endpoint is checked against the forum's JWKS (RS256 signature, issuer,
 * audience, expiry), and the UserInfo response is only accepted when its `sub` matches the ID token's. No nonce
 * is sent: the authorization code flow is already bound to this browser session by `state` and PKCE.
 */
class FlarumProvider extends GenericProvider
{
    /**
     * Allowed clock difference between the two forums, in seconds.
     */
    public const LEEWAY = 60;

    public const JWKS_CACHE_TTL = 86400;

    /**
     * @var string The forum URL, which flarum/oauth-provider uses as the `iss` of its ID tokens.
     */
    protected $issuer;

    /**
     * @var string
     */
    protected $jwksUri;

    protected ?Cache $cache;

    protected ?LoggerInterface $logger;

    /**
     * Subjects of the verified ID tokens, keyed by the access token they came with.
     *
     * @var WeakMap<AccessToken, string>
     */
    protected WeakMap $verifiedSubjects;

    /**
     * Whether this instance has already fetched the key set, so a refresh would fetch the same keys again.
     */
    protected bool $jwksFetched = false;

    /**
     * @param array{cache?: Cache, logger?: LoggerInterface} $collaborators
     */
    public function __construct(array $options = [], array $collaborators = [])
    {
        $this->cache = $collaborators['cache'] ?? null;
        $this->logger = $collaborators['logger'] ?? null;
        $this->verifiedSubjects = new WeakMap();

        parent::__construct($options, $collaborators);
    }

    protected function getRequiredOptions(): array
    {
        return [...parent::getRequiredOptions(), 'issuer', 'jwksUri'];
    }

    protected function createAccessToken(array $response, AbstractGrant $grant): AccessTokenInterface
    {
        /** @var AccessToken $token */
        $token = parent::createAccessToken($response, $grant);

        $idToken = $response['id_token'] ?? null;

        if (is_string($idToken) && $idToken !== '') {
            $this->verifiedSubjects[$token] = $this->verifyIdToken($idToken);
        } elseif ($grant instanceof AuthorizationCode) {
            throw new IdentityProviderException('The token response has no ID token.', 0, $response);
        }

        return $token;
    }

    public function getResourceOwner(AccessToken $token): ResourceOwnerInterface
    {
        $subject = $this->verifiedSubjects[$token] ?? null;

        if ($subject === null) {
            throw new IdentityProviderException('The access token did not come with a verified ID token.', 0, []);
        }

        $owner = parent::getResourceOwner($token);

        if ((string) $owner->getId() !== $subject) {
            throw new IdentityProviderException('The UserInfo subject does not match the ID token.', 0, $owner->toArray());
        }

        return $owner;
    }

    /**
     * @return string The ID token's subject.
     */
    protected function verifyIdToken(string $idToken): string
    {
        $previousLeeway = JWT::$leeway;
        JWT::$leeway = max($previousLeeway, self::LEEWAY);

        try {
            // A key the cached set doesn't have means the forum rotated its key, so fetch the set again once.
            $claims = $this->decode($idToken, $this->signingKeys(false));

            if ($claims === null && !$this->jwksFetched) {
                $claims = $this->decode($idToken, $this->signingKeys(true));
            }
        } catch (IdentityProviderException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new IdentityProviderException('Invalid ID token: '.$e->getMessage(), 0, []);
        } finally {
            JWT::$leeway = $previousLeeway;
        }

        if ($claims === null) {
            throw new IdentityProviderException('Invalid ID token: no signing key of the issuer verifies its signature.', 0, []);
        }

        if (($claims->iss ?? null) !== $this->issuer) {
            // Usually the forum URL setting differs from the provider's `url` in scheme, host or path. Only the log
            // says so: the error is shown to whoever is logging in.
            $this->logger?->warning(sprintf(
                '[OAuth][flarum] The ID token was issued by %s, but the forum URL is set to %s. Enter the provider forum\'s URL exactly as set in its config.php.',
                json_encode($claims->iss ?? null, JSON_UNESCAPED_SLASHES),
                json_encode($this->issuer, JSON_UNESCAPED_SLASHES)
            ));

            throw new IdentityProviderException('Invalid ID token: unexpected issuer.', 0, []);
        }

        if (!in_array($this->clientId, (array) ($claims->aud ?? []), true)) {
            throw new IdentityProviderException('Invalid ID token: not issued to this client.', 0, []);
        }

        if (!isset($claims->exp)) {
            throw new IdentityProviderException('Invalid ID token: no expiry.', 0, []);
        }

        if (!is_string($claims->sub ?? null) || $claims->sub === '') {
            throw new IdentityProviderException('Invalid ID token: no subject.', 0, []);
        }

        return $claims->sub;
    }

    /**
     * @param array<string, Key> $keys
     *
     * @return stdClass|null The claims, or null when none of the keys verifies the signature.
     */
    protected function decode(string $idToken, array $keys): ?stdClass
    {
        // flarum/oauth-provider doesn't put a `kid` in the token header, so without one every key is tried.
        $kid = $this->header($idToken)['kid'] ?? null;
        $candidates = $kid === null ? $keys : array_intersect_key($keys, [(string) $kid => true]);

        foreach ($candidates as $key) {
            try {
                return JWT::decode($idToken, $key);
            } catch (SignatureInvalidException) {
                continue;
            }
        }

        return null;
    }

    protected function header(string $jwt): array
    {
        $header = json_decode(JWT::urlsafeB64Decode(explode('.', $jwt)[0]), true);

        if (!is_array($header)) {
            throw new UnexpectedValueException('Invalid header encoding');
        }

        return $header;
    }

    /**
     * @return array<string, Key>
     */
    protected function signingKeys(bool $refresh): array
    {
        $cacheKey = 'fof-oauth.flarum.jwks.'.hash('sha256', $this->jwksUri);

        $jwks = $refresh ? null : $this->cache?->get($cacheKey);

        if (!is_array($jwks)) {
            $jwks = $this->fetchJwks();
            $this->cache?->put($cacheKey, $jwks, self::JWKS_CACHE_TTL);
        }

        return JWK::parseKeySet($jwks, 'RS256');
    }

    /**
     * @return array{keys: list<array<string, mixed>>} The RS256 signing keys of the issuer's key set.
     */
    protected function fetchJwks(): array
    {
        try {
            $document = $this->getParsedResponse(
                $this->getRequest('GET', $this->jwksUri, ['headers' => ['Accept' => 'application/json']])
            );
        } catch (Throwable $e) {
            throw new IdentityProviderException("Could not fetch the issuer's JWKS: ".$e->getMessage(), 0, []);
        }

        $keys = array_values(array_filter(
            is_array($document) && is_array($document['keys'] ?? null) ? $document['keys'] : [],
            fn ($key) => is_array($key)
                && ($key['kty'] ?? null) === 'RSA'
                && ($key['use'] ?? 'sig') === 'sig'
                && ($key['alg'] ?? 'RS256') === 'RS256'
        ));

        if ($keys === []) {
            throw new IdentityProviderException("The issuer's JWKS has no RS256 signing key.", 0, []);
        }

        $this->jwksFetched = true;

        return ['keys' => $keys];
    }
}
