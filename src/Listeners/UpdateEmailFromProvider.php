<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Listeners;

use Flarum\Settings\SettingsRepositoryInterface;
use FoF\Extend\Events\OAuthLoginSuccessful;
use FoF\OAuth\Jobs\CheckAndUpdateUserEmail;
use FoF\OAuth\Provider;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Container\Container;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use Throwable;

class UpdateEmailFromProvider
{
    public const DOCS_URL = 'https://github.com/FriendsOfFlarum/oauth/blob/1.x/README.md#email-trust';

    /**
     * Seconds before the "provider does not implement getProviderVerifiedEmail()" warning is repeated.
     */
    public const WARNING_TTL = 86400;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var Dispatcher
     */
    protected $bus;

    /**
     * @var Container
     */
    protected $container;

    /**
     * @var Cache
     */
    protected $cache;

    public function __construct(SettingsRepositoryInterface $settings, Dispatcher $bus, Container $container, Cache $cache)
    {
        $this->settings = $settings;
        $this->bus = $bus;
        $this->container = $container;
        $this->cache = $cache;
    }

    public function handle(OAuthLoginSuccessful $event)
    {
        if (!(bool) $this->settings->get('fof-oauth.update_email_from_provider')) {
            return;
        }

        $provider = $this->findProvider($event->providerName);

        if ($provider === null) {
            return;
        }

        $email = $this->verifiedEmail($provider, $event);

        // Only an address the provider confirms the user owns may replace the account's email.
        if ($email === null) {
            return;
        }

        $this->bus->dispatch(new CheckAndUpdateUserEmail(
            $event->providerName,
            $event->userResource->getId(),
            $email
        ));
    }

    protected function findProvider(string $name): ?Provider
    {
        /** @var Provider $provider */
        foreach ($this->container->tagged('fof-oauth.providers') as $provider) {
            if ($provider->name() === $name) {
                return $provider;
            }
        }

        return null;
    }

    protected function verifiedEmail(Provider $provider, OAuthLoginSuccessful $event): ?string
    {
        if (!$this->implementsVerifiedEmail($provider)) {
            $this->warnOnce(
                "fof-oauth.unverified-provider-warned.{$provider->name()}",
                sprintf(
                    '[fof/oauth] Email sync skipped for provider "%s" (%s): it does not implement getProviderVerifiedEmail(), so the email it reports cannot be trusted. See %s',
                    $provider->name(),
                    get_class($provider),
                    self::DOCS_URL
                )
            );

            return null;
        }

        try {
            return $provider->getProviderVerifiedEmail($event->userResource, $event->token->getToken());
        } catch (Throwable $e) {
            // A failed verification lookup must never block the login itself.
            $this->logger()->warning(sprintf(
                '[fof/oauth] Email sync skipped for provider "%s": verifying the email failed: %s',
                $provider->name(),
                $e->getMessage()
            ));

            return null;
        }
    }

    protected function implementsVerifiedEmail(Provider $provider): bool
    {
        return (new ReflectionMethod($provider, 'getProviderVerifiedEmail'))->getDeclaringClass()->getName() !== Provider::class;
    }

    protected function warnOnce(string $key, string $message): void
    {
        if ($this->cache->add($key, true, self::WARNING_TTL)) {
            $this->logger()->warning($message);
        }
    }

    protected function logger(): LoggerInterface
    {
        return $this->container->make('log');
    }
}
