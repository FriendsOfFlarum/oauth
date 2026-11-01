<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Providers;

use Flarum\Forum\Auth\Registration;
use Flarum\Foundation\Config;
use Flarum\Settings\SettingsRepositoryInterface;
use FoF\OAuth\Provider;
use FoF\OAuth\Providers\Custom\Flarum\FlarumProvider;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\GenericResourceOwner;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Log in with an account on another Flarum forum, which runs flarum/oauth-provider.
 */
class Flarum extends Provider
{
    public function __construct(
        SettingsRepositoryInterface $settings,
        protected Container $container,
    ) {
        parent::__construct($settings);
    }

    public function name(): string
    {
        return 'flarum';
    }

    public function icon(): string
    {
        return 'flarum-logo';
    }

    public function link(): string
    {
        return 'https://github.com/flarum/oauth-provider';
    }

    public function fields(): array
    {
        return [
            'forum_url'     => 'required',
            'client_id'     => 'required',
            'client_secret' => 'required',
        ];
    }

    public function pkceEnabled(): bool
    {
        return true;
    }

    public function provider(string $redirectUri): AbstractProvider
    {
        $base = $this->forumUrl();

        return new FlarumProvider([
            'clientId'                => $this->getSetting('client_id'),
            'clientSecret'            => $this->getSetting('client_secret'),
            'redirectUri'             => $redirectUri,
            'issuer'                  => $base,
            'jwksUri'                 => "$base/.well-known/jwks.json",
            'urlAuthorize'            => "$base/oauth/authorize",
            'urlAccessToken'          => "$base/oauth/token",
            'urlResourceOwnerDetails' => "$base/oauth/userinfo",
            'scopeSeparator'          => ' ',
            'responseResourceOwnerId' => 'sub',
            'timeout'                 => 10,
        ], [
            // Resolved here rather than in the constructor: resolving the cache while providers are being
            // registered pulls `cache.store` in before fof/redis has set up its connections.
            'cache'  => $this->container->make(Cache::class),
            'logger' => $this->container->make(LoggerInterface::class),
        ]);
    }

    /**
     * The provider forum's URL, which must use https: the client secret, the authorization code and the user's
     * details are sent to it. Plain http is only allowed for loopback hosts, and in debug mode for local testing.
     */
    protected function forumUrl(): string
    {
        $url = rtrim(trim($this->getSetting('forum_url')), '/');
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host = strtolower(trim((string) parse_url($url, PHP_URL_HOST), '[]'));

        if ($host === '') {
            throw new RuntimeException('The Flarum provider has no valid forum URL configured.');
        }

        $loopback = $host === 'localhost'
            || $host === '::1'
            || (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false && str_starts_with($host, '127.'));

        if ($scheme !== 'https' && !($scheme === 'http' && ($loopback || $this->container->make(Config::class)->inDebugMode()))) {
            throw new RuntimeException("The Flarum provider's forum URL must use https: $url");
        }

        return $url;
    }

    public function options(): array
    {
        return ['scope' => ['openid', 'profile', 'email']];
    }

    /**
     * @param GenericResourceOwner $user
     */
    public function suggestions(Registration $registration, mixed $user, string $token): void
    {
        $data = $user->toArray();

        $this->verifyEmail($email = Arr::get($data, 'email'));

        if ($this->getProviderVerifiedEmail($user, $token) !== null) {
            $registration->provideTrustedEmail($email);
        } else {
            $registration->suggestEmail($email);
        }

        $registration
            ->suggestUsername(Arr::get($data, 'name') ?: '')
            ->setPayload($data);

        $this->provideAvatar($registration, $this->bestAvatarUrl(Arr::get($data, 'picture_srcset'), Arr::get($data, 'picture')));
    }

    /**
     * The provider forum reports `email_verified` from its own email confirmation.
     *
     * @param GenericResourceOwner $user
     */
    public function getProviderVerifiedEmail(mixed $user, string $token): ?string
    {
        $data = $user->toArray();

        if (($data['email_verified'] ?? null) === true && is_string($data['email'] ?? null) && $data['email'] !== '') {
            return $data['email'];
        }

        return null;
    }

    /**
     * The URL of the highest density avatar in the provider's `picture_srcset` (`url 1x, url 2x, ...`), or the
     * `picture` URL. Flarum makes its own sizes from the downloaded avatar, so the largest source is best.
     */
    protected function bestAvatarUrl(?string $srcset, ?string $fallback): ?string
    {
        $bestUrl = null;
        $bestDensity = 0.0;

        foreach (explode(',', (string) $srcset) as $entry) {
            $parts = preg_split('/\s+/', trim($entry), 2);
            $url = $parts[0] ?? '';
            $density = (float) rtrim($parts[1] ?? '1x', 'x');

            if ($url !== '' && $density > $bestDensity) {
                $bestDensity = $density;
                $bestUrl = $url;
            }
        }

        return $bestUrl ?? $fallback;
    }
}
