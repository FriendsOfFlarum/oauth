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
    private $provider;

    public function __construct(string $provider)
    {
        $this->provider = $provider;
    }

    public function extend(Container $container, ?Extension $extension = null)
    {
        $provider = $container->make($this->provider);

        if ($provider instanceof Provider) {
            $container->tag([
                $this->provider,
            ], 'fof-oauth.providers');
        } else {
            throw new InvalidArgumentException("{$this->provider} has to extend ".Provider::class);
        }
    }
}
