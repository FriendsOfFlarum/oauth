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
use FoF\OAuth\Providers\Discord;
use Wohali\OAuth2\Client\Provider\DiscordResourceOwner;

class DiscordTest extends ProviderTestCase
{
    private function suggest(array $response): Registration
    {
        $registration = new Registration();

        (new Discord($this->settings()))->suggestions($registration, new DiscordResourceOwner($response), 'token');

        return $registration;
    }

    public function test_verified_email_is_trusted(): void
    {
        $registration = $this->suggest(['id' => '1', 'username' => 'user', 'email' => 'user@example.com', 'verified' => true]);

        $this->assertTrustedEmail('user@example.com', $registration);
    }

    public function test_unverified_email_is_only_suggested(): void
    {
        $registration = $this->suggest(['id' => '1', 'username' => 'user', 'email' => 'user@example.com', 'verified' => false]);

        $this->assertSuggestedEmail('user@example.com', $registration);
    }

    public function test_missing_verified_flag_is_only_suggested(): void
    {
        $registration = $this->suggest(['id' => '1', 'username' => 'user', 'email' => 'user@example.com']);

        $this->assertSuggestedEmail('user@example.com', $registration);
    }

    public function test_missing_email_is_rejected(): void
    {
        $this->expectException(AuthenticationException::class);

        $this->suggest(['id' => '1', 'username' => 'user', 'verified' => true]);
    }

    // getProviderVerifiedEmail()

    private function verified(array $response): ?string
    {
        return (new Discord($this->settings()))->getProviderVerifiedEmail(new DiscordResourceOwner($response), 'token');
    }

    public function test_verified_email_returned_when_verified(): void
    {
        $this->assertSame('user@example.com', $this->verified(['id' => '1', 'email' => 'user@example.com', 'verified' => true]));
    }

    public function test_no_verified_email_when_unverified_or_flag_missing(): void
    {
        $this->assertNull($this->verified(['id' => '1', 'email' => 'user@example.com', 'verified' => false]));
        $this->assertNull($this->verified(['id' => '1', 'email' => 'user@example.com']));
    }

    public function test_no_verified_email_when_flag_is_not_boolean_true(): void
    {
        $this->assertNull($this->verified(['id' => '1', 'email' => 'user@example.com', 'verified' => 'true']));
        $this->assertNull($this->verified(['id' => '1', 'email' => 'user@example.com', 'verified' => 1]));
    }

    public function test_no_verified_email_when_email_missing(): void
    {
        $this->assertNull($this->verified(['id' => '1', 'verified' => true]));
    }
}
