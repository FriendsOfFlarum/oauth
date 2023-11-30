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
use Flarum\Testing\integration\TestCase;

class ForumSerializerTest extends TestCase
{
    public function setUp(): void
    {
        $this->extension('fof-oauth');

        $this->extend(
            (new Extend\Csrf())->exemptRoute('login')
        );
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
    }

    /**
     * @test
     */
    public function it_does_not_include_providers_in_forum_attributes_for_logged_in_users()
    {
        $response = $this->send(
            $this->request('GET', '/api', ['authenticatedAs' => 1])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayNotHasKey('fof-oauth', $body['data']['attributes']);
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
