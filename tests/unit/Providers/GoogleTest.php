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

    public function test_verified_non_workspace_non_gmail_address_is_only_suggested(): void
    {
        $registration = $this->suggest([
            'sub' => '1', 'name' => 'User', 'email' => 'user@elsewhere.example', 'email_verified' => true,
        ]);

        $this->assertSuggestedEmail('user@elsewhere.example', $registration);
    }

    public function test_missing_email_is_rejected(): void
    {
        $this->expectException(AuthenticationException::class);

        $this->suggest(['sub' => '1', 'name' => 'User']);
    }
}
