<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Auth;

use Flarum\User\User;
use FoF\OAuth\OAuthOnly;

/**
 * Returning false halts every other checker, so no password is accepted for the user — this covers
 * /api/token, /login and any extension that calls User::checkPassword().
 */
class OAuthOnlyPasswordChecker
{
    public function __construct(
        protected OAuthOnly $oauthOnly
    ) {
    }

    public function __invoke(User $user): ?bool
    {
        return $this->oauthOnly->allowsPasswordAuth($user) ? null : false;
    }
}
