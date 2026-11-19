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
use Flarum\User\Event\RegisteringFromProvider;
use Flarum\User\LoginProvider;
use Flarum\User\User;
use FoF\OAuth\Listeners\AssignToUser;
use PHPUnit\Framework\Attributes\Test;

class AssignGroupTest extends TestCase
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
                ['id' => 3, 'username' => 'existing', 'is_email_confirmed' => 1, 'email' => 'existing@machine.local', 'joined_at' => '2021-01-01 00:00:00'],
            ],
            'group_user' => [
                ['user_id' => 2, 'group_id' => 4],
            ],
            'registration_tokens' => [
                [
                    'token'           => 'gitlab-registration-token',
                    'provider'        => 'gitlab',
                    'identifier'      => '98765',
                    'user_attributes' => json_encode(['email' => 'provideruser@machine.local']),
                    'payload'         => json_encode([]),
                    'created_at'      => Carbon::now(),
                ],
            ],
        ]);

        $this->setting('mail_driver', 'log');
        $this->setting('fof-oauth.gitlab', 1);
        $this->setting('fof-oauth.gitlab.client_id', 'test');
        $this->setting('fof-oauth.gitlab.client_secret', 'test');
        $this->setting('fof-oauth.gitlab.group', 4);
        $this->setting('fof-oauth.update_email_from_provider', 0);
    }

    #[Test]
    public function provider_sign_up_is_assigned_the_providers_group(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/users', [
                'json' => ['data' => ['type' => 'users', 'attributes' => [
                    'username' => 'provideruser',
                    'token'    => 'gitlab-registration-token',
                ]]],
            ])->withAttribute('bypassCsrfToken', true)
        );

        $this->assertEquals(201, $response->getStatusCode(), (string) $response->getBody());

        $user = User::query()->where('username', 'provideruser')->firstOrFail();

        $this->assertEquals([4], $user->groups()->pluck('id')->all());
    }

    /**
     * Another extension may add the same group during registration, e.g. fof/doorman's doorkey group.
     */
    #[Test]
    public function assigning_a_group_the_user_already_has_does_not_fail(): void
    {
        $this->app();

        $user = User::query()->findOrFail(2);

        $this->app()->getContainer()->make(AssignToUser::class)
            ->handle(new RegisteringFromProvider($user, 'gitlab', []));

        $user->save();

        $this->assertEquals([4], $user->groups()->pluck('id')->all());
    }

    #[Test]
    public function linking_the_provider_from_settings_assigns_the_providers_group(): void
    {
        $this->mockGitlab(['id' => 111, 'username' => 'existing', 'email' => 'existing@machine.local', 'confirmed_at' => '2021-01-01T00:00:00Z']);

        $response = $this->oauthLinkCallback('gitlab', 3, 3);

        $this->assertEquals(302, $response->getStatusCode(), (string) $response->getBody());
        $this->assertTrue(LoginProvider::where('user_id', 3)->where('provider', 'gitlab')->exists());
        $this->assertEquals([4], User::query()->findOrFail(3)->groups()->pluck('id')->all());
    }

    #[Test]
    public function logging_in_with_a_provider_whose_verified_email_matches_assigns_the_providers_group(): void
    {
        $this->mockGitlab(['id' => 111, 'username' => 'newcomer', 'email' => 'existing@machine.local', 'confirmed_at' => '2021-01-01T00:00:00Z']);

        $this->assertStringContainsString('_flarum_linked=gitlab', $this->oauthLogin('gitlab'));
        $this->assertEquals([4], User::query()->findOrFail(3)->groups()->pluck('id')->all());
    }

    #[Test]
    public function linking_the_provider_when_the_user_already_has_its_group_does_not_fail(): void
    {
        $this->mockGitlab(['id' => 111, 'username' => 'normal', 'email' => 'normal@machine.local', 'confirmed_at' => '2021-01-01T00:00:00Z']);

        $response = $this->oauthLinkCallback('gitlab', 2, 2);

        $this->assertEquals(302, $response->getStatusCode(), (string) $response->getBody());
        $this->assertEquals([4], User::query()->findOrFail(2)->groups()->pluck('id')->all());
    }

    #[Test]
    public function linking_a_provider_without_a_group_leaves_the_users_groups_alone(): void
    {
        $this->setting('fof-oauth.gitlab.group', '');
        $this->mockGitlab(['id' => 111, 'username' => 'existing', 'email' => 'existing@machine.local', 'confirmed_at' => '2021-01-01T00:00:00Z']);

        $response = $this->oauthLinkCallback('gitlab', 3, 3);

        $this->assertEquals(302, $response->getStatusCode(), (string) $response->getBody());
        $this->assertTrue(LoginProvider::where('user_id', 3)->where('provider', 'gitlab')->exists());
        $this->assertEquals([], User::query()->findOrFail(3)->groups()->pluck('id')->all());
    }

    /**
     * An admin may have given the group by hand, so unlinking the provider does not take it away.
     */
    #[Test]
    public function unlinking_the_provider_keeps_the_group(): void
    {
        $this->mockGitlab(['id' => 111, 'username' => 'existing', 'email' => 'existing@machine.local', 'confirmed_at' => '2021-01-01T00:00:00Z']);
        $this->oauthLinkCallback('gitlab', 3, 3);

        $linkId = LoginProvider::where('user_id', 3)->where('provider', 'gitlab')->value('id');

        $response = $this->send(
            $this->request('DELETE', "/api/linked-accounts/$linkId", ['authenticatedAs' => 3])
        );

        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());
        $this->assertFalse(LoginProvider::where('user_id', 3)->where('provider', 'gitlab')->exists());
        $this->assertEquals([4], User::query()->findOrFail(3)->groups()->pluck('id')->all());
    }
}
