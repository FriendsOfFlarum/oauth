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

use Flarum\Api\Context;
use Flarum\User\Exception\PermissionDeniedException;
use FoF\OAuth\OAuthOnly;
use Illuminate\Support\Arr;

/**
 * In OAuth-only mode, only registrations completing a provider sign-up (carrying a registration token) are allowed.
 * Admins can still create users directly.
 */
class RequireRegistrationToken
{
    public function __construct(
        protected OAuthOnly $oauthOnly
    ) {
    }

    /**
     * @throws PermissionDeniedException
     */
    public function __invoke(Context $context): void
    {
        if ($this->oauthOnly->allowsPasswordAuth($context->getActor())) {
            return;
        }

        if (empty(Arr::get($context->body(), 'data.attributes.token'))) {
            throw new PermissionDeniedException();
        }
    }
}
