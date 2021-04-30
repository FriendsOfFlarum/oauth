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

    public function provider(string $redirectUri): AbstractProvider
    {
        return new FacebookProvider([
            'clientId'        => $this->getSetting('app_id'),
            'clientSecret'    => $this->getSetting('app_secret'),
            'redirectUri'     => $redirectUri,
            'graphApiVersion' => 'v3.0',
        ]);
    }

    public function suggestions(Registration $registration, $user, string $token)
    {
        $this->verifyEmail($email = $user->getEmail());

        $registration
            ->provideTrustedEmail($email)
            ->provideAvatar($user->getPictureUrl() ?: '')
            ->suggestUsername($user->getName() ?: '')
            ->setPayload($user->toArray());
    }
}
