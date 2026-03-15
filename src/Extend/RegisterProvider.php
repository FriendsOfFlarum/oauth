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
use RuntimeException;

class RegisterProvider implements ExtenderInterface
{
    public function __construct(
        private readonly string $provider
    ) {
    }

    public function extend(Container $container, ?Extension $extension = null): void
    {
        /** @var Provider $instance */
        $instance = $container->make($this->provider);

        if (!$instance instanceof Provider) {
            throw new InvalidArgumentException(
                "{$this->provider} must extend ".Provider::class
            );
        }

        // Guard against duplicate provider names — two extensions registering the
        // same name would cause silent routing collisions and confusing behaviour.
        foreach ($container->tagged('fof-oauth.providers') as $existing) {
            if ($existing->name() === $instance->name()) {
                throw new RuntimeException(
                    "OAuth provider '{$instance->name()}' is already registered. "
                    .'Each provider name must be unique across all extensions.'
                );
            }
        }

        $container->tag([$this->provider], 'fof-oauth.providers');
    }
}
