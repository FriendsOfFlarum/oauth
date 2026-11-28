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

use Flarum\Group\Group;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\LoginProvider;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class SsoFilterTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-oauth');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'linked', 'email' => 'linked@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'oauthmod', 'email' => 'oauthmod@machine.local', 'is_email_confirmed' => 1],
            ],
            LoginProvider::class => [
                ['id' => 1, 'user_id' => 3, 'provider' => 'github', 'identifier' => 'gh-123', 'created_at' => '2023-01-01 00:00:00'],
            ],
            'groups' => [
                ['id' => 10, 'name_singular' => 'OAuthMod', 'name_plural' => 'OAuthMods', 'color' => null, 'icon' => null, 'is_hidden' => 0],
            ],
            'group_user' => [
                ['user_id' => 4, 'group_id' => 10],
            ],
            'group_permission' => [
                ['permission' => 'searchUsers', 'group_id' => Group::MEMBER_ID],
                ['permission' => 'moderateUserProviders', 'group_id' => 10],
            ],
        ]);
    }

    private function filteredUserIds(int $actorId, string $key, string|array $value): array
    {
        $response = $this->send(
            $this->request('GET', '/api/users', ['authenticatedAs' => $actorId])
                ->withQueryParams(['filter' => [$key => $value]])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $ids = array_map('intval', array_column(json_decode($response->getBody()->getContents(), true)['data'], 'id'));
        sort($ids);

        return $ids;
    }

    #[Test]
    public function admin_can_filter_by_provider(): void
    {
        $this->assertEquals([3], $this->filteredUserIds(1, 'sso', 'github'));
    }

    #[Test]
    public function user_with_moderate_permission_can_filter_by_provider(): void
    {
        $this->assertEquals([3], $this->filteredUserIds(4, 'sso', 'github'));
    }

    #[Test]
    public function user_with_moderate_permission_can_exclude_a_provider(): void
    {
        $this->assertEquals([1, 2, 4], $this->filteredUserIds(4, '-sso', 'github'));
    }

    #[Test]
    public function filter_is_ignored_without_moderate_permission(): void
    {
        $this->assertEquals([1, 2, 3, 4], $this->filteredUserIds(2, 'sso', 'github'));
    }

    #[Test]
    public function filter_accepts_a_list_of_providers(): void
    {
        $this->assertEquals([3], $this->filteredUserIds(4, 'sso', ['github', 'google']));
    }
}
