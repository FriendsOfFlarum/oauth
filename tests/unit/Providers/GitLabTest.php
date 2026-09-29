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

class GitLabTest extends ProviderTestCase
{
    private function suggest(array $response): Registration
    {
        $registration = new Registration();
        $owner = new GitlabResourceOwner($response, new AccessToken(['access_token' => 'token']));

        (new GitLab($this->settings()))->suggestions($registration, $owner, 'token');

        return $registration;
    }

    public function test_confirmed_email_is_trusted(): void
    {
        $registration = $this->suggest([
            'id' => 1, 'username' => 'user', 'avatar_url' => 'https://gitlab.example/avatar.png', 'email' => 'user@example.com', 'confirmed_at' => '2021-01-01T00:00:00Z',
        ]);

        $this->assertTrustedEmail('user@example.com', $registration);
    }

    public function test_unconfirmed_email_is_only_suggested(): void
    {
        $registration = $this->suggest([
            'id' => 1, 'username' => 'user', 'avatar_url' => 'https://gitlab.example/avatar.png', 'email' => 'user@example.com', 'confirmed_at' => null,
        ]);

        $this->assertSuggestedEmail('user@example.com', $registration);
    }

    public function test_missing_confirmed_at_is_only_suggested(): void
    {
        $registration = $this->suggest(['id' => 1, 'username' => 'user', 'avatar_url' => 'https://gitlab.example/avatar.png', 'email' => 'user@example.com']);

        $this->assertSuggestedEmail('user@example.com', $registration);
    }

    public function test_empty_email_is_rejected(): void
    {
        $this->expectException(AuthenticationException::class);

        $this->suggest(['id' => 1, 'username' => 'user', 'avatar_url' => 'https://gitlab.example/avatar.png', 'email' => '', 'confirmed_at' => '2021-01-01T00:00:00Z']);
    }
}
