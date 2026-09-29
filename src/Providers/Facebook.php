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
use FoF\OAuth\Provider;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Facebook as FacebookProvider;
use League\OAuth2\Client\Provider\FacebookUser;

class Facebook extends Provider
{
    public function name(): string
    {
        return 'facebook';
    }

    public function link(): string
    {
        return 'https://developers.facebook.com/apps/';
    }

    public function fields(): array
    {
        return [
            'app_id'     => 'required',
            'app_secret' => 'required',
        ];
    }

    public function pkceEnabled(): bool
    {
        return false;
    }

    public function provider(string $redirectUri): AbstractProvider
    {
        return new FacebookProvider([
            'clientId'        => $this->getSetting('app_id'),
            'clientSecret'    => $this->getSetting('app_secret'),
            'redirectUri'     => $redirectUri,
            'graphApiVersion' => 'v3.0',
        ]);
    }

    /**
     * @param FacebookUser $user
     */
    public function suggestions(Registration $registration, mixed $user, string $token): void
    {
        $this->verifyEmail($email = $user->getEmail());

        if ($this->getProviderVerifiedEmail($user, $token) !== null) {
            $registration->provideTrustedEmail($email);
        } else {
            $registration->suggestEmail($email);
        }

        $registration
            ->suggestUsername($user->getName() ?: '')
            ->setPayload($user->toArray());

        $this->provideAvatar($registration, $user->getPictureUrl());
    }

    /**
     * Facebook exposes no email verification flag, so any email it returns is trusted without a verifiable signal.
     *
     * Meta's User reference only says `email` is "The User's primary email address listed on their profile" and
     * "will not be returned if no valid email address is available". It does not document verification. That
     * unconfirmed addresses are not returned is Meta's long-standing behaviour as reported by integrators, not a
     * documented guarantee. Revisit this if Meta documents a verification signal or the behaviour changes.
     *
     * @see https://developers.facebook.com/docs/graph-api/reference/user/
     *
     * @param FacebookUser $user
     */
    public function getProviderVerifiedEmail(mixed $user, string $token): ?string
    {
        return $user->getEmail() ?: null;
    }
}
