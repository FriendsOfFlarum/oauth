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

use Flarum\Forum\Auth\Registration;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class SettingSuggestions
{
    /**
     * @var string|null
     */
    public $provider;

    /**
     * @var Registration
     */
    public $registration;

    /**
     * @var ResourceOwnerInterface
     */
    public $resourceOwner;

    /**
     * @var string|null
     */
    public $token;

    public function __construct(string $provider, Registration $registration, ResourceOwnerInterface $resourceOwner, ?string $token = null)
    {
        $this->provider = $provider;
        $this->registration = $registration;
        $this->resourceOwner = $resourceOwner;
        $this->token = $token;
    }
}
