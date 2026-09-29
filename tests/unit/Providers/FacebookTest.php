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
use PHPUnit\Framework\Attributes\Test;

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
    #[Test]
    public function returned_email_is_trusted(): void
    {
        $this->assertTrustedEmail('user@example.com', $this->suggest(['id' => '1', 'name' => 'User', 'email' => 'user@example.com']));
    }

    #[Test]
    public function missing_email_is_rejected(): void
    {
        $this->expectException(AuthenticationException::class);

        $this->suggest(['id' => '1', 'name' => 'User']);
    }

    #[Test]
    public function returned_email_is_treated_as_verified(): void
    {
        $this->assertSame('user@example.com', (new Facebook($this->settings()))->getProviderVerifiedEmail(new FacebookUser(['id' => '1', 'email' => 'user@example.com']), 'token'));
    }

    #[Test]
    public function no_verified_email_when_email_missing(): void
    {
        $this->assertNull((new Facebook($this->settings()))->getProviderVerifiedEmail(new FacebookUser(['id' => '1']), 'token'));
    }
}
