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

use Exception;
use Flarum\Forum\Auth\Registration;
use Flarum\Settings\SettingsRepositoryInterface;
use FoF\OAuth\Provider;
use FoF\OAuth\Support\JwksKeyLoader;
use Illuminate\Support\Arr;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use League\OAuth2\Client\Provider\AbstractProvider;
use OpenIDConnectClient\OpenIDConnectProvider;
use Psr\Log\LoggerInterface;

/**
 * OpenID Connect provider for federated Flarum SSO.
 *
 * Uses steverhoades/oauth2-openid-connect-client to perform OIDC authorization
 * code flow with cryptographic ID token validation: the library verifies the
 * JWT signature against the issuer's JWKS plus the iss, aud, exp, iat, and
 * nbf claims before returning the access token.
 *
 * Nonce replay-defence is not currently enforced by the upstream library's
 * ValidatorChain — see https://github.com/steverhoades/oauth2-openid-connect-client
 * — so we rely on fof/oauth's existing `state` CSRF check for replay
 * protection. Signature + issuer + audience verification still prevents any
 * spoofed or MITM'd userinfo response from being trusted.
 */
class Flarum extends Provider
{
    public function __construct(
        SettingsRepositoryInterface $settings,
        protected JwksKeyLoader $jwks,
        protected LoggerInterface $logger,
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
        return 'https://docs.flarum.org/extensions/oauth-provider';
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

    public function provider(string $redirectUri): ?AbstractProvider
    {
        $base = rtrim($this->getSetting('forum_url'), '/');

        if ($base === '') {
            return null;
        }

        try {
            $publicKeys = $this->jwks->load("$base/.well-known/jwks.json");
        } catch (Exception $e) {
            $this->logger->error('fof/oauth: failed to load JWKS from Flarum provider', [
                'issuer' => $base,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        return new OpenIDConnectProvider(
            [
                'clientId'                => $this->getSetting('client_id'),
                'clientSecret'            => $this->getSetting('client_secret'),
                'redirectUri'             => $redirectUri,
                'idTokenIssuer'           => $base,
                'urlAuthorize'            => "$base/oauth/authorize",
                'urlAccessToken'          => "$base/oauth/token",
                'urlResourceOwnerDetails' => "$base/oauth/userinfo",
                'publicKey'               => $publicKeys,
                'scopes'                  => ['openid', 'profile', 'email'],
                'responseResourceOwnerId' => 'sub',
            ],
            [
                'signer' => new Sha256(),
            ],
        );
    }

    public function options(): array
    {
        return [
            'scope' => ['openid', 'profile', 'email'],
        ];
    }

    public function suggestions(Registration $registration, mixed $user, string $token): void
    {
        $data = $user->toArray();

        $this->verifyEmail($email = Arr::get($data, 'email'));

        $registration
            ->provideTrustedEmail($email)
            ->suggestUsername(Arr::get($data, 'name') ?: '')
            ->setPayload($data);

        $this->provideAvatar(
            $registration,
            $this->bestAvatarUrl(
                Arr::get($data, 'picture_srcset'),
                Arr::get($data, 'picture')
            )
        );
    }

    /**
     * Pick the highest-density variant from an srcset, falling back to the
     * single picture URL. The avatar pipeline downloads this and derives its
     * own scaled variants, so handing it the largest source available gives
     * the best result.
     */
    protected function bestAvatarUrl(?string $srcset, ?string $fallback): ?string
    {
        if (empty($srcset)) {
            return $fallback;
        }

        $bestUrl = null;
        $bestDensity = 0.0;

        foreach (explode(',', $srcset) as $entry) {
            $entry = trim($entry);
            if ($entry === '') {
                continue;
            }

            $parts = preg_split('/\s+/', $entry, 2);
            $url = $parts[0] ?? null;
            $descriptor = $parts[1] ?? '1x';

            if (! $url) {
                continue;
            }

            $density = (float) rtrim($descriptor, 'x');
            if ($density > $bestDensity) {
                $bestDensity = $density;
                $bestUrl = $url;
            }
        }

        return $bestUrl ?? $fallback;
    }
}
