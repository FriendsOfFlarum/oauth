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

use Carbon\Carbon;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;

/**
 * Covers the `fof-oauth.update_email_from_provider` sync that runs after every OAuth login.
 */
class EmailSyncTest extends TestCase
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
                    'id'                 => 3, 'username' => 'linked',
                    'is_email_confirmed' => 1, 'email' => 'linked@machine.local',
                    'joined_at'          => '2021-01-01 00:00:00',
                ],
                [
                    'id'                 => 4, 'username' => 'other',
                    'is_email_confirmed' => 1, 'email' => 'other@machine.local',
                    'joined_at'          => '2021-01-01 00:00:00',
                ],
            ],
            'login_providers' => [
                ['id' => 1, 'user_id' => 3, 'provider' => 'gitlab', 'identifier' => '123456'],
            ],
        ]);

        $this->setting('fof-oauth.gitlab', 1);
        $this->setting('fof-oauth.gitlab.client_id', 'test');
        $this->setting('fof-oauth.gitlab.client_secret', 'test');
        $this->setting('mail_driver', 'log');
    }

    public function test_sync_is_enabled_by_default(): void
    {
        $this->mockGitlab(['id' => 123456, 'username' => 'linked', 'email' => 'new@example.com', 'confirmed_at' => '2021-01-01T00:00:00Z']);
        $logs = $this->captureLogs();

        $this->oauthLogin('gitlab');

        $this->assertCount(1, $this->sentMail($logs, 'new@example.com'));
    }

    public function test_verified_provider_email_requests_confirmation_instead_of_replacing_email(): void
    {
        $this->setting('fof-oauth.update_email_from_provider', 1);
        $this->mockGitlab(['id' => 123456, 'username' => 'linked', 'email' => 'new@example.com', 'confirmed_at' => '2021-01-01T00:00:00Z']);
        $logs = $this->captureLogs();

        $payload = $this->oauthLogin('gitlab');

        $this->assertTrue($payload['loggedIn'] ?? false);
        $this->assertSame('linked@machine.local', User::find(3)->email);

        $mail = $this->sentMail($logs, 'new@example.com');
        $this->assertCount(1, $mail);
        $this->assertStringContainsString('/confirm/', $mail[0]);
    }

    public function test_following_the_confirmation_link_completes_the_change(): void
    {
        $this->setting('fof-oauth.update_email_from_provider', 1);
        $this->mockGitlab(['id' => 123456, 'username' => 'linked', 'email' => 'new@example.com', 'confirmed_at' => '2021-01-01T00:00:00Z']);
        $logs = $this->captureLogs();

        $this->oauthLogin('gitlab');

        $mail = $this->sentMail($logs, 'new@example.com');
        $this->assertCount(1, $mail);
        $this->assertEquals(1, preg_match('#/confirm/([A-Za-z0-9]+)#', $mail[0], $matches));

        $response = $this->send(
            $this->request('POST', "/confirm/{$matches[1]}")->withAttribute('bypassCsrfToken', true)
        );

        $this->assertEquals(302, $response->getStatusCode());
        $user = User::find(3);
        $this->assertSame('new@example.com', $user->email);
        $this->assertTrue((bool) $user->is_email_confirmed);
    }

    public function test_old_address_is_notified_of_the_requested_change(): void
    {
        $this->setting('fof-oauth.update_email_from_provider', 1);
        $this->mockGitlab(['id' => 123456, 'username' => 'linked', 'email' => 'new@example.com', 'confirmed_at' => '2021-01-01T00:00:00Z']);
        $logs = $this->captureLogs();

        $this->oauthLogin('gitlab');

        // Translations resolve to their raw keys in the test environment, so the notice is identified by its key.
        $mail = $this->sentMail($logs, 'linked@machine.local');
        $this->assertCount(1, $mail);
        $this->assertStringContainsString('fof-oauth.email.provider_email_change_notice.subject', $mail[0]);
        $this->assertStringNotContainsString('/confirm/', $mail[0]);
    }

    public function test_repeat_login_does_not_resend_while_the_same_change_is_pending(): void
    {
        $this->setting('fof-oauth.update_email_from_provider', 1);
        $this->mockGitlab(['id' => 123456, 'username' => 'linked', 'email' => 'new@example.com', 'confirmed_at' => '2021-01-01T00:00:00Z']);
        $logs = $this->captureLogs();

        $this->oauthLogin('gitlab');
        $this->oauthLogin('gitlab');

        $this->assertCount(1, $this->sentMail($logs, 'new@example.com'));
        $this->assertCount(1, $this->sentMail($logs, 'linked@machine.local'));
    }

    public function test_a_different_new_address_is_requested_while_another_is_pending(): void
    {
        $this->setting('fof-oauth.update_email_from_provider', 1);
        $this->mockGitlab(['id' => 123456, 'username' => 'linked', 'email' => 'first@example.com', 'confirmed_at' => '2021-01-01T00:00:00Z']);
        $logs = $this->captureLogs();

        $this->oauthLogin('gitlab');

        $this->mockGitlab(['id' => 123456, 'username' => 'linked', 'email' => 'second@example.com', 'confirmed_at' => '2021-01-01T00:00:00Z']);
        $this->oauthLogin('gitlab');

        $this->assertCount(1, $this->sentMail($logs, 'first@example.com'));
        $this->assertCount(1, $this->sentMail($logs, 'second@example.com'));
        $this->assertCount(2, $this->sentMail($logs, 'linked@machine.local'));
    }

    public function test_an_expired_pending_change_is_requested_again(): void
    {
        $this->setting('fof-oauth.update_email_from_provider', 1);
        $this->mockGitlab(['id' => 123456, 'username' => 'linked', 'email' => 'new@example.com', 'confirmed_at' => '2021-01-01T00:00:00Z']);
        $logs = $this->captureLogs();

        $this->oauthLogin('gitlab');

        Carbon::setTestNow(Carbon::now()->addDays(2));

        try {
            $this->oauthLogin('gitlab');
        } finally {
            Carbon::setTestNow();
        }

        $this->assertCount(2, $this->sentMail($logs, 'new@example.com'));
    }

    public function test_unconfirmed_provider_email_is_not_synced(): void
    {
        $this->setting('fof-oauth.update_email_from_provider', 1);
        $this->mockGitlab(['id' => 123456, 'username' => 'linked', 'email' => 'new@example.com', 'confirmed_at' => null]);
        $logs = $this->captureLogs();

        $payload = $this->oauthLogin('gitlab');

        $this->assertTrue($payload['loggedIn'] ?? false);
        $user = User::find(3);
        $this->assertSame('linked@machine.local', $user->email);
        $this->assertTrue((bool) $user->is_email_confirmed);
        $this->assertCount(0, $this->sentMail($logs));
    }

    public function test_unchanged_provider_email_leaves_account_untouched(): void
    {
        $this->setting('fof-oauth.update_email_from_provider', 1);
        $this->mockGitlab(['id' => 123456, 'username' => 'linked', 'email' => 'linked@machine.local', 'confirmed_at' => '2021-01-01T00:00:00Z']);

        $payload = $this->oauthLogin('gitlab');

        $this->assertTrue($payload['loggedIn'] ?? false);
        $this->assertSame('linked@machine.local', User::find(3)->email);
    }

    public function test_sync_disabled_leaves_account_email_untouched(): void
    {
        $this->setting('fof-oauth.update_email_from_provider', 0);
        $this->mockGitlab(['id' => 123456, 'username' => 'linked', 'email' => 'new@example.com', 'confirmed_at' => '2021-01-01T00:00:00Z']);
        $logs = $this->captureLogs();

        $payload = $this->oauthLogin('gitlab');

        $this->assertTrue($payload['loggedIn'] ?? false);
        $this->assertSame('linked@machine.local', User::find(3)->email);
        $this->assertCount(0, $this->sentMail($logs));
    }

    public function test_provider_email_belonging_to_another_account_is_skipped_and_login_succeeds(): void
    {
        $this->setting('fof-oauth.update_email_from_provider', 1);
        $this->mockGitlab(['id' => 123456, 'username' => 'linked', 'email' => 'other@machine.local', 'confirmed_at' => '2021-01-01T00:00:00Z']);
        $logs = $this->captureLogs();

        $payload = $this->oauthLogin('gitlab');

        $this->assertTrue($payload['loggedIn'] ?? false);
        $this->assertSame('linked@machine.local', User::find(3)->email);
        $this->assertSame('other@machine.local', User::find(4)->email);
        $this->assertCount(0, $this->sentMail($logs));
    }

    // GitHub: only the primary verified address from GET /user/emails may be synced.

    public function test_github_public_email_without_verified_primary_is_not_synced(): void
    {
        $this->enableGithub();
        $this->mockGithub(
            ['id' => 654321, 'login' => 'linked', 'email' => 'victim@example.com'],
            [['email' => 'victim@example.com', 'primary' => true, 'verified' => false]]
        );
        $logs = $this->captureLogs();

        $payload = $this->oauthLogin('github');

        $this->assertTrue($payload['loggedIn'] ?? false);
        $this->assertSame('linked@machine.local', User::find(3)->email);
        $this->assertCount(0, $this->sentMail($logs));
    }

    public function test_github_verified_primary_is_requested_rather_than_public_email(): void
    {
        $this->enableGithub();
        $this->mockGithub(
            ['id' => 654321, 'login' => 'linked', 'email' => 'public@example.com'],
            [
                ['email' => 'public@example.com', 'primary' => false, 'verified' => false],
                ['email' => 'primary@example.com', 'primary' => true, 'verified' => true],
            ]
        );

        $logs = $this->captureLogs();

        $this->oauthLogin('github');

        $this->assertSame('linked@machine.local', User::find(3)->email);
        $this->assertCount(1, $this->sentMail($logs, 'primary@example.com'));
        $this->assertCount(0, $this->sentMail($logs, 'public@example.com'));
    }

    private function enableGithub(): void
    {
        $this->setting('fof-oauth.update_email_from_provider', 1);
        $this->setting('fof-oauth.github', 1);
        $this->setting('fof-oauth.github.client_id', 'test');
        $this->setting('fof-oauth.github.client_secret', 'test');
        $this->prepareDatabase([
            'login_providers' => [
                ['id' => 2, 'user_id' => 3, 'provider' => 'github', 'identifier' => '654321'],
            ],
        ]);
    }
}
