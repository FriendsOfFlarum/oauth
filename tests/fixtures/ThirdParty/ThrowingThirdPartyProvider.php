<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Tests\fixtures\ThirdParty;

use RuntimeException;

/**
 * A third-party provider whose verification lookup fails, e.g. because the provider's API is down.
 */
class ThrowingThirdPartyProvider extends ThirdPartyProvider
{
    public function name(): string
    {
        return 'throwingthirdparty';
    }

    public function getProviderVerifiedEmail($user, string $token): ?string
    {
        throw new RuntimeException('verification API unavailable');
    }
}
