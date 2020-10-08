<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) 2020 FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace FoF\OAuth;

use Flarum\Forum\Auth\Registration;
use Flarum\Settings\SettingsRepositoryInterface;
use FoF\OAuth\Errors\AuthenticationException;
use League\OAuth2\Client\Provider\AbstractProvider;

abstract class Provider
{
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    // Provider data

    abstract public function name(): string;

    abstract public function link(): string;

    abstract public function fields(): array;

    public function icon(): string
    {
        return "fab fa-{$this->name()}";
    }

    // Controller options

    public function provider(string $redirectUri): AbstractProvider
    {
        //
    }

    public function options(): array
    {
        return [];
    }

    public function suggestions(Registration $registration, $user, string $token)
    {
        //
    }

    // Helpers

    public function enabled()
    {
        return $this->settings->get("fof-oauth.{$this->name()}");
    }

    protected function getSetting($key): string
    {
        return $this->settings->get("fof-oauth.{$this->name()}.{$key}");
    }

    protected function verifyEmail($email)
    {
        if (!$email || empty($email)) {
            throw new AuthenticationException('invalid_email');
        }
    }
}
