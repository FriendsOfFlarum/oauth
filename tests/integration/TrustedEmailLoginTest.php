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
use Flarum\User\LoginProvider;
use Flarum\User\RegistrationToken;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Covers how a provider's trusted vs suggested email is handled for an identity not yet linked to any account.
 */
class TrustedEmailLoginTest extends TestCase
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
                ['id' => 4, 'username' => 'existing', 'is_email_confirmed' => 1, 'email' => 'existing@machine.local', 'joined_at' => '2021-01-01 00:00:00'],
            ],
        ]);

        $this->setting('fof-oauth.gitlab', 1);
        $this->setting('fof-oauth.gitlab.client_id', 'test');
        $this->setting('fof-oauth.gitlab.client_secret', 'test');
        $this->setting('fof-oauth.update_email_from_provider', 0);
    }

    private function registrationToken(string $location): ?RegistrationToken
    {
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        return isset($query['_flarum_auth']) ? RegistrationToken::find($query['_flarum_auth']) : null;
    }

    #[Test]
    public function trusted_email_matching_an_account_logs_in_and_links_it(): void
    {
        $this->mockGitlab(['id' => 999, 'username' => 'newcomer', 'email' => 'existing@machine.local', 'confirmed_at' => '2021-01-01T00:00:00Z']);

        $location = $this->oauthLogin('gitlab');

        $this->assertStringNotContainsString('_flarum_auth', $location);
        $this->assertStringContainsString('_flarum_linked=gitlab', $location);
        $this->assertSame(4, LoginProvider::where('provider', 'gitlab')->where('identifier', '999')->value('user_id'));
    }

    #[Test]
    #[DataProvider('debugModes')]
    public function suggested_email_matching_an_account_redirects_asking_the_user_to_log_in_and_link(bool $debug): void
    {
        $this->config('debug', $debug);
        $this->mockGitlab(['id' => 999, 'username' => 'newcomer', 'email' => 'existing@machine.local', 'confirmed_at' => null]);

        $response = $this->oauthCallback('gitlab', '/d/1-discussion?page=2');
        $location = $response->getHeaderLine('Location');

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertSame('/d/1-discussion?page=2&_fof_oauth_unverified=gitlab', $location);
        $this->assertStringNotContainsString('existing', urldecode($location));
        $this->assertArrayNotHasKey('flarum_remember', $this->cookiesFrom($response));
        $this->assertSame(0, RegistrationToken::query()->count());
        $this->assertFalse(LoginProvider::where('provider', 'gitlab')->where('identifier', '999')->exists());
    }

    public static function debugModes(): array
    {
        return ['debug on' => [true], 'debug off' => [false]];
    }

    #[Test]
    public function suggested_email_not_matching_any_account_starts_registration(): void
    {
        $this->mockGitlab(['id' => 999, 'username' => 'newcomer', 'email' => 'newcomer@example.com', 'confirmed_at' => null]);

        $this->assertNotNull($this->registrationToken($this->oauthLogin('gitlab')));
    }

    #[Test]
    public function trusted_email_not_matching_any_account_starts_registration(): void
    {
        $this->mockGitlab(['id' => 999, 'username' => 'newcomer', 'email' => 'newcomer@example.com', 'confirmed_at' => '2021-01-01T00:00:00Z']);

        $token = $this->registrationToken($this->oauthLogin('gitlab'));

        $this->assertNotNull($token);
        $this->assertSame('newcomer@example.com', $token->user_attributes['email'] ?? null);
    }
}
