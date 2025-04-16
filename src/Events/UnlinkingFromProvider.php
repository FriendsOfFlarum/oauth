<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Events;

use Flarum\User\LoginProvider;
use Flarum\User\User;

class UnlinkingFromProvider
{
    /**
     * @var User
     */
    public $user;

    /**
     * @var LoginProvider
     */
    public $provider;

    /**
     * @var User|null
     */
    public $actor;

    /**
     * @param User          $user
     * @param LoginProvider $provider
     */
    public function __construct(User $user, LoginProvider $provider, ?User $actor = null)
    {
        $this->user = $user;
        $this->provider = $provider;
        $this->actor = $actor;
    }
}
