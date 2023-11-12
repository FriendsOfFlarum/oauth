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
}
