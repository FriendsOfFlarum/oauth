<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Tests\unit\Providers;

use Flarum\Forum\Auth\Registration;
use FoF\OAuth\Errors\AuthenticationException;
use FoF\OAuth\Providers\Twitter;
use League\OAuth1\Client\Server\User;

class TwitterTest extends ProviderTestCase
{
    private function suggest(?string $email): Registration
    {
        $user = new User();
        $user->uid = '1';
        $user->nickname = 'user';
        $user->imageUrl = 'https://pbs.twimg.com/profile_images/1/avatar_normal.jpg';
        $user->email = $email;

        $registration = new Registration();

        (new Twitter($this->settings()))->suggestions($registration, $user, '');

        return $registration;
    }

    /**
     * Twitter exposes no verification flag; any returned email is trusted.
     */
    public function test_returned_email_is_trusted(): void
    {
        $this->assertTrustedEmail('user@example.com', $this->suggest('user@example.com'));
    }

    public function test_missing_email_is_rejected(): void
    {
        $this->expectException(AuthenticationException::class);

        $this->suggest(null);
    }

    public function test_returned_email_is_treated_as_verified(): void
    {
        $user = new User();
        $user->email = 'user@example.com';

        $this->assertSame('user@example.com', (new Twitter($this->settings()))->getProviderVerifiedEmail($user, ''));
    }

    public function test_no_verified_email_when_email_missing(): void
    {
        $this->assertNull((new Twitter($this->settings()))->getProviderVerifiedEmail(new User(), ''));
    }
}
