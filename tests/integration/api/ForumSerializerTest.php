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

class ForumSerializerTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    public function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-oauth');

        $this->extend(
            (new Extend\Csrf())->exemptRoute('login')
        );

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'moderator', 'is_email_confirmed' => true],
            ],
            'group_user' => [
                ['user_id' => 3, 'group_id' => 4],
            ],
            'group_permission' => [
                ['permission' => 'moderateUserProviders', 'group_id' => 4],
            ],
        ]);
    }

    public function authorizedUserProvider()
    {
        return [
            [1],
            [3],
        ];
    }

    /**
     * @test
     */
    public function it_includes_providers_in_forum_attributes_for_guests()
    {
        $response = $this->send(
            $this->request('GET', '/api')
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody(), true);

        $this->assertArrayHasKey('fof-oauth', $body['data']['attributes']);
        $this->assertArrayNotHasKey('fofOauthModerate', $body['data']['attributes']);
    }

    /**
     * @dataProvider authorizedUserProvider
     *
     * @test
     */
    public function it_does_not_include_providers_in_forum_attributes_for_logged_in_users(int $userId)
    {
        $response = $this->send(
            $this->request('GET', '/api', ['authenticatedAs' => $userId])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayNotHasKey('fof-oauth', $body['data']['attributes']);
        $this->assertArrayHasKey('fofOauthModerate', $body['data']['attributes']);
        $this->assertTrue($body['data']['attributes']['fofOauthModerate']);
    }

    /**
     * @test
     */
    public function normal_user_does_not_have_moderate_flag()
    {
        $response = $this->send(
            $this->request('GET', '/api', ['authenticatedAs' => 2])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayNotHasKey('fof-oauth', $body['data']['attributes']);
        $this->assertArrayHasKey('fofOauthModerate', $body['data']['attributes']);
        $this->assertFalse($body['data']['attributes']['fofOauthModerate']);
    }

    /**
     * @test
     */
    public function admin_panel_is_available()
    {
        $login = $this->send(
            $this->request('POST', '/login', [
                'json' => [
                    'identification' => 'admin',
                    'password'       => 'password',
                ],
            ])
        );

        $this->assertEquals(200, $login->getStatusCode());

        $response = $this->send(
            $this->request('GET', '/admin', ['cookiesFrom' => $login])
        );

        $this->assertEquals(200, $response->getStatusCode());
    }
}
