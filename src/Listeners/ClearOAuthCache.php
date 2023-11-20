<?php

namespace FoF\OAuth\Listeners;

use Flarum\Settings\Event\Saving;
use Illuminate\Contracts\Cache\Store as Cache;
use Illuminate\Support\Str;

class ClearOAuthCache
{
    protected $cache;
    
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }
    
    public function handle(Saving $event)
    {
        foreach (array_keys($event->settings) as $key) {
            if (Str::startsWith($key, 'fof-oauth')) {
                $this->cache->forget('fof-oauth.providers.forum');
                $this->cache->forget('fof-oauth.providers.admin');
                break; // Exit the loop once the cache is cleared
            }
        }
    }
}
