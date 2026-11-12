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

use Flarum\Foundation\Application;
use Flarum\Foundation\Paths;
use Flarum\Http\RouteCollection;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\unit\TestCase;
use FoF\OAuth\Extend\RegisterProvider;
use FoF\OAuth\OAuthServiceProvider;
use FoF\OAuth\Provider;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\Container as ContainerContract;
use InvalidArgumentException;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;

class RegisterProviderTest extends TestCase
{
    private ContainerContract $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = new Container();
    }

    private function makeProviderClass(string $name, ContainerContract $container): string
    {
        $className = 'TestProvider_'.$name.'_'.uniqid();

        // Dynamically define a provider class with the given name.
        eval(<<<PHP
            class {$className} extends FoF\\OAuth\\Provider {
                public function name(): string { return '{$name}'; }
                public function link(): string { return 'https://example.com'; }
                public function fields(): array { return []; }
                public function provider(string \$redirectUri): ?\\League\\OAuth2\\Client\\Provider\\AbstractProvider { return null; }
                public function pkceEnabled(): bool { return false; }
            }
        PHP);

        $container->bind($className, fn () => new $className(
            m::mock(\Flarum\Settings\SettingsRepositoryInterface::class)
        ));

        return $className;
    }

    #[Test]
    public function registers_provider_under_tag(): void
    {
        $className = $this->makeProviderClass('myprovider', $this->container);

        $extender = new RegisterProvider($className);
        $extender->extend($this->container);

        $tagged = iterator_to_array($this->container->tagged('fof-oauth.providers'));
        $this->assertCount(1, $tagged);
        $this->assertInstanceOf(Provider::class, $tagged[0]);
        $this->assertEquals('myprovider', $tagged[0]->name());
    }

    #[Test]
    public function throws_when_class_does_not_extend_provider(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $extender = new RegisterProvider(\stdClass::class);
        $extender->extend($this->container);
    }

    #[Test]
    public function throws_when_duplicate_provider_names_are_resolved_for_routes(): void
    {
        $app = new Application(new Paths(['base' => __DIR__, 'public' => __DIR__, 'storage' => __DIR__]));
        $app->instance(SettingsRepositoryInterface::class, m::mock(SettingsRepositoryInterface::class));
        $app->singleton('flarum.forum.routes', fn () => new RouteCollection());
        (new OAuthServiceProvider($app))->register();
        $class1 = $this->makeProviderClass('duplicate', $app);
        $class2 = $this->makeProviderClass('duplicate', $app);
        (new RegisterProvider($class1))->extend($app);
        (new RegisterProvider($class2))->extend($app);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("OAuth provider 'duplicate' is already registered. Each provider name must be unique across all extensions.");
        $app->make('flarum.forum.routes');
    }

    #[Test]
    public function registers_a_provider_through_a_container_binding(): void
    {
        $className = $this->makeProviderClass('bound', $this->container);
        $this->container->bind('bound-provider', fn () => $this->container->make($className));
        $this->container->alias('bound-provider', 'provider-alias');

        (new RegisterProvider('provider-alias'))->extend($this->container);

        $tagged = iterator_to_array($this->container->tagged('fof-oauth.providers'));
        $this->assertCount(1, $tagged);
        $this->assertInstanceOf(Provider::class, $tagged[0]);
        $this->assertSame('bound', $tagged[0]->name());
    }

    #[Test]
    public function registers_an_existing_provider_instance(): void
    {
        $className = $this->makeProviderClass('existing', $this->container);
        $provider = new $className(m::mock(SettingsRepositoryInterface::class));
        $this->container->instance($className, $provider);

        (new RegisterProvider($className))->extend($this->container);

        $this->assertSame([$provider], iterator_to_array($this->container->tagged('fof-oauth.providers')));
    }

    #[Test]
    public function rejects_a_binding_that_does_not_resolve_to_a_provider(): void
    {
        $this->container->bind('NotAProvider', fn () => new \stdClass());
        (new RegisterProvider('NotAProvider'))->extend($this->container);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('NotAProvider must extend '.Provider::class);
        iterator_to_array($this->container->tagged('fof-oauth.providers'));
    }

    #[Test]
    public function two_different_providers_can_coexist(): void
    {
        $class1 = $this->makeProviderClass('alpha', $this->container);
        $class2 = $this->makeProviderClass('beta', $this->container);

        (new RegisterProvider($class1))->extend($this->container);
        (new RegisterProvider($class2))->extend($this->container);

        $tagged = iterator_to_array($this->container->tagged('fof-oauth.providers'));
        $this->assertCount(2, $tagged);
    }
}
