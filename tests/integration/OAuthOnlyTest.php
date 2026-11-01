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
use Flarum\User\PasswordToken;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;

class OAuthOnlyTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-oauth');

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
            ],
            'password_tokens' => [
                ['token' => 'member-reset-token', 'user_id' => 2, 'created_at' => Carbon::now()],
                ['token' => 'admin-reset-token', 'user_id' => 1, 'created_at' => Carbon::now()],
            ],
            'registration_tokens' => [
                [
                    'token'           => 'gitlab-registration-token',
                    'provider'        => 'gitlab',
                    'identifier'      => '98765',
                    'user_attributes' => json_encode(['email' => 'new@machine.local']),
                    'payload'         => json_encode([]),
                    'created_at'      => Carbon::now(),
                ],
            ],
        ]);

        $this->setting('mail_driver', 'log');
        $this->setting('fof-oauth.gitlab.client_id', 'test');
        $this->setting('fof-oauth.gitlab.client_secret', 'test');
        $this->setting('fof-oauth.gitlab', 1);
        $this->setting('fof-oauth.oauth_only', 1);
    }

    // -------------------------------------------------------------------------
    // Password login
    // -------------------------------------------------------------------------

    #[Test]
    public function member_cannot_log_in_with_password(): void
    {
        $response = $this->createToken('normal', 'too-obscure');

        $this->assertEquals(401, $response->getStatusCode());
    }

    #[Test]
    public function admin_can_still_log_in_with_password(): void
    {
        $response = $this->createToken('admin', 'password');

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function member_can_log_in_with_password_when_setting_is_off(): void
    {
        $this->setting('fof-oauth.oauth_only', 0);

        $response = $this->createToken('normal', 'too-obscure');

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function setting_has_no_effect_without_an_enabled_provider(): void
    {
        $this->setting('fof-oauth.gitlab', 0);

        $response = $this->createToken('normal', 'too-obscure');

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
    }

    // -------------------------------------------------------------------------
    // Registration
    // -------------------------------------------------------------------------

    #[Test]
    public function guest_cannot_register_with_password(): void
    {
        $response = $this->register([
            'username' => 'newuser',
            'email'    => 'new@machine.local',
            'password' => 'too-obscure',
        ]);

        $this->assertEquals(403, $response->getStatusCode(), (string) $response->getBody());
        $this->assertNull(User::query()->where('username', 'newuser')->first());
    }

    #[Test]
    public function guest_can_register_with_registration_token(): void
    {
        $response = $this->register([
            'username' => 'newuser',
            'token'    => 'gitlab-registration-token',
        ]);

        $this->assertEquals(201, $response->getStatusCode(), (string) $response->getBody());
        $this->assertNotNull(User::query()->where('username', 'newuser')->first());
    }

    #[Test]
    public function admin_can_create_user_with_password(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/users', [
                'authenticatedAs' => 1,
                'json'            => ['data' => ['type' => 'users', 'attributes' => [
                    'username' => 'newuser',
                    'email'    => 'new@machine.local',
                    'password' => 'too-obscure',
                ]]],
            ])
        );

        $this->assertEquals(201, $response->getStatusCode(), (string) $response->getBody());
    }

    // -------------------------------------------------------------------------
    // Password reset
    // -------------------------------------------------------------------------

    #[Test]
    public function forgot_password_for_member_responds_as_usual_but_issues_no_token(): void
    {
        $response = $this->forgot('normal@machine.local');

        $this->assertEquals(204, $response->getStatusCode());
        // Core's job would have replaced the fixture token with a new one.
        $this->assertEquals(['member-reset-token'], PasswordToken::query()->where('user_id', 2)->pluck('token')->all());
    }

    #[Test]
    public function forgot_password_for_admin_issues_token(): void
    {
        $response = $this->forgot('admin@machine.local');

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertNotEquals(['admin-reset-token'], PasswordToken::query()->where('user_id', 1)->pluck('token')->all());
    }

    #[Test]
    public function member_cannot_open_reset_page_with_previously_issued_token(): void
    {
        $response = $this->send($this->request('GET', '/reset/member-reset-token'));

        $this->assertEquals(403, $response->getStatusCode());
    }

    #[Test]
    public function member_cannot_save_password_with_previously_issued_token(): void
    {
        $response = $this->send(
            $this->request('POST', '/reset')
                ->withParsedBody([
                    'passwordToken'         => 'member-reset-token',
                    'password'              => 'new-password-123',
                    'password_confirmation' => 'new-password-123',
                ])
                ->withAttribute('bypassCsrfToken', true)
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertNotNull(PasswordToken::query()->find('member-reset-token'));
    }

    #[Test]
    public function admin_can_open_reset_page(): void
    {
        $response = $this->send($this->request('GET', '/reset/admin-reset-token'));

        $this->assertEquals(200, $response->getStatusCode());
    }

    // -------------------------------------------------------------------------
    // Forum attribute
    // -------------------------------------------------------------------------

    #[Test]
    public function forum_attribute_reflects_mode(): void
    {
        $this->assertTrue($this->forumAttribute('fofOauthOnly'));
    }

    #[Test]
    public function forum_attribute_is_false_without_an_enabled_provider(): void
    {
        $this->setting('fof-oauth.gitlab', 0);

        $this->assertFalse($this->forumAttribute('fofOauthOnly'));
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    protected function createToken(string $identification, string $password): ResponseInterface
    {
        return $this->send(
            $this->request('POST', '/api/token', [
                'json' => compact('identification', 'password'),
            ])->withAttribute('bypassCsrfToken', true)
        );
    }

    protected function register(array $attributes): ResponseInterface
    {
        return $this->send(
            $this->request('POST', '/api/users', [
                'json' => ['data' => ['type' => 'users', 'attributes' => $attributes]],
            ])->withAttribute('bypassCsrfToken', true)
        );
    }

    protected function forgot(string $email): ResponseInterface
    {
        return $this->send(
            $this->request('POST', '/api/forgot', [
                'json' => compact('email'),
            ])->withAttribute('bypassCsrfToken', true)
        );
    }

    protected function forumAttribute(string $key): mixed
    {
        $response = $this->send($this->request('GET', '/api'));

        return json_decode((string) $response->getBody(), true)['data']['attributes'][$key] ?? null;
    }
}
