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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Covers the defaults third-party providers inherit from `FoF\OAuth\Provider`.
 */
class ProviderTest extends TestCase
{
    /**
     * @var SettingsRepositoryInterface&MockObject
     */
    private $settings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->settings = $this->createMock(SettingsRepositoryInterface::class);
    }

    private function makeProvider(): Provider
    {
        return new class($this->settings) extends Provider {
            public function name(): string
            {
                return 'testprovider';
            }

            public function link(): string
            {
                return 'https://example.com/docs';
            }

            public function fields(): array
            {
                return ['client_id' => 'required', 'client_secret' => 'required'];
            }

            public function provider(string $redirectUri): ?AbstractProvider
            {
                return null;
            }

            public function exposeSetting(string $key): string
            {
                return $this->getSetting($key);
            }
        };
    }

    public function test_icon_defaults_to_fab_fa_name(): void
    {
        $this->assertSame('fab fa-testprovider', $this->makeProvider()->icon());
    }

    public function test_priority_defaults_to_zero(): void
    {
        $this->assertSame(0, $this->makeProvider()->priority());
    }

    public function test_options_defaults_to_empty_array(): void
    {
        $this->assertSame([], $this->makeProvider()->options());
    }

    public function test_is_included_in_route_pattern_by_default(): void
    {
        $this->assertFalse($this->makeProvider()->excludeFromRoutePattern());
    }

    public function test_suggestions_is_a_no_op_by_default(): void
    {
        $registration = new Registration();

        $this->makeProvider()->suggestions($registration, new \stdClass(), 'token');

        $this->assertSame([], $registration->getProvided());
        $this->assertSame([], $registration->getSuggested());
    }

    public function test_enabled_reads_from_settings(): void
    {
        $this->settings->method('get')->with('fof-oauth.testprovider')->willReturn('1');

        $this->assertNotEmpty($this->makeProvider()->enabled());
    }

    public function test_enabled_is_falsy_when_setting_is_absent(): void
    {
        $this->settings->method('get')->with('fof-oauth.testprovider')->willReturn(null);

        $this->assertEmpty($this->makeProvider()->enabled());
    }

    public function test_get_setting_is_namespaced_by_provider_name(): void
    {
        $this->settings->method('get')->with('fof-oauth.testprovider.client_id')->willReturn('abc');

        $this->assertSame('abc', $this->makeProvider()->exposeSetting('client_id'));
    }

    public function test_get_setting_returns_empty_string_when_absent(): void
    {
        $this->settings->method('get')->willReturn(null);

        $this->assertSame('', $this->makeProvider()->exposeSetting('client_id'));
    }
}
