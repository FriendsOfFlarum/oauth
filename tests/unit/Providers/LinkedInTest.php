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
use PHPUnit\Framework\Attributes\Test;

class LinkedInTest extends ProviderTestCase
{
    private function suggest(array $response): Registration
    {
        $registration = new Registration();

        (new LinkedIn($this->settings()))->suggestions($registration, new LinkedInResourceOwner($response), 'token');

        return $registration;
    }

    #[Test]
    public function verified_email_is_trusted(): void
    {
        $this->assertTrustedEmail('user@example.com', $this->suggest(['sub' => '1', 'given_name' => 'User', 'email' => 'user@example.com', 'email_verified' => true]));
    }

    #[Test]
    public function unverified_email_is_only_suggested(): void
    {
        $this->assertSuggestedEmail('user@example.com', $this->suggest(['sub' => '1', 'given_name' => 'User', 'email' => 'user@example.com', 'email_verified' => false]));
    }

    #[Test]
    public function missing_verified_flag_is_only_suggested(): void
    {
        $this->assertSuggestedEmail('user@example.com', $this->suggest(['sub' => '1', 'given_name' => 'User', 'email' => 'user@example.com']));
    }

    #[Test]
    public function missing_email_is_rejected(): void
    {
        $this->expectException(AuthenticationException::class);

        $this->suggest(['sub' => '1', 'given_name' => 'User', 'email_verified' => true]);
    }

    private function verified(array $response): ?string
    {
        return (new LinkedIn($this->settings()))->getProviderVerifiedEmail(new LinkedInResourceOwner($response), 'token');
    }

    #[Test]
    public function verified_email_returned_when_email_verified(): void
    {
        $this->assertSame('user@example.com', $this->verified(['sub' => '1', 'email' => 'user@example.com', 'email_verified' => true]));
    }

    #[Test]
    public function no_verified_email_when_unverified_missing_or_not_boolean_true(): void
    {
        $this->assertNull($this->verified(['sub' => '1', 'email' => 'user@example.com', 'email_verified' => false]));
        $this->assertNull($this->verified(['sub' => '1', 'email' => 'user@example.com']));
        $this->assertNull($this->verified(['sub' => '1', 'email' => 'user@example.com', 'email_verified' => 'true']));
    }

    #[Test]
    public function no_verified_email_when_email_missing(): void
    {
        $this->assertNull($this->verified(['sub' => '1', 'email_verified' => true]));
    }
}
