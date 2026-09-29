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

/**
 * Covers `GET /api/linked-accounts`, `GET /api/users/{id}/linked-accounts` and `DELETE /api/linked-accounts/{id}`.
 */
class LinkedAccountsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-oauth');

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
                [
                    'id'                 => 3, 'username' => 'UserWithProviders',
                    'is_email_confirmed' => 1, 'email' => 'withproviders@machine.local',
                    'joined_at'          => '2021-01-01 00:00:00',
                ],
                [
                    'id'                 => 4, 'username' => 'Moderator',
                    'is_email_confirmed' => 1, 'email' => 'mod@machine.local',
                    'joined_at'          => '2021-01-01 00:00:00',
                ],
            ],
            'login_providers' => [
                [
                    'id'            => 1, 'user_id' => 3, 'provider' => 'github', 'identifier' => 'gh-123',
                    'created_at'    => '2023-01-01 00:00:00', 'last_login_at' => '2024-01-01 00:00:00',
                ],
                [
                    'id'            => 2, 'user_id' => 3, 'provider' => 'google', 'identifier' => 'goog-456',
                    'created_at'    => '2023-06-01 00:00:00', 'last_login_at' => '2024-06-01 00:00:00',
                ],
            ],
            'groups' => [
                ['id' => 10, 'name_singular' => 'OAuthMod', 'name_plural' => 'OAuthMods', 'color' => null, 'icon' => null, 'is_hidden' => 0],
            ],
            'group_user' => [
                ['user_id' => 4, 'group_id' => 10],
            ],
            'group_permission' => [
                ['permission' => 'moderateUserProviders', 'group_id' => 10],
            ],
        ]);

        $this->setting('fof-oauth.github', 1);
        $this->setting('fof-oauth.github.client_id', 'test');
        $this->setting('fof-oauth.github.client_secret', 'test');
        $this->setting('fof-oauth.google', 1);
        $this->setting('fof-oauth.google.client_id', 'test');
        $this->setting('fof-oauth.google.client_secret', 'test');
    }

    private function listAs(string $path, ?int $actorId): array
    {
        $request = $this->request('GET', $path);

        $response = $this->send($actorId === null ? $request : $this->requestAsUser($request, $actorId));

        return [$response->getStatusCode(), json_decode($response->getBody()->getContents(), true)];
    }

    private function linked(array $body): array
    {
        return array_values(array_filter($body['data'], function (array $account) {
            return $account['attributes']['linked'] === true;
        }));
    }

    // GET /api/linked-accounts (own accounts)

    public function test_user_can_list_own_linked_accounts(): void
    {
        [$status, $body] = $this->listAs('/api/linked-accounts', 3);

        $this->assertEquals(200, $status);

        $linked = $this->linked($body);
        $this->assertCount(2, $linked);
        $this->assertEqualsCanonicalizing(['github', 'google'], array_column(array_column($linked, 'attributes'), 'name'));
    }

    public function test_enabled_but_unlinked_providers_are_listed_as_not_linked(): void
    {
        $this->setting('fof-oauth.gitlab', 1);

        [$status, $body] = $this->listAs('/api/linked-accounts', 3);

        $this->assertEquals(200, $status);

        $gitlab = array_values(array_filter($body['data'], function (array $account) {
            return $account['attributes']['name'] === 'gitlab';
        }));
        $this->assertCount(1, $gitlab);
        $this->assertFalse($gitlab[0]['attributes']['linked']);
    }

    public function test_orphaned_flag_set_for_links_to_disabled_providers(): void
    {
        $this->prepareDatabase([
            'login_providers' => [
                ['id' => 99, 'user_id' => 3, 'provider' => 'twitter', 'identifier' => 'tw-old', 'created_at' => '2022-01-01 00:00:00'],
            ],
        ]);

        [$status, $body] = $this->listAs('/api/linked-accounts', 3);

        $this->assertEquals(200, $status);

        $twitter = array_values(array_filter($body['data'], function (array $account) {
            return $account['attributes']['name'] === 'twitter';
        }));
        $this->assertCount(1, $twitter);
        $this->assertTrue($twitter[0]['attributes']['orphaned']);
    }

    public function test_guest_cannot_list_linked_accounts(): void
    {
        [$status] = $this->listAs('/api/linked-accounts', null);

        $this->assertEquals(401, $status);
    }

    // GET /api/users/{id}/linked-accounts

    public function test_user_cannot_list_another_users_linked_accounts(): void
    {
        [$status] = $this->listAs('/api/users/3/linked-accounts', 2);

        $this->assertEquals(403, $status);
    }

    public function test_admin_listing_another_user_sees_that_users_accounts(): void
    {
        [$status, $body] = $this->listAs('/api/users/3/linked-accounts', 1);

        $this->assertEquals(200, $status);

        $linked = $this->linked($body);
        $this->assertCount(2, $linked);
        $this->assertEqualsCanonicalizing(['gh-123', 'goog-456'], array_column(array_column($linked, 'attributes'), 'providerIdentifier'));
    }

    public function test_user_with_permission_can_list_another_users_linked_accounts(): void
    {
        [$status, $body] = $this->listAs('/api/users/3/linked-accounts', 4);

        $this->assertEquals(200, $status);
        $this->assertCount(2, $this->linked($body));
    }

    public function test_guest_cannot_list_another_users_linked_accounts(): void
    {
        [$status] = $this->listAs('/api/users/3/linked-accounts', null);

        $this->assertEquals(401, $status);
    }

    // DELETE /api/linked-accounts/{id}

    public function test_user_can_unlink_own_account(): void
    {
        $response = $this->send($this->requestAsUser($this->request('DELETE', '/api/linked-accounts/1'), 3));

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertFalse(LoginProvider::where('id', 1)->exists());
    }

    public function test_user_cannot_unlink_another_users_account(): void
    {
        $response = $this->send($this->requestAsUser($this->request('DELETE', '/api/linked-accounts/1'), 2));

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertTrue(LoginProvider::where('id', 1)->exists());
    }

    public function test_admin_can_unlink_any_account(): void
    {
        $response = $this->send($this->requestAsUser($this->request('DELETE', '/api/linked-accounts/1'), 1));

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertFalse(LoginProvider::where('id', 1)->exists());
    }

    public function test_user_with_permission_can_unlink_another_users_account(): void
    {
        $response = $this->send($this->requestAsUser($this->request('DELETE', '/api/linked-accounts/1'), 4));

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertFalse(LoginProvider::where('id', 1)->exists());
    }

    public function test_guest_cannot_unlink_account(): void
    {
        // Skip CSRF so the request reaches the authorisation check rather than failing earlier with a 400.
        $response = $this->send($this->request('DELETE', '/api/linked-accounts/1')->withAttribute('bypassCsrfToken', true));

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertTrue(LoginProvider::where('id', 1)->exists());
    }

    // No create or update endpoints

    public function test_post_to_linked_accounts_is_rejected(): void
    {
        $response = $this->send($this->requestAsUser($this->request('POST', '/api/linked-accounts', ['json' => [
            'data' => ['type' => 'linked-accounts', 'attributes' => ['provider' => 'github']],
        ]]), 3));

        $this->assertEquals(405, $response->getStatusCode());
        $this->assertCount(2, LoginProvider::where('user_id', 3)->get());
    }

    public function test_patch_to_linked_account_is_rejected(): void
    {
        $response = $this->send($this->requestAsUser($this->request('PATCH', '/api/linked-accounts/1', ['json' => [
            'data' => ['type' => 'linked-accounts', 'id' => '1', 'attributes' => []],
        ]]), 3));

        $this->assertEquals(405, $response->getStatusCode());
        $this->assertTrue(LoginProvider::where('id', 1)->exists());
    }
}
