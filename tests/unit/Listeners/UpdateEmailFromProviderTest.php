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
use Flarum\Testing\unit\TestCase;
use FoF\OAuth\Events\OAuthLoginSuccessful;
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
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

class UpdateEmailFromProviderTest extends TestCase
{
    private Container $container;

    private Dispatcher&MockObject $bus;

    private TestHandler $logs;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logs = new TestHandler();

        $this->container = new Container();
        $this->container->instance(SettingsRepositoryInterface::class, $this->settings('1'));
        $this->container->instance('log', new Logger('test', [$this->logs]));
        $this->container->tag([ThirdPartyProvider::class, VerifiedThirdPartyProvider::class, ThrowingThirdPartyProvider::class], 'fof-oauth.providers');

        $this->bus = $this->createMock(Dispatcher::class);
    }

    private function settings(string $sync): SettingsRepositoryInterface
    {
        $settings = $this->createStub(SettingsRepositoryInterface::class);
        $settings->method('get')->willReturnCallback(fn ($key) => $key === 'fof-oauth.update_email_from_provider' ? $sync : null);

        return $settings;
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
            (string) ($owner['id'] ?? '')
        );
    }

    #[Test]
    public function verified_email_dispatches_sync_job(): void
    {
        $this->bus->expects($this->once())->method('dispatch')->with($this->callback(
            fn ($job) => $job instanceof CheckAndUpdateUserEmail
                && $job->providerName === 'verifiedthirdparty'
                && $job->identifier === 'tp-1'
                && $job->providedEmail === 'user@example.com'
        ));

        $this->listener()->handle($this->event('verifiedthirdparty', ['id' => 'tp-1', 'email' => 'user@example.com', 'verified' => true]));
    }

    #[Test]
    public function unverified_email_dispatches_nothing(): void
    {
        $this->bus->expects($this->never())->method('dispatch');

        $this->listener()->handle($this->event('verifiedthirdparty', ['id' => 'tp-1', 'email' => 'user@example.com', 'verified' => false]));

        $this->assertCount(0, $this->logs->getRecords());
    }

    #[Test]
    public function disabled_setting_dispatches_nothing(): void
    {
        $this->bus->expects($this->never())->method('dispatch');

        $this->listener(null, $this->settings('0'))->handle($this->event('verifiedthirdparty', ['id' => 'tp-1', 'email' => 'user@example.com', 'verified' => true]));
    }

    #[Test]
    public function unregistered_provider_dispatches_nothing(): void
    {
        $this->bus->expects($this->never())->method('dispatch');

        $this->listener()->handle($this->event('notregistered', ['id' => 'tp-1', 'email' => 'user@example.com', 'verified' => true]));

        $this->assertCount(0, $this->logs->getRecords());
    }

    #[Test]
    public function provider_without_implementation_warns_once_per_ttl(): void
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
        $this->assertTrue($this->logs->hasWarningThatContains(UpdateEmailFromProvider::DOCS_URL));
        $this->assertSame(86400, UpdateEmailFromProvider::WARNING_TTL);
    }

    #[Test]
    public function failed_lookup_is_logged_and_dispatches_nothing(): void
    {
        $this->bus->expects($this->never())->method('dispatch');

        $this->listener()->handle($this->event('throwingthirdparty', ['id' => 'tp-1', 'email' => 'user@example.com']));

        $this->assertTrue($this->logs->hasWarningThatContains('verifying the email failed: verification API unavailable'));
    }
}
