<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Tests\unit;

use Flarum\Forum\Auth\Registration;
use Flarum\Settings\SettingsRepositoryInterface;
use FoF\OAuth\Provider;
use League\OAuth2\Client\Provider\AbstractProvider;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;
use Flarum\Testing\unit\TestCase;

class ProviderTest extends TestCase
{
    private SettingsRepositoryInterface $settings;

    protected function setUp(): void
    {
        parent::setUp();
        $this->settings = m::mock(SettingsRepositoryInterface::class);
    }

    private function makeProvider(array $overrides = []): Provider
    {
        return new class($this->settings, $overrides) extends Provider {
            public function __construct(
                SettingsRepositoryInterface $settings,
                private array $overrides = []
            ) {
                parent::__construct($settings);
            }

            public function name(): string
            {
                return $this->overrides['name'] ?? 'testprovider';
            }

            public function link(): string
            {
                return 'https://example.com/docs';
            }

            public function fields(): array
            {
                return ['client_id' => [], 'client_secret' => []];
            }

            public function provider(string $redirectUri): ?AbstractProvider
            {
                return null;
            }

            public function pkceEnabled(): bool
            {
                return $this->overrides['pkceEnabled'] ?? false;
            }
        };
    }

    #[Test]
    public function icon_defaults_to_fab_fa_name(): void
    {
        $provider = $this->makeProvider();
        $this->assertEquals('fab fa-testprovider', $provider->icon());
    }

    #[Test]
    public function priority_defaults_to_zero(): void
    {
        $provider = $this->makeProvider();
        $this->assertEquals(0, $provider->priority());
    }

    #[Test]
    public function options_defaults_to_empty_array(): void
    {
        $provider = $this->makeProvider();
        $this->assertEquals([], $provider->options());
    }

    #[Test]
    public function suggestions_is_a_no_op_by_default(): void
    {
        $provider = $this->makeProvider();
        $registration = new Registration();

        // Should not throw, should not modify registration.
        $provider->suggestions($registration, new \stdClass(), 'token');

        $this->assertEquals([], $registration->getProvided());
        $this->assertEquals([], $registration->getSuggested());
    }

    #[Test]
    public function pkce_enabled_returns_declared_value(): void
    {
        $this->assertTrue($this->makeProvider(['pkceEnabled' => true])->pkceEnabled());
        $this->assertFalse($this->makeProvider(['pkceEnabled' => false])->pkceEnabled());
    }

    #[Test]
    public function enabled_reads_from_settings(): void
    {
        $this->settings->shouldReceive('get')
            ->with('fof-oauth.testprovider')
            ->andReturn('1');

        $provider = $this->makeProvider();
        $this->assertTrue($provider->enabled());
    }

    #[Test]
    public function enabled_returns_false_when_setting_is_absent(): void
    {
        $this->settings->shouldReceive('get')
            ->with('fof-oauth.testprovider')
            ->andReturn(null);

        $provider = $this->makeProvider();
        $this->assertFalse($provider->enabled());
    }
}
