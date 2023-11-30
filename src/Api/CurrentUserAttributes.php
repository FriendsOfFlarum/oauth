<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Api;

use Flarum\Api\Serializer\CurrentUserSerializer;
use Flarum\User\User;
use FoF\Extend\Controllers\AbstractOAuthController;
use Illuminate\Contracts\Cache\Store as Cache;

class CurrentUserAttributes
{
    /**
     * @var Cache
     */
    protected $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    public function __invoke(CurrentUserSerializer $serializer, User $user, array $attributes): array
    {
        $session = $serializer->getRequest()->getAttribute('session');

        if ($session !== null) {
            $attributes['loginProvider'] = $this->cache->get(AbstractOAuthController::SESSION_OAUTH2PROVIDER.'_'.$session->getId());
        }

        return $attributes;
    }
}
