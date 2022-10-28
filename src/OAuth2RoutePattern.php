<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth;

/**
 * We use an object that can be cast to string, because it allows us to use the Flarum Route extender
 * Without having to build the URL during the booting container event
 * By using __toString, the code will only be evaluated when Flarum resolves flarum.forum.routes.
 */
class OAuth2RoutePattern
{
    public function __toString(): string
    {
        /**
         * @var Provider[] $providers
         */
        $providers = resolve('container')->tagged('fof-oauth.providers');

        $providerNames = [];

        foreach ($providers as $provider) {
            // Skip disabled providers, this increases compatibility with other oauth extensions which might offer the same providers
            // Skip providers that want to provider their own route (ie in extend.php)
            if (!$provider->enabled() || $provider->excludeFromRoutePattern()) {
                continue;
            }

            $providerNames[] = $provider->name();
        }

        return '/auth/{provider:'.implode('|', $providerNames).'}';
    }
}
