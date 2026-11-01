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
use FoF\OAuth\Providers\GitLab;
use League\OAuth2\Client\Token\AccessToken;
use Omines\OAuth2\Client\Provider\GitlabResourceOwner;
use PHPUnit\Framework\Attributes\Test;

class GitLabTest extends ProviderTestCase
{
    private function suggest(array $response): Registration
    {
        $registration = new Registration();
        $owner = new GitlabResourceOwner(
            array_merge(['id' => 1, 'username' => 'user', 'avatar_url' => 'https://gitlab.example/avatar.png'], $response),
            new AccessToken(['access_token' => 'token'])
        );

        (new GitLab($this->settings()))->suggestions($registration, $owner, 'token');

        return $registration;
    }

    #[Test]
    public function confirmed_email_is_trusted(): void
    {
        $this->assertTrustedEmail('user@example.com', $this->suggest(['email' => 'user@example.com', 'confirmed_at' => '2021-01-01T00:00:00Z']));
    }

    #[Test]
    public function unconfirmed_email_is_only_suggested(): void
    {
        $this->assertSuggestedEmail('user@example.com', $this->suggest(['email' => 'user@example.com', 'confirmed_at' => null]));
    }

    #[Test]
    public function missing_confirmed_at_is_only_suggested(): void
    {
        $this->assertSuggestedEmail('user@example.com', $this->suggest(['email' => 'user@example.com']));
    }

    #[Test]
    public function empty_email_is_rejected(): void
    {
        $this->expectException(AuthenticationException::class);

        $this->suggest(['email' => '', 'confirmed_at' => '2021-01-01T00:00:00Z']);
    }

    private function verified(array $response): ?string
    {
        $owner = new GitlabResourceOwner(
            array_merge(['id' => 1, 'username' => 'user', 'avatar_url' => 'https://gitlab.example/avatar.png'], $response),
            new AccessToken(['access_token' => 'token'])
        );

        return (new GitLab($this->settings()))->getProviderVerifiedEmail($owner, 'token');
    }

    #[Test]
    public function verified_email_returned_when_confirmed(): void
    {
        $this->assertSame('user@example.com', $this->verified(['email' => 'user@example.com', 'confirmed_at' => '2021-01-01T00:00:00Z']));
    }

    #[Test]
    public function no_verified_email_when_unconfirmed_or_missing(): void
    {
        $this->assertNull($this->verified(['email' => 'user@example.com', 'confirmed_at' => null]));
        $this->assertNull($this->verified(['email' => 'user@example.com']));
    }

    #[Test]
    public function no_verified_email_when_email_empty(): void
    {
        $this->assertNull($this->verified(['email' => '', 'confirmed_at' => '2021-01-01T00:00:00Z']));
    }
}
