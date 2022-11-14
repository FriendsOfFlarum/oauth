<?php

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
     * @var string
     */
    public $provider;

    /**
     * @param User $user
     * @param LoginProvider $provider
     */
    public function __construct(User $user, LoginProvider $provider)
    {
        $this->user = $user;
        $this->provider = $provider;
    }
}
