<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Tests\fixtures\ThirdParty;

use Flarum\Forum\Auth\Registration;
use FoF\OAuth\Provider;
use League\OAuth2\Client\Provider\AbstractProvider;

/**
 * Stands in for a provider registered by another extension via `FoF\OAuth\Extend\RegisterProvider`.
 * It only implements the documented extension points and trusts whatever email it is given.
 */
class ThirdPartyProvider extends Provider
{
    /**
     * Resource owner data the fake client returns on the next login.
     */
    public static array $resourceOwner = [];

    public function name(): string
    {
        return 'thirdparty';
    }

    public function link(): string
    {
        return 'https://thirdparty.example';
    }

    public function fields(): array
    {
        return [];
    }

    public function provider(string $redirectUri): ?AbstractProvider
    {
        return new ThirdPartyClient(static::$resourceOwner, ['redirectUri' => $redirectUri]);
    }

    public function pkceEnabled(): bool
    {
        return false;
    }

    public function suggestions(Registration $registration, mixed $user, string $token): void
    {
        $this->verifyEmail($email = $user->getEmail());

        $registration
            ->provideTrustedEmail($email)
            ->suggestUsername('thirdparty')
            ->setPayload($user->toArray());
    }
}
