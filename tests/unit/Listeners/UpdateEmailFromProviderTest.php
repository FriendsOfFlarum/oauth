<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Tests\unit\Listeners;

use Flarum\Settings\SettingsRepositoryInterface;
use FoF\Extend\Events\OAuthLoginSuccessful;
use FoF\OAuth\Jobs\CheckAndUpdateUserEmail;
use FoF\OAuth\Listeners\UpdateEmailFromProvider;
use FoF\OAuth\Tests\fixtures\ThirdParty\ThirdPartyProvider;
use FoF\OAuth\Tests\fixtures\ThirdParty\ThirdPartyResourceOwner;
use FoF\OAuth\Tests\fixtures\ThirdParty\ThrowingThirdPartyProvider;
use FoF\OAuth\Tests\fixtures\ThirdParty\VerifiedThirdPartyProvider;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Cache\Repository as Cache;
use League\OAuth2\Client\Token\AccessToken;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdateEmailFromProviderTest extends TestCase
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var Dispatcher&MockObject
     */
    private $bus;

    /**
     * @var TestHandler
     */
    private $logs;

    protected function setUp(): void
    {
        parent::setUp();

        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('get')->willReturnCallback(function ($key) {
            return $key === 'fof-oauth.update_email_from_provider' ? '1' : null;
        });

        $this->logs = new TestHandler();

        $this->container = new Container();
        $this->container->instance(SettingsRepositoryInterface::class, $settings);
        $this->container->instance('log', new Logger('test', [$this->logs]));
        $this->container->tag([ThirdPartyProvider::class, VerifiedThirdPartyProvider::class, ThrowingThirdPartyProvider::class], 'fof-oauth.providers');

        $this->bus = $this->createMock(Dispatcher::class);
    }

    private function listener(?Cache $cache = null, ?SettingsRepositoryInterface $settings = null): UpdateEmailFromProvider
    {
        return new UpdateEmailFromProvider(
            $settings ?? $this->container->make(SettingsRepositoryInterface::class),
            $this->bus,
            $this->container,
            $cache ?? new CacheRepository(new ArrayStore())
        );
    }

    private function event(string $provider, array $owner): OAuthLoginSuccessful
    {
        return new OAuthLoginSuccessful(
            new AccessToken(['access_token' => 'token']),
            new ThirdPartyResourceOwner($owner),
            $provider,
            (string) ($owner['id'] ?? ''),
            null
        );
    }

    public function test_verified_email_dispatches_sync_job(): void
    {
        $this->bus->expects($this->once())->method('dispatch')->with($this->callback(function ($job) {
            return $job instanceof CheckAndUpdateUserEmail
                && $job->providerName === 'verifiedthirdparty'
                && $job->identifier === 'tp-1'
                && $job->providedEmail === 'user@example.com';
        }));

        $this->listener()->handle($this->event('verifiedthirdparty', ['id' => 'tp-1', 'email' => 'user@example.com', 'verified' => true]));
    }

    public function test_unverified_email_dispatches_nothing(): void
    {
        $this->bus->expects($this->never())->method('dispatch');

        $this->listener()->handle($this->event('verifiedthirdparty', ['id' => 'tp-1', 'email' => 'user@example.com', 'verified' => false]));

        $this->assertCount(0, $this->logs->getRecords());
    }

    public function test_disabled_setting_dispatches_nothing(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('get')->willReturn('0');

        $this->bus->expects($this->never())->method('dispatch');

        $this->listener(null, $settings)->handle($this->event('verifiedthirdparty', ['id' => 'tp-1', 'email' => 'user@example.com', 'verified' => true]));
    }

    public function test_unregistered_provider_dispatches_nothing(): void
    {
        $this->bus->expects($this->never())->method('dispatch');

        $this->listener()->handle($this->event('notregistered', ['id' => 'tp-1', 'email' => 'user@example.com', 'verified' => true]));

        $this->assertCount(0, $this->logs->getRecords());
    }

    public function test_provider_without_implementation_warns_once_per_ttl(): void
    {
        $cache = $this->createMock(Cache::class);
        $cache->expects($this->exactly(2))
            ->method('add')
            ->with('fof-oauth.unverified-provider-warned.thirdparty', true, UpdateEmailFromProvider::WARNING_TTL)
            ->willReturnOnConsecutiveCalls(true, false);

        $this->bus->expects($this->never())->method('dispatch');

        $listener = $this->listener($cache);
        $listener->handle($this->event('thirdparty', ['id' => 'tp-1', 'email' => 'user@example.com']));
        $listener->handle($this->event('thirdparty', ['id' => 'tp-1', 'email' => 'user@example.com']));

        $this->assertCount(1, $this->logs->getRecords());
        $this->assertTrue($this->logs->hasWarningThatContains('does not implement getProviderVerifiedEmail()'));
        $this->assertSame(86400, UpdateEmailFromProvider::WARNING_TTL);
    }

    public function test_warning_links_to_the_1x_readme(): void
    {
        // The repository's default branch is 2.x, so an unqualified README link would not show the 1.x docs.
        $this->assertSame('https://github.com/FriendsOfFlarum/oauth/blob/1.x/README.md#email-trust', UpdateEmailFromProvider::DOCS_URL);
    }

    public function test_failed_lookup_is_logged_and_dispatches_nothing(): void
    {
        $this->bus->expects($this->never())->method('dispatch');

        $this->listener()->handle($this->event('throwingthirdparty', ['id' => 'tp-1', 'email' => 'user@example.com']));

        $this->assertTrue($this->logs->hasWarningThatContains('verifying the email failed: verification API unavailable'));
    }
}
