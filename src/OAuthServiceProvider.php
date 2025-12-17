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

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\Config;
use Flarum\Http\RouteCollection;
use Flarum\Http\RouteHandlerFactory;
use Illuminate\Contracts\Cache\Store as Cache;
use Illuminate\Contracts\Container\Container;

class OAuthServiceProvider extends AbstractServiceProvider
{
    public function register()
    {
        $this->container->tag([
            Providers\Discord::class,
            Providers\Facebook::class,
            Providers\GitHub::class,
            Providers\GitLab::class,
            Providers\Twitter::class,
            Providers\Google::class,
            Providers\LinkedIn::class,
        ], 'fof-oauth.providers');

        // Add OAuth provider routes
        $this->container->resolving('flarum.forum.routes', function (RouteCollection $collection, Container $container) {
            /** @var RouteHandlerFactory $factory */
            $factory = $container->make(RouteHandlerFactory::class);

            $collection->addRoute('GET', new OAuth2RoutePattern(), 'fof-oauth', $factory->toController(Controllers\AuthController::class));
        });
    }

    public function boot(): void
    {
        // Register provider singletons after all extensions have registered their providers
        $this->container->singleton('fof-oauth.providers.forum', function (Container $container) {
            /** @var Cache $cache */
            $cache = $container->make(Cache::class);
            /** @var Config $config */
            $config = $container->make(Config::class);

            // If we're in debug mode, don't cache the providers, but directly return them.
            if ($config->inDebugMode()) {
                return $this->mapProviders();
            }

            $cacheKey = 'fof-oauth.providers.forum';

            $data = $cache->get($cacheKey);
            if ($data === null) {
                $data = $this->mapProviders();
                $cache->forever($cacheKey, $data);
            }

            return $data;
        });

        $this->container->singleton('fof-oauth.providers.admin', function (Container $container) {
            /** @var Cache $cache */
            $cache = $container->make(Cache::class);
            /** @var Config $config */
            $config = $container->make(Config::class);

            // If we're in debug mode, don't cache the providers, but directly return them.
            if ($config->inDebugMode()) {
                return $this->mapProviders(true);
            }

            $cacheKey = 'fof-oauth.providers.admin';

            $data = $cache->get($cacheKey);
            if ($data === null) {
                $data = $this->mapProviders(true);
                $cache->forever($cacheKey, $data);
            }

            return $data;
        });
    }

    protected function mapProviders(bool $admin = false): array
    {
        $providers = $this->container->tagged('fof-oauth.providers');

        if ($admin) {
            return array_map(static function (Provider $provider) {
                return [
                    'name'   => $provider->name(),
                    'icon'   => $provider->icon(),
                    'link'   => $provider->link(),
                    'fields' => $provider->fields(),
                ];
            }, iterator_to_array($providers));
        }

        return array_map(static function (Provider $provider) {
            if (!$provider->enabled()) {
                return null;
            }

            return [
                'name'     => $provider->name(),
                'icon'     => $provider->icon(),
                'priority' => $provider->priority(),
            ];
        }, iterator_to_array($providers));
    }
}
