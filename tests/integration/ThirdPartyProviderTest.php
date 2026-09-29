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
use Flarum\User\User;
use FoF\OAuth\Extend\RegisterProvider;
use FoF\OAuth\Listeners\UpdateEmailFromProvider;
use FoF\OAuth\Tests\fixtures\ThirdParty\ThirdPartyProvider;
use FoF\OAuth\Tests\fixtures\ThirdParty\ThrowingThirdPartyProvider;
use FoF\OAuth\Tests\fixtures\ThirdParty\VerifiedThirdPartyProvider;
use Illuminate\Contracts\Cache\Repository as Cache;
use Monolog\Handler\TestHandler;

/**
 * Pins the behaviour other extensions rely on when they register their own provider.
 */
class ThirdPartyProviderTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use PerformsOAuthLogin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-oauth');
        $this->extend(
            new RegisterProvider(ThirdPartyProvider::class),
            new RegisterProvider(VerifiedThirdPartyProvider::class),
            new RegisterProvider(ThrowingThirdPartyProvider::class)
        );

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
                [
                    'id'                 => 3, 'username' => 'linked',
                    'is_email_confirmed' => 1, 'email' => 'linked@machine.local',
                    'joined_at'          => '2021-01-01 00:00:00',
                ],
                [
                    'id'                 => 4, 'username' => 'existing',
                    'is_email_confirmed' => 1, 'email' => 'existing@machine.local',
                    'joined_at'          => '2021-01-01 00:00:00',
                ],
            ],
            'login_providers' => [
                ['id' => 1, 'user_id' => 3, 'provider' => 'thirdparty', 'identifier' => 'tp-1'],
                ['id' => 2, 'user_id' => 3, 'provider' => 'verifiedthirdparty', 'identifier' => 'tp-1'],
                ['id' => 3, 'user_id' => 3, 'provider' => 'throwingthirdparty', 'identifier' => 'tp-1'],
            ],
        ]);

        $this->setting('fof-oauth.thirdparty', 1);
        $this->setting('fof-oauth.verifiedthirdparty', 1);
        $this->setting('fof-oauth.throwingthirdparty', 1);
        $this->setting('fof-oauth.update_email_from_provider', 1);
        $this->setting('mail_driver', 'log');
    }

    protected function tearDown(): void
    {
        ThirdPartyProvider::$resourceOwner = [];

        parent::tearDown();
    }

    /**
     * Boot the app, clear any rate-limited warning left by an earlier run, and start capturing logs.
     */
    private function prepareLogs(): TestHandler
    {
        /** @var Cache $cache */
        $cache = $this->app()->getContainer()->make(Cache::class);

        foreach (['thirdparty', 'verifiedthirdparty', 'throwingthirdparty'] as $name) {
            $cache->forget("fof-oauth.unverified-provider-warned.$name");
        }

        return $this->captureLogs();
    }

    private function warnings(TestHandler $logs, string $needle): array
    {
        return array_values(array_filter($logs->getRecords(), function ($record) use ($needle) {
            return strpos($record['message'], $needle) !== false;
        }));
    }

    // Login and registration are unaffected by the email trust contract

    public function test_linked_identity_logs_in(): void
    {
        ThirdPartyProvider::$resourceOwner = ['id' => 'tp-1', 'email' => 'linked@machine.local'];

        $payload = $this->oauthLogin('thirdparty');

        $this->assertTrue($payload['loggedIn'] ?? false);
    }

    public function test_trusted_email_matching_an_account_logs_in_and_links_it(): void
    {
        ThirdPartyProvider::$resourceOwner = ['id' => 'tp-2', 'email' => 'existing@machine.local'];

        $payload = $this->oauthLogin('thirdparty');

        $this->assertTrue($payload['loggedIn'] ?? false);
        $this->assertSame(4, LoginProvider::where('provider', 'thirdparty')->where('identifier', 'tp-2')->value('user_id'));
    }

    // Email sync

    public function test_provider_without_verified_email_is_not_synced_and_logs_a_warning(): void
    {
        $logs = $this->prepareLogs();
        ThirdPartyProvider::$resourceOwner = ['id' => 'tp-1', 'email' => 'new@example.com'];

        $payload = $this->oauthLogin('thirdparty');

        $this->assertTrue($payload['loggedIn'] ?? false);
        $this->assertSame('linked@machine.local', User::find(3)->email);

        $warnings = $this->warnings($logs, 'does not implement getProviderVerifiedEmail()');
        $this->assertCount(1, $warnings);
        $this->assertSame('WARNING', $warnings[0]['level_name']);
        $this->assertStringContainsString('"thirdparty"', $warnings[0]['message']);
        $this->assertStringContainsString(ThirdPartyProvider::class, $warnings[0]['message']);
        $this->assertStringContainsString(UpdateEmailFromProvider::DOCS_URL, $warnings[0]['message']);
    }

    public function test_missing_implementation_warning_is_not_repeated(): void
    {
        $logs = $this->prepareLogs();
        ThirdPartyProvider::$resourceOwner = ['id' => 'tp-1', 'email' => 'new@example.com'];

        $this->oauthLogin('thirdparty');
        $this->oauthLogin('thirdparty');

        $this->assertCount(1, $this->warnings($logs, 'does not implement getProviderVerifiedEmail()'));
    }

    public function test_provider_with_verified_email_requests_confirmation(): void
    {
        $logs = $this->prepareLogs();
        ThirdPartyProvider::$resourceOwner = ['id' => 'tp-1', 'email' => 'new@example.com', 'verified' => true];

        $this->oauthLogin('verifiedthirdparty');

        $this->assertSame('linked@machine.local', User::find(3)->email);
        $this->assertCount(1, $this->sentMail($logs, 'new@example.com'));
        $this->assertCount(0, $this->warnings($logs, '[fof/oauth]'));
    }

    public function test_provider_with_unverified_email_is_not_synced_and_does_not_warn(): void
    {
        $logs = $this->prepareLogs();
        ThirdPartyProvider::$resourceOwner = ['id' => 'tp-1', 'email' => 'new@example.com', 'verified' => false];

        $payload = $this->oauthLogin('verifiedthirdparty');

        $this->assertTrue($payload['loggedIn'] ?? false);
        $this->assertSame('linked@machine.local', User::find(3)->email);
        $this->assertCount(0, $this->warnings($logs, '[fof/oauth]'));
    }

    public function test_failed_verification_lookup_does_not_block_login_and_is_logged(): void
    {
        $logs = $this->prepareLogs();
        ThirdPartyProvider::$resourceOwner = ['id' => 'tp-1', 'email' => 'new@example.com'];

        $payload = $this->oauthLogin('throwingthirdparty');

        $this->assertTrue($payload['loggedIn'] ?? false);
        $this->assertSame('linked@machine.local', User::find(3)->email);

        $warnings = $this->warnings($logs, 'verifying the email failed');
        $this->assertCount(1, $warnings);
        $this->assertStringContainsString('verification API unavailable', $warnings[0]['message']);
    }

    public function test_provider_email_is_not_synced_when_sync_disabled(): void
    {
        $this->setting('fof-oauth.update_email_from_provider', 0);
        $logs = $this->prepareLogs();
        ThirdPartyProvider::$resourceOwner = ['id' => 'tp-1', 'email' => 'new@example.com', 'verified' => true];

        $this->oauthLogin('verifiedthirdparty');

        $this->assertSame('linked@machine.local', User::find(3)->email);
        $this->assertCount(0, $this->warnings($logs, '[fof/oauth]'));
    }
}
