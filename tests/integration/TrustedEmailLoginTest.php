<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Tests\integration;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\LoginProvider;

/**
 * Covers how a provider's trusted vs suggested email is handled for an identity not yet linked to any account.
 */
class TrustedEmailLoginTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use PerformsOAuthLogin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-oauth');

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
                [
                    'id'                 => 4, 'username' => 'existing',
                    'is_email_confirmed' => 1, 'email' => 'existing@machine.local',
                    'joined_at'          => '2021-01-01 00:00:00',
                ],
            ],
        ]);

        $this->setting('fof-oauth.gitlab', 1);
        $this->setting('fof-oauth.gitlab.client_id', 'test');
        $this->setting('fof-oauth.gitlab.client_secret', 'test');
        $this->setting('fof-oauth.update_email_from_provider', 0);
    }

    public function test_trusted_email_matching_an_account_logs_in_and_links_it(): void
    {
        $this->mockGitlab(['id' => 999, 'username' => 'newcomer', 'email' => 'existing@machine.local', 'confirmed_at' => '2021-01-01T00:00:00Z']);

        $payload = $this->oauthLogin('gitlab');

        $this->assertTrue($payload['loggedIn'] ?? false);
        $this->assertSame(4, LoginProvider::where('provider', 'gitlab')->where('identifier', '999')->value('user_id'));
    }

    public function test_suggested_email_matching_an_account_does_not_log_in(): void
    {
        $this->mockGitlab(['id' => 999, 'username' => 'newcomer', 'email' => 'existing@machine.local', 'confirmed_at' => null]);

        $payload = $this->oauthLogin('gitlab');

        $this->assertArrayNotHasKey('loggedIn', $payload);
        $this->assertArrayHasKey('token', $payload);
        $this->assertSame('existing@machine.local', $payload['email'] ?? null);
        $this->assertNotContains('email', $payload['provided'] ?? []);
        $this->assertFalse(LoginProvider::where('provider', 'gitlab')->where('identifier', '999')->exists());
    }

    public function test_trusted_email_not_matching_any_account_starts_registration(): void
    {
        $this->mockGitlab(['id' => 999, 'username' => 'newcomer', 'email' => 'newcomer@example.com', 'confirmed_at' => '2021-01-01T00:00:00Z']);

        $payload = $this->oauthLogin('gitlab');

        $this->assertArrayNotHasKey('loggedIn', $payload);
        $this->assertArrayHasKey('token', $payload);
        $this->assertSame('newcomer@example.com', $payload['email'] ?? null);
        $this->assertContains('email', $payload['provided'] ?? []);
    }
}
