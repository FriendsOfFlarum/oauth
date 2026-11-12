<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Extend;

use Flarum\Extend\ExtenderInterface;
use Flarum\Extension\Extension;
use FoF\OAuth\Provider;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

class RegisterProvider implements ExtenderInterface
{
    public function __construct(
        private readonly string $provider
    ) {
    }

    public function extend(Container $container, ?Extension $extension = null): void
    {
        if (!is_subclass_of($this->provider, Provider::class) && !$container->bound($this->provider)) {
            throw new InvalidArgumentException(
                "{$this->provider} must extend ".Provider::class
            );
        }

        // Validate container bindings lazily: resolving them here can freeze routes before extensions finish loading.
        $container->extend($this->provider, function ($instance) {
            if (!$instance instanceof Provider) {
                throw new InvalidArgumentException(
                    "{$this->provider} must extend ".Provider::class
                );
            }

            return $instance;
        });

        $container->tag([$this->provider], 'fof-oauth.providers');
    }
}
