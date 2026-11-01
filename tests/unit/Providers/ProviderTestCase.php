<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Tests\unit\Providers;

use Flarum\Forum\Auth\Registration;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\unit\TestCase;

abstract class ProviderTestCase extends TestCase
{
    protected function settings(): SettingsRepositoryInterface
    {
        $settings = $this->createStub(SettingsRepositoryInterface::class);
        $settings->method('get')->willReturn(null);

        return $settings;
    }

    protected function assertTrustedEmail(string $email, Registration $registration): void
    {
        $this->assertSame($email, $registration->getProvided()['email'] ?? null, 'Email should be provided as trusted');
        $this->assertArrayNotHasKey('email', $registration->getSuggested(), 'Trusted email should not also be suggested');
    }

    protected function assertSuggestedEmail(string $email, Registration $registration): void
    {
        $this->assertSame($email, $registration->getSuggested()['email'] ?? null, 'Email should only be suggested');
        $this->assertArrayNotHasKey('email', $registration->getProvided(), 'Suggested email must not be trusted');
    }
}
