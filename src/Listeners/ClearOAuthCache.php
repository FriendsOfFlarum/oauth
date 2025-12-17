<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Listeners;

use Flarum\Extension\Event\Disabling;
use Flarum\Extension\Event\Enabling;
use Flarum\Settings\Event\Saving;
use Illuminate\Contracts\Cache\Store as Cache;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Str;

class ClearOAuthCache
{
    public function __construct(
        protected Cache $cache
    ) {
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(Saving::class, [$this, 'settingsSaved']);
        $events->listen([Enabling::class, Disabling::class], [$this, 'clearOauthCache']);
    }

    public function clearOauthCache(): void
    {
        $this->cache->forget('fof-oauth.providers.forum');
        $this->cache->forget('fof-oauth.providers.admin');
    }

    public function settingsSaved(Saving $event): void
    {
        foreach (array_keys($event->settings) as $key) {
            if (Str::startsWith($key, 'fof-oauth')) {
                $this->clearOauthCache();
                break; // Exit the loop once the cache is cleared
            }
        }
    }
}
