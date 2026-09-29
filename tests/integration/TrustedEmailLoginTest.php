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
use Flarum\User\RegistrationToken;

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

    /**
     * @dataProvider debugModes
     */
    public function test_suggested_email_matching_an_account_asks_the_user_to_log_in_and_link(bool $debug): void
    {
        $this->config('debug', $debug);
        $this->mockGitlab(['id' => 999, 'username' => 'newcomer', 'email' => 'existing@machine.local', 'confirmed_at' => null]);

        $payload = $this->oauthLogin('gitlab');

        $this->assertSame([
            'fofOAuth' => [
                'unverifiedEmailInUse' => true,
                'provider'             => 'gitlab',
                'email'                => 'existing@machine.local',
            ],
        ], $payload);
        $this->assertSame(0, RegistrationToken::query()->count());
        $this->assertFalse(LoginProvider::where('provider', 'gitlab')->where('identifier', '999')->exists());
    }

    public function test_provider_email_cannot_inject_markup_into_the_popup_response(): void
    {
        $email = '"</script><script>alert(1)</script>@example.com';
        $this->prepareDatabase([
            'users' => [
                ['id' => 5, 'username' => 'odd', 'is_email_confirmed' => 1, 'email' => $email, 'joined_at' => '2021-01-01 00:00:00'],
            ],
        ]);
        $this->mockGitlab(['id' => 999, 'username' => 'newcomer', 'email' => $email, 'confirmed_at' => null]);

        $body = $this->oauthCallback('gitlab')->getBody()->getContents();

        $this->assertEquals(1, preg_match('/authenticationComplete\((.*)\);<\/script>$/', $body, $matches), $body);
        $this->assertStringNotContainsString('<', $matches[1]);
        $this->assertSame($email, json_decode($matches[1], true)['fofOAuth']['email']);
    }

    public function debugModes(): array
    {
        return ['debug on' => [true], 'debug off' => [false]];
    }

    public function test_suggested_email_not_matching_any_account_starts_registration(): void
    {
        $this->mockGitlab(['id' => 999, 'username' => 'newcomer', 'email' => 'newcomer@example.com', 'confirmed_at' => null]);

        $payload = $this->oauthLogin('gitlab');

        $this->assertArrayNotHasKey('loggedIn', $payload);
        $this->assertArrayHasKey('token', $payload);
        $this->assertSame('newcomer@example.com', $payload['email'] ?? null);
        $this->assertNotContains('email', $payload['provided'] ?? []);
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
