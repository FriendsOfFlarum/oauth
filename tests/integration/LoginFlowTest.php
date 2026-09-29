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
use Flarum\User\LoginProvider;
use Flarum\User\RegistrationToken;

/**
 * Covers the OAuth route and the login / registration outcomes of a completed round trip.
 */
class LoginFlowTest extends TestCase
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
                    'id'                 => 3, 'username' => 'existing',
                    'is_email_confirmed' => 1, 'email' => 'existing@machine.local',
                    'joined_at'          => '2021-01-01 00:00:00',
                ],
            ],
            'login_providers' => [
                [
                    'id'            => 1, 'user_id' => 3, 'provider' => 'gitlab', 'identifier' => '12345',
                    'created_at'    => '2021-01-01 00:00:00', 'last_login_at' => '2021-01-01 00:00:00',
                ],
            ],
        ]);

        $this->setting('fof-oauth.gitlab', 1);
        $this->setting('fof-oauth.gitlab.client_id', 'test');
        $this->setting('fof-oauth.gitlab.client_secret', 'test');
        $this->setting('fof-oauth.update_email_from_provider', 0);
    }

    public function test_linked_identity_receives_remember_cookie(): void
    {
        $this->mockGitlab(['id' => 12345, 'username' => 'existing', 'email' => 'existing@machine.local', 'confirmed_at' => '2021-01-01T00:00:00Z']);

        $response = $this->oauthCallback('gitlab');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('"loggedIn":true', $response->getBody()->getContents());
        $this->assertArrayHasKey('flarum_remember', $this->cookiesFrom($response));
    }

    public function test_last_login_at_is_updated_on_login(): void
    {
        $this->mockGitlab(['id' => 12345, 'username' => 'existing', 'email' => 'existing@machine.local', 'confirmed_at' => '2021-01-01T00:00:00Z']);

        $this->oauthLogin('gitlab');

        $this->assertTrue(LoginProvider::find(1)->last_login_at->greaterThan(Carbon::parse('2021-01-01 00:00:00')));
    }

    public function test_new_identity_registration_token_is_persisted(): void
    {
        $this->mockGitlab(['id' => 22222, 'username' => 'newcomer', 'email' => 'newcomer@example.com', 'confirmed_at' => '2021-01-01T00:00:00Z']);

        $payload = $this->oauthLogin('gitlab');

        $token = RegistrationToken::find($payload['token'] ?? null);
        $this->assertNotNull($token);
        $this->assertSame('gitlab', $token->provider);
        $this->assertSame('22222', (string) $token->identifier);
    }

    public function test_disabled_provider_returns_404(): void
    {
        $response = $this->send($this->request('GET', '/auth/github'));

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function test_unknown_provider_returns_404(): void
    {
        $response = $this->send($this->request('GET', '/auth/doesnotexist'));

        $this->assertEquals(404, $response->getStatusCode());
    }
}
