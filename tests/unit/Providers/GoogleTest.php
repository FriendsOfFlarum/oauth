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
use FoF\OAuth\Providers\Google;
use League\OAuth2\Client\Provider\GoogleUser;
use PHPUnit\Framework\Attributes\Test;

class GoogleTest extends ProviderTestCase
{
    private function suggest(array $response): Registration
    {
        $registration = new Registration();

        (new Google($this->settings()))->suggestions($registration, new GoogleUser($response), 'token');

        return $registration;
    }

    #[Test]
    public function gmail_address_is_trusted(): void
    {
        $this->assertTrustedEmail('user@gmail.com', $this->suggest(['sub' => '1', 'name' => 'User', 'email' => 'user@gmail.com']));
    }

    #[Test]
    public function verified_workspace_address_is_trusted(): void
    {
        $this->assertTrustedEmail('user@company.example', $this->suggest([
            'sub' => '1', 'name' => 'User', 'email' => 'user@company.example', 'email_verified' => true, 'hd' => 'company.example',
        ]));
    }

    #[Test]
    public function unverified_workspace_address_is_only_suggested(): void
    {
        $this->assertSuggestedEmail('user@company.example', $this->suggest([
            'sub' => '1', 'name' => 'User', 'email' => 'user@company.example', 'email_verified' => false, 'hd' => 'company.example',
        ]));
    }

    #[Test]
    public function verified_personal_account_address_is_trusted(): void
    {
        $this->assertTrustedEmail('user@elsewhere.example', $this->suggest([
            'sub' => '1', 'name' => 'User', 'email' => 'user@elsewhere.example', 'email_verified' => true,
        ]));
    }

    #[Test]
    public function unverified_personal_account_address_is_only_suggested(): void
    {
        $this->assertSuggestedEmail('user@elsewhere.example', $this->suggest([
            'sub' => '1', 'name' => 'User', 'email' => 'user@elsewhere.example', 'email_verified' => false,
        ]));
    }

    #[Test]
    public function missing_email_is_rejected(): void
    {
        $this->expectException(AuthenticationException::class);

        $this->suggest(['sub' => '1', 'name' => 'User']);
    }

    private function verified(array $response): ?string
    {
        return (new Google($this->settings()))->getProviderVerifiedEmail(new GoogleUser($response), 'token');
    }

    #[Test]
    public function verified_email_returned_for_gmail_workspace_and_verified_personal_account(): void
    {
        $this->assertSame('user@gmail.com', $this->verified(['sub' => '1', 'email' => 'user@gmail.com']));
        $this->assertSame('user@company.example', $this->verified(['sub' => '1', 'email' => 'user@company.example', 'email_verified' => true, 'hd' => 'company.example']));
        $this->assertSame('user@elsewhere.example', $this->verified(['sub' => '1', 'email' => 'user@elsewhere.example', 'email_verified' => true]));
    }

    #[Test]
    public function no_verified_email_when_unverified_missing_or_not_boolean_true(): void
    {
        $this->assertNull($this->verified(['sub' => '1', 'email' => 'user@company.example', 'email_verified' => false, 'hd' => 'company.example']));
        $this->assertNull($this->verified(['sub' => '1', 'email' => 'user@elsewhere.example', 'email_verified' => false]));
        $this->assertNull($this->verified(['sub' => '1', 'email' => 'user@elsewhere.example']));
        $this->assertNull($this->verified(['sub' => '1', 'email' => 'user@elsewhere.example', 'email_verified' => 'true']));
        $this->assertNull($this->verified(['sub' => '1', 'email_verified' => true]));
    }
}
