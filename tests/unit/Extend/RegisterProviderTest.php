<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Tests\unit\Extend;

use Flarum\Settings\SettingsRepositoryInterface;
use FoF\OAuth\Extend\RegisterProvider;
use FoF\OAuth\Provider;
use FoF\OAuth\Providers\GitLab;
use FoF\OAuth\Tests\fixtures\ThirdParty\ThirdPartyProvider;
use Illuminate\Container\Container;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class RegisterProviderTest extends TestCase
{
    /**
     * @var Container
     */
    private $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new Container();
        $this->container->instance(SettingsRepositoryInterface::class, $this->createMock(SettingsRepositoryInterface::class));
    }

    private function taggedNames(): array
    {
        return array_map(function (Provider $provider) {
            return $provider->name();
        }, iterator_to_array($this->container->tagged('fof-oauth.providers')));
    }

    public function test_registers_provider_under_tag(): void
    {
        (new RegisterProvider(ThirdPartyProvider::class))->extend($this->container);

        $this->assertSame(['thirdparty'], $this->taggedNames());
    }

    public function test_throws_when_class_does_not_extend_provider(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new RegisterProvider(\stdClass::class))->extend($this->container);
    }

    public function test_two_different_providers_can_coexist(): void
    {
        (new RegisterProvider(GitLab::class))->extend($this->container);
        (new RegisterProvider(ThirdPartyProvider::class))->extend($this->container);

        $this->assertSame(['gitlab', 'thirdparty'], $this->taggedNames());
    }
}
