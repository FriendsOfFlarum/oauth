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
use PHPUnit\Framework\Attributes\Test;
use Wohali\OAuth2\Client\Provider\DiscordResourceOwner;

class DiscordTest extends ProviderTestCase
{
    private function suggest(array $response): Registration
    {
        $registration = new Registration();

        (new Discord($this->settings()))->suggestions($registration, new DiscordResourceOwner($response), 'token');

        return $registration;
    }

    #[Test]
    public function verified_email_is_trusted(): void
    {
        $this->assertTrustedEmail('user@example.com', $this->suggest(['id' => '1', 'username' => 'user', 'email' => 'user@example.com', 'verified' => true]));
    }

    #[Test]
    public function unverified_email_is_only_suggested(): void
    {
        $this->assertSuggestedEmail('user@example.com', $this->suggest(['id' => '1', 'username' => 'user', 'email' => 'user@example.com', 'verified' => false]));
    }

    #[Test]
    public function missing_verified_flag_is_only_suggested(): void
    {
        $this->assertSuggestedEmail('user@example.com', $this->suggest(['id' => '1', 'username' => 'user', 'email' => 'user@example.com']));
    }

    #[Test]
    public function missing_email_is_rejected(): void
    {
        $this->expectException(AuthenticationException::class);

        $this->suggest(['id' => '1', 'username' => 'user', 'verified' => true]);
    }

    private function verified(array $response): ?string
    {
        return (new Discord($this->settings()))->getProviderVerifiedEmail(new DiscordResourceOwner($response), 'token');
    }

    #[Test]
    public function verified_email_returned_when_verified(): void
    {
        $this->assertSame('user@example.com', $this->verified(['id' => '1', 'email' => 'user@example.com', 'verified' => true]));
    }

    #[Test]
    public function no_verified_email_when_unverified_missing_or_not_boolean_true(): void
    {
        $this->assertNull($this->verified(['id' => '1', 'email' => 'user@example.com', 'verified' => false]));
        $this->assertNull($this->verified(['id' => '1', 'email' => 'user@example.com']));
        $this->assertNull($this->verified(['id' => '1', 'email' => 'user@example.com', 'verified' => 'true']));
        $this->assertNull($this->verified(['id' => '1', 'email' => 'user@example.com', 'verified' => 1]));
    }

    #[Test]
    public function no_verified_email_when_email_missing(): void
    {
        $this->assertNull($this->verified(['id' => '1', 'verified' => true]));
    }
}
