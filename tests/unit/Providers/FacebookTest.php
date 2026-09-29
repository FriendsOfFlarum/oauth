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
use FoF\OAuth\Providers\Facebook;
use League\OAuth2\Client\Provider\FacebookUser;

class FacebookTest extends ProviderTestCase
{
    private function suggest(array $response): Registration
    {
        $registration = new Registration();

        (new Facebook($this->settings()))->suggestions($registration, new FacebookUser($response), 'token');

        return $registration;
    }

    /**
     * Facebook exposes no verification flag; any returned email is trusted.
     */
    public function test_returned_email_is_trusted(): void
    {
        $registration = $this->suggest(['id' => '1', 'name' => 'User', 'email' => 'user@example.com']);

        $this->assertTrustedEmail('user@example.com', $registration);
    }

    public function test_missing_email_is_rejected(): void
    {
        $this->expectException(AuthenticationException::class);

        $this->suggest(['id' => '1', 'name' => 'User']);
    }

    public function test_returned_email_is_treated_as_verified(): void
    {
        $user = new FacebookUser(['id' => '1', 'email' => 'user@example.com']);

        $this->assertSame('user@example.com', (new Facebook($this->settings()))->getProviderVerifiedEmail($user, 'token'));
    }

    public function test_no_verified_email_when_email_missing(): void
    {
        $this->assertNull((new Facebook($this->settings()))->getProviderVerifiedEmail(new FacebookUser(['id' => '1']), 'token'));
    }
}
