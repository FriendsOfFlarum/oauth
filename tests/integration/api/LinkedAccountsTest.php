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

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\LoginProvider;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class LinkedAccountsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-oauth');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                [
                    'id'                 => 3,
                    'username'           => 'UserWithProviders',
                    'is_email_confirmed' => 1,
                    'email'              => 'withproviders@machine.local',
                    'password'           => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim',
                    'joined_at'          => '2021-01-01 00:00:00',
                ],
            ],
            LoginProvider::class => [
                [
                    'id'            => 1,
                    'user_id'       => 3,
                    'provider'      => 'github',
                    'identifier'    => 'gh-123',
                    'created_at'    => '2023-01-01 00:00:00',
                    'last_login_at' => '2024-01-01 00:00:00',
                ],
                [
                    'id'            => 2,
                    'user_id'       => 3,
                    'provider'      => 'google',
                    'identifier'    => 'goog-456',
                    'created_at'    => '2023-06-01 00:00:00',
                    'last_login_at' => '2024-06-01 00:00:00',
                ],
            ],
            'group_user' => [
                ['user_id' => 3, 'group_id' => 1], // admin
            ],
        ]);

        $this->setting('fof-oauth.github.client_id', 'test');
        $this->setting('fof-oauth.github.client_secret', 'test');
        $this->setting('fof-oauth.github', 1);
        $this->setting('fof-oauth.google.client_id', 'test');
        $this->setting('fof-oauth.google.client_secret', 'test');
        $this->setting('fof-oauth.google', 1);
    }

    // -------------------------------------------------------------------------
    // GET /linked-accounts?filter[userId]={id}
    // -------------------------------------------------------------------------

    #[Test]
    public function user_can_list_own_linked_accounts(): void
    {
        $response = $this->send(
            $this->requestAsUser(
                $this->request('GET', '/api/linked-accounts', ['json' => []]),
                3
            )->withQueryParams(['filter' => ['userId' => 3]])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertArrayHasKey('data', $body);
    }

    #[Test]
    public function linked_accounts_include_linked_flag(): void
    {
        $response = $this->send(
            $this->requestAsUser(
                $this->request('GET', '/api/linked-accounts'),
                3
            )->withQueryParams(['filter' => ['userId' => 3]])
        );

        $body = json_decode($response->getBody()->getContents(), true);
        $accounts = $body['data'];

        $linked = array_filter($accounts, fn ($a) => $a['attributes']['linked'] === true);
        $this->assertCount(2, $linked);
    }

    #[Test]
    public function orphaned_flag_set_for_removed_providers(): void
    {
        // twitter was linked to a user but the provider is now removed
        $this->prepareDatabase([
            LoginProvider::class => [
                [
                    'id'            => 99,
                    'user_id'       => 3,
                    'provider'      => 'twitter',
                    'identifier'    => 'tw-old',
                    'created_at'    => '2022-01-01 00:00:00',
                    'last_login_at' => '2022-01-01 00:00:00',
                ],
            ],
        ]);

        $response = $this->send(
            $this->requestAsUser(
                $this->request('GET', '/api/linked-accounts'),
                3
            )->withQueryParams(['filter' => ['userId' => 3]])
        );

        $body = json_decode($response->getBody()->getContents(), true);
        $accounts = $body['data'];

        $twitterAccount = array_values(array_filter($accounts, fn ($a) => $a['attributes']['name'] === 'twitter'))[0] ?? null;
        $this->assertNotNull($twitterAccount);
        $this->assertTrue($twitterAccount['attributes']['orphaned']);
    }

    #[Test]
    public function guest_cannot_list_linked_accounts(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/linked-accounts')
                ->withQueryParams(['filter' => ['userId' => 3]])
        );

        $this->assertEquals(401, $response->getStatusCode());
    }

    #[Test]
    public function user_cannot_list_another_users_linked_accounts(): void
    {
        $response = $this->send(
            $this->requestAsUser(
                $this->request('GET', '/api/linked-accounts'),
                2  // normal user trying to see user 3's accounts
            )->withQueryParams(['filter' => ['userId' => 3]])
        );

        // 404 rather than 403: deliberately does not reveal that the resource exists.
        $this->assertEquals(404, $response->getStatusCode());
    }

    // -------------------------------------------------------------------------
    // DELETE /linked-accounts/{id}
    // -------------------------------------------------------------------------

    #[Test]
    public function user_can_unlink_own_account(): void
    {
        $response = $this->send(
            $this->requestAsUser(
                $this->request('DELETE', '/api/linked-accounts/1'),
                3
            )
        );

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertFalse(LoginProvider::where('id', 1)->exists());
    }

    #[Test]
    public function user_cannot_unlink_another_users_account(): void
    {
        $response = $this->send(
            $this->requestAsUser(
                $this->request('DELETE', '/api/linked-accounts/1'),
                2  // normal user, not the owner
            )
        );

        $this->assertContains($response->getStatusCode(), [403, 404]);
        $this->assertTrue(LoginProvider::where('id', 1)->exists());
    }

    #[Test]
    public function admin_viewing_another_users_accounts_sees_that_users_accounts_not_their_own(): void
    {
        // Admin (user 1) has NO login providers.
        // User 3 has github + google linked.
        // When admin queries filter[userId]=3 they must get user 3's accounts, not their own.
        $response = $this->send(
            $this->requestAsUser(
                $this->request('GET', '/api/linked-accounts'),
                1  // admin, no providers
            )->withQueryParams(['filter' => ['userId' => 3]])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $accounts = $body['data'];

        // Every returned record must belong to user 3, not user 1.
        foreach ($accounts as $account) {
            $this->assertEquals(
                '3',
                $account['attributes']['userId'],
                "Account {$account['attributes']['name']} has userId {$account['attributes']['userId']}, expected 3"
            );
        }

        // The two linked providers for user 3 must be present and flagged as linked.
        $linked = array_filter($accounts, fn ($a) => $a['attributes']['linked'] === true);
        $this->assertCount(2, $linked, 'Expected exactly 2 linked accounts for user 3');

        $names = array_column(array_column($linked, 'attributes'), 'name');
        $this->assertContains('github', $names);
        $this->assertContains('google', $names);
    }

    #[Test]
    public function admin_can_unlink_any_account(): void
    {
        $response = $this->send(
            $this->requestAsUser(
                $this->request('DELETE', '/api/linked-accounts/1'),
                1  // admin user (id=1)
            )
        );

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertFalse(LoginProvider::where('id', 1)->exists());
    }
}
