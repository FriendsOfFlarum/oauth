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
use FoF\OAuth\Providers\Custom\LinkedIn\Provider\LinkedInResourceOwner;
use FoF\OAuth\Providers\LinkedIn;

class LinkedInTest extends ProviderTestCase
{
    private function suggest(array $response): Registration
    {
        $registration = new Registration();

        (new LinkedIn($this->settings()))->suggestions($registration, new LinkedInResourceOwner($response), 'token');

        return $registration;
    }

    public function test_verified_email_is_trusted(): void
    {
        $registration = $this->suggest(['sub' => '1', 'given_name' => 'User', 'email' => 'user@example.com', 'email_verified' => true]);

        $this->assertTrustedEmail('user@example.com', $registration);
    }

    public function test_unverified_email_is_only_suggested(): void
    {
        $registration = $this->suggest(['sub' => '1', 'given_name' => 'User', 'email' => 'user@example.com', 'email_verified' => false]);

        $this->assertSuggestedEmail('user@example.com', $registration);
    }

    public function test_missing_verified_flag_is_only_suggested(): void
    {
        $registration = $this->suggest(['sub' => '1', 'given_name' => 'User', 'email' => 'user@example.com']);

        $this->assertSuggestedEmail('user@example.com', $registration);
    }

    public function test_missing_email_is_rejected(): void
    {
        $this->expectException(AuthenticationException::class);

        $this->suggest(['sub' => '1', 'given_name' => 'User', 'email_verified' => true]);
    }
}
