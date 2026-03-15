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
    public function register(): void
    {
        $this->container->tag([
            Providers\Discord::class,
            Providers\Facebook::class,
            Providers\GitHub::class,
            Providers\GitLab::class,
            Providers\Google::class,
            Providers\LinkedIn::class,
        ], 'fof-oauth.providers');

        // Single stable route — provider is a plain path segment, validated in the controller.
        $this->container->resolving('flarum.forum.routes', function (RouteCollection $collection, Container $container) {
            /** @var RouteHandlerFactory $factory */
            $factory = $container->make(RouteHandlerFactory::class);

            $collection->addRoute(
                'GET',
                '/auth/{provider}',
                'fof-oauth',
                $factory->toController(Controllers\AuthController::class)
            );
        });
    }

    public function boot(): void
    {
        $this->container->singleton('fof-oauth.providers.forum', function (Container $container) {
            /** @var Config $config */
            $config = $container->make(Config::class);

            if ($config->inDebugMode()) {
                return $this->mapProviders();
            }

            /** @var Cache $cache */
            $cache = $container->make(Cache::class);
            $key = 'fof-oauth.providers.forum';

            return $cache->get($key) ?? tap($this->mapProviders(), fn ($data) => $cache->forever($key, $data));
        });

        $this->container->singleton('fof-oauth.providers.admin', function (Container $container) {
            /** @var Config $config */
            $config = $container->make(Config::class);

            if ($config->inDebugMode()) {
                return $this->mapProviders(true);
            }

            /** @var Cache $cache */
            $cache = $container->make(Cache::class);
            $key = 'fof-oauth.providers.admin';

            return $cache->get($key) ?? tap($this->mapProviders(true), fn ($data) => $cache->forever($key, $data));
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

        return array_values(array_filter(array_map(static function (Provider $provider) {
            if (!$provider->enabled()) {
                return null;
            }

            return [
                'name'     => $provider->name(),
                'icon'     => $provider->icon(),
                'priority' => $provider->priority(),
            ];
        }, iterator_to_array($providers))));
    }
}
