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

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use FoF\OAuth\Controllers\AbstractOAuthController;
use Illuminate\Contracts\Cache\Store as CacheStore;
use PHPUnit\Framework\Attributes\Test;

/**
 * Browsers read "\" as "/" and strip tab/CR/LF in a Location header, so returnTo values like "/\evil.example"
 * must never survive to the OAuth callback redirect: it would carry the registration token off-site.
 */
class ReturnToRedirectTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use PerformsOAuthLogin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-oauth');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
        ]);

        $this->setting('fof-oauth.gitlab', 1);
        $this->setting('fof-oauth.gitlab.client_id', 'test');
        $this->setting('fof-oauth.gitlab.client_secret', 'test');
        $this->setting('fof-oauth.update_email_from_provider', 0);
    }

    #[Test]
    public function registration_token_is_not_sent_to_a_backslash_returnTo(): void
    {
        $this->mockGitlab(['id' => 999, 'username' => 'newcomer', 'email' => 'newcomer@machine.local', 'confirmed_at' => '2021-01-01T00:00:00Z']);

        $location = $this->oauthLogin('gitlab', '/\\evil.example');

        $this->assertStringStartsWith('/?_flarum_auth=', $location);
    }

    #[Test]
    public function registration_token_is_not_sent_to_a_returnTo_hiding_a_double_slash_behind_a_tab(): void
    {
        $this->mockGitlab(['id' => 999, 'username' => 'newcomer', 'email' => 'newcomer@machine.local', 'confirmed_at' => '2021-01-01T00:00:00Z']);

        $location = $this->oauthLogin('gitlab', "/\t/evil.example");

        $this->assertStringStartsWith('/?_flarum_auth=', $location);
    }

    #[Test]
    public function registration_token_is_not_sent_to_an_unsafe_returnTo_cached_before_the_fix(): void
    {
        $this->mockGitlab(['id' => 999, 'username' => 'newcomer', 'email' => 'newcomer@machine.local', 'confirmed_at' => '2021-01-01T00:00:00Z']);

        $init = $this->send($this->request('GET', '/auth/gitlab')->withQueryParams(['returnTo' => '/']));
        $cookies = $this->cookiesFrom($init);
        parse_str((string) parse_url($init->getHeaderLine('Location'), PHP_URL_QUERY), $query);

        // Older releases stored returnTo after a weaker check; such an entry can still be in the cache.
        $this->app()->getContainer()->make(CacheStore::class)
            ->forever(AbstractOAuthController::SESSION_RETURN_TO.'_'.$cookies['flarum_session'], '/\\evil.example');

        $callback = $this->send(
            $this->request('GET', '/auth/gitlab')
                ->withQueryParams(['code' => 'code', 'state' => $query['state']])
                ->withCookieParams($cookies)
        );

        $this->assertStringStartsWith('/?_flarum_auth=', $callback->getHeaderLine('Location'));
    }
}
