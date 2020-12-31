<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) 2020 FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace FoF\OAuth;

use Illuminate\Support\ServiceProvider;

class OAuthServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->tag([
            Providers\Discord::class,
            Providers\Facebook::class,
            Providers\GitHub::class,
            Providers\GitLab::class,
            Providers\Twitter::class,
            Providers\Google::class,
            Providers\WeChat::class,
        ], 'fof-oauth.providers');

        $this->app->singleton('fof-oauth.providers.forum', $this->map(function (Provider $provider) {
            if (!$provider->enabled()) {
                return null;
            }

            return [
                'name' => $provider->name(),
                'icon' => $provider->icon(),
            ];
        }));

        $this->app->singleton('fof-oauth.providers.admin', $this->map(function (Provider $provider) {
            return [
                'name'   => $provider->name(),
                'icon'   => $provider->icon(),
                'link'   => $provider->link(),
                'fields' => $provider->fields(),
            ];
        }));
    }

    protected function map(callable $cb)
    {
        return function () use ($cb) {
            $providers = $this->app->tagged('fof-oauth.providers');

            return array_map(function ($provider) use ($cb) {
                return $cb($provider);
            }, iterator_to_array($providers));
        };
    }
}
