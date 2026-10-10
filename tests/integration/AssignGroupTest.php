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
use Flarum\User\User;
use FoF\OAuth\Listeners\AssignToUser;
use PHPUnit\Framework\Attributes\Test;

class AssignGroupTest extends TestCase
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
        $this->setting('fof-oauth.gitlab.group', 4);
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
}
