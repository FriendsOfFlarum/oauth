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
use Wohali\OAuth2\Client\Provider\Discord as DiscordProvider;

class Discord extends Provider
{
    public function name(): string
    {
        return 'discord';
    }

    public function link(): string
    {
        return 'https://discordapp.com/developers/applications';
    }

    public function fields(): array
    {
        return [
            'client_id'     => 'required',
            'client_secret' => 'required',
        ];
    }

    public function provider(string $redirectUri): AbstractProvider
    {
        return new DiscordProvider([
            'clientId'     => $this->getSetting('client_id'),
            'clientSecret' => $this->getSetting('client_secret'),
            'redirectUri'  => $redirectUri,
        ]);
    }

    public function options(): array
    {
        return ['scope' => ['identify', 'email']];
    }

    public function suggestions(Registration $registration, $user, string $token)
    {
        $this->verifyEmail($email = $user->getEmail());

        $hash = $user->getAvatarHash();
        $file = $hash ?
            "https://cdn.discordapp.com/avatars/{$user->getId()}/{$user->getAvatarHash()}.png"
            : 'https://cdn.discordapp.com/embed/avatars/0.png';

        $registration
            ->provideTrustedEmail($email)
            ->provideAvatar($file)
            ->suggestUsername($user->getUsername() ?: '')
            ->setPayload($user->toArray());
    }
}
