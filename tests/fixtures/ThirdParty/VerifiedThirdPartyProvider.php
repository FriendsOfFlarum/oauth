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

/**
 * A third-party provider that follows the email trust contract, with a boolean `verified` flag.
 */
class VerifiedThirdPartyProvider extends ThirdPartyProvider
{
    public function name(): string
    {
        return 'verifiedthirdparty';
    }

    public function getProviderVerifiedEmail(mixed $user, string $token): ?string
    {
        return ($user->toArray()['verified'] ?? false) === true ? $user->getEmail() : null;
    }
}
