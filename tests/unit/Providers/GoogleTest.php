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

class GoogleTest extends ProviderTestCase
{
    private function suggest(array $response): Registration
    {
        $registration = new Registration();

        (new Google($this->settings()))->suggestions($registration, new GoogleUser($response), 'token');

        return $registration;
    }

    public function test_gmail_address_is_trusted(): void
    {
        $registration = $this->suggest(['sub' => '1', 'name' => 'User', 'email' => 'user@gmail.com']);

        $this->assertTrustedEmail('user@gmail.com', $registration);
    }

    public function test_verified_workspace_address_is_trusted(): void
    {
        $registration = $this->suggest([
            'sub' => '1', 'name' => 'User', 'email' => 'user@company.example', 'email_verified' => true, 'hd' => 'company.example',
        ]);

        $this->assertTrustedEmail('user@company.example', $registration);
    }

    public function test_unverified_workspace_address_is_only_suggested(): void
    {
        $registration = $this->suggest([
            'sub' => '1', 'name' => 'User', 'email' => 'user@company.example', 'email_verified' => false, 'hd' => 'company.example',
        ]);

        $this->assertSuggestedEmail('user@company.example', $registration);
    }

    public function test_verified_personal_account_address_is_trusted(): void
    {
        $registration = $this->suggest([
            'sub' => '1', 'name' => 'User', 'email' => 'user@elsewhere.example', 'email_verified' => true,
        ]);

        $this->assertTrustedEmail('user@elsewhere.example', $registration);
    }

    public function test_unverified_personal_account_address_is_only_suggested(): void
    {
        $registration = $this->suggest([
            'sub' => '1', 'name' => 'User', 'email' => 'user@elsewhere.example', 'email_verified' => false,
        ]);

        $this->assertSuggestedEmail('user@elsewhere.example', $registration);
    }

    public function test_missing_email_is_rejected(): void
    {
        $this->expectException(AuthenticationException::class);

        $this->suggest(['sub' => '1', 'name' => 'User']);
    }

    // getProviderVerifiedEmail()

    private function verified(array $response): ?string
    {
        return (new Google($this->settings()))->getProviderVerifiedEmail(new GoogleUser($response), 'token');
    }

    public function test_verified_email_returned_for_gmail_and_verified_workspace(): void
    {
        $this->assertSame('user@gmail.com', $this->verified(['sub' => '1', 'email' => 'user@gmail.com']));
        $this->assertSame('user@company.example', $this->verified([
            'sub' => '1', 'email' => 'user@company.example', 'email_verified' => true, 'hd' => 'company.example',
        ]));
    }

    public function test_verified_email_returned_for_verified_personal_account_address(): void
    {
        $this->assertSame('user@elsewhere.example', $this->verified(['sub' => '1', 'email' => 'user@elsewhere.example', 'email_verified' => true]));
    }

    public function test_no_verified_email_when_unverified_missing_or_not_boolean_true(): void
    {
        $this->assertNull($this->verified(['sub' => '1', 'email' => 'user@company.example', 'email_verified' => false, 'hd' => 'company.example']));
        $this->assertNull($this->verified(['sub' => '1', 'email' => 'user@elsewhere.example', 'email_verified' => false]));
        $this->assertNull($this->verified(['sub' => '1', 'email' => 'user@elsewhere.example']));
        $this->assertNull($this->verified(['sub' => '1', 'email' => 'user@elsewhere.example', 'email_verified' => 'true']));
        $this->assertNull($this->verified(['sub' => '1', 'email_verified' => true]));
    }
}
