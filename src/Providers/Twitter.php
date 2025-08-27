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
use FoF\OAuth\Errors\AuthenticationException;
use FoF\OAuth\Provider;
use League\OAuth2\Client\Provider\AbstractProvider;

class Twitter extends Provider
{
    public function name(): string
    {
        return 'twitter';
    }

    public function link(): string
    {
        return 'https://developer.twitter.com/en/apps';
    }

    public function fields(): array
    {
        return [
            'api_key'    => 'required',
            'api_secret' => 'required',
        ];
    }

    public function excludeFromRoutePattern(): bool
    {
        return true;
    }

    public function provider(string $redirectUri): ?AbstractProvider
    {
        return null;
    }

    public function server(string $redirectUri): \League\OAuth1\Client\Server\Twitter
    {
        return new \League\OAuth1\Client\Server\Twitter([
            'identifier'   => $this->getSetting('api_key'),
            'secret'       => $this->getSetting('api_secret'),
            'callback_uri' => $redirectUri,
        ]);
    }

    /**
     * @throws AuthenticationException
     */
    public function suggestions(Registration $registration, $user, string $token)
    {
        $email = $user->email;

        if (empty($email)) {
            throw new AuthenticationException('invalid_email');
        }

        $registration
            ->provideTrustedEmail($email)
            ->suggestUsername($user->nickname ?: '')
            ->setPayload(get_object_vars($user));

        $this->provideAvatar($registration, str_replace('_normal', '', $user->imageUrl));
    }
}
