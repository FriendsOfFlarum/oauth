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
use Flarum\User\User;

/**
 * Covers linking an OAuth identity to the currently logged-in account.
 */
class AccountLinkingTest extends TestCase
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
                    'id'                 => 3, 'username' => 'UserA',
                    'is_email_confirmed' => 1, 'email' => 'usera@machine.local',
                    'joined_at'          => '2021-01-01 00:00:00',
                ],
                [
                    'id'                 => 4, 'username' => 'UserB',
                    'is_email_confirmed' => 1, 'email' => 'userb@machine.local',
                    'joined_at'          => '2021-01-01 00:00:00',
                ],
            ],
            'login_providers' => [
                // User 4 already has this GitLab identity linked.
                ['id' => 1, 'user_id' => 4, 'provider' => 'gitlab', 'identifier' => '55555', 'created_at' => '2021-01-01 00:00:00'],
            ],
        ]);

        $this->setting('fof-oauth.gitlab', 1);
        $this->setting('fof-oauth.gitlab.client_id', 'test');
        $this->setting('fof-oauth.gitlab.client_secret', 'test');
        $this->setting('fof-oauth.update_email_from_provider', 0);
    }

    public function test_authenticated_user_can_link_provider_account(): void
    {
        $this->mockGitlab(['id' => 44444, 'username' => 'usera', 'email' => 'usera@machine.local', 'confirmed_at' => '2021-01-01T00:00:00Z']);

        $response = $this->oauthLinkCallback('gitlab', 3, 3);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('window.opener.app.linkingComplete()', $response->getBody()->getContents());
        $this->assertTrue(
            LoginProvider::where('user_id', 3)->where('provider', 'gitlab')->where('identifier', '44444')->exists()
        );
    }

    public function test_link_fails_when_linkTo_user_id_does_not_match_actor(): void
    {
        $this->mockGitlab(['id' => 77777, 'username' => 'usera', 'email' => 'usera@machine.local', 'confirmed_at' => '2021-01-01T00:00:00Z']);

        $response = $this->oauthLinkCallback('gitlab', 3, 4);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertFalse(LoginProvider::where('provider', 'gitlab')->where('identifier', '77777')->exists());
    }

    public function test_link_fails_when_provider_already_linked_to_another_user(): void
    {
        $this->mockGitlab(['id' => 55555, 'username' => 'usera', 'email' => 'usera@machine.local', 'confirmed_at' => '2021-01-01T00:00:00Z']);

        $response = $this->oauthLinkCallback('gitlab', 3, 3);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertSame(4, LoginProvider::where('provider', 'gitlab')->where('identifier', '55555')->value('user_id'));
    }

    public function test_linking_requests_confirmation_of_verified_provider_email(): void
    {
        $this->setting('fof-oauth.update_email_from_provider', 1);
        $this->setting('mail_driver', 'log');
        $this->mockGitlab(['id' => 44444, 'username' => 'usera', 'email' => 'different@example.com', 'confirmed_at' => '2021-01-01T00:00:00Z']);
        $logs = $this->captureLogs();

        $response = $this->oauthLinkCallback('gitlab', 3, 3);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('usera@machine.local', User::find(3)->email);
        $this->assertCount(1, $this->sentMail($logs, 'different@example.com'));
        $this->assertCount(1, $this->sentMail($logs, 'usera@machine.local'));
    }

    public function test_linking_does_not_sync_unverified_provider_email(): void
    {
        $this->setting('fof-oauth.update_email_from_provider', 1);
        $this->mockGitlab(['id' => 44444, 'username' => 'usera', 'email' => 'different@example.com', 'confirmed_at' => null]);

        $response = $this->oauthLinkCallback('gitlab', 3, 3);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue(LoginProvider::where('user_id', 3)->where('identifier', '44444')->exists());
        $this->assertSame('usera@machine.local', User::find(3)->email);
    }
}
