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
    protected $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    public function subscribe(Dispatcher $events)
    {
        $events->listen(Saving::class, [$this, 'settingsSaved']);
        $events->listen([Enabling::class, Disabling::class], [$this, 'clearOauthCache']);
    }

    public function clearOauthCache()
    {
        $this->cache->forget('fof-oauth.providers.forum');
        $this->cache->forget('fof-oauth.providers.admin');
    }

    public function settingsSaved(Saving $event)
    {
        foreach (array_keys($event->settings) as $key) {
            if (Str::startsWith($key, 'fof-oauth')) {
                $this->clearOauthCache();
                break; // Exit the loop once the cache is cleared
            }
        }
    }
}
