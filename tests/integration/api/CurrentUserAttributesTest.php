<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Tests\integration\api;

use Flarum\Extend;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use FoF\Extend\Controllers\AbstractOAuthController;
use Illuminate\Contracts\Cache\Store as Cache;

class CurrentUserAttributesTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extend(
            (new Extend\Csrf())->exemptRoute('login')
        );

        $this->extension('fof-oauth');

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
                [
                    'id'                 => 3, 'username' => 'oauth_user', 'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim',
                    'is_email_confirmed' => 1, 'email' => 'oauth_user@example.com',
                    'joined_at'          => '2021-01-01 00:00:00',
                ],
            ],
            'login_providers' => [
                [
                    'id'            => 1,
                    'user_id'       => 3,
                    'provider'      => 'gitlab',
                    'identifier'    => '123456',
                    'last_login_at' => '2023-01-01 00:00:00',
                ],
                [
                    'id'            => 2,
                    'user_id'       => 3,
                    'provider'      => 'github',
                    'identifier'    => '654321',
                    'last_login_at' => '2023-01-02 00:00:00',
                ],
            ],
        ]);
    }

    /**
     * @test
     */
    public function it_includes_login_provider_in_current_user_attributes()
    {
        // Log in as the user with OAuth providers
        $response = $this->send(
            $this->request('POST', '/login', [
                'json' => [
                    'identification' => 'oauth_user',
                    'password'       => 'too-obscure',
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        // Get the current user data
        $response = $this->send(
            $this->request('GET', '/api/users/3', ['cookiesFrom' => $response])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);

        // Check that the loginProvider attribute is present and has the most recent provider
        $this->assertArrayHasKey('loginProvider', $body['data']['attributes']);
        $this->assertEquals('github', $body['data']['attributes']['loginProvider']);
    }

    /**
     * @test
     */
    public function it_returns_null_for_users_without_login_providers()
    {
        // Log in as a normal user without OAuth providers
        $response = $this->send(
            $this->request('POST', '/login', [
                'json' => [
                    'identification' => 'normal',
                    'password'       => 'too-obscure',
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        // Get the current user data
        $response = $this->send(
            $this->request('GET', '/api/users/2', ['cookiesFrom' => $response])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);

        // Check that the loginProvider attribute is present but null
        $this->assertArrayHasKey('loginProvider', $body['data']['attributes']);
        $this->assertNull($body['data']['attributes']['loginProvider']);
    }

    /**
     * @test
     */
    public function it_uses_cached_provider_when_available()
    {
        // Log in as the user with OAuth providers
        $response = $this->send(
            $this->request('POST', '/login', [
                'json' => [
                    'identification' => 'oauth_user',
                    'password'       => 'too-obscure',
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        // Get the session ID from cookies
        $cookies = [];
        foreach ($response->getHeaders()['Set-Cookie'] as $cookie) {
            if (strpos($cookie, 'flarum_session=') === 0) {
                preg_match('/flarum_session=([^;]+)/', $cookie, $matches);
                $sessionId = $matches[1];
                break;
            }
        }

        // Manually set a cached value
        $cache = $this->app()->getContainer()->make(Cache::class);
        $cache->forever(AbstractOAuthController::SESSION_OAUTH2PROVIDER.'_'.$sessionId, 'discord');

        // Get the current user data
        $response = $this->send(
            $this->request('GET', '/api/users/3', ['cookiesFrom' => $response])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);

        // Check that the loginProvider attribute uses the cached value
        $this->assertArrayHasKey('loginProvider', $body['data']['attributes']);
        $this->assertEquals('discord', $body['data']['attributes']['loginProvider']);
    }
}
