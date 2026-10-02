<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth;

use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Illuminate\Contracts\Container\Container;

/**
 * OAuth-only mode: members log in and register through providers only, admins keep password login.
 */
class OAuthOnly
{
    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected Container $container
    ) {
    }

    /**
     * Only takes effect while at least one provider is enabled, so disabling the last one can't lock members out.
     */
    public function active(): bool
    {
        return (bool) $this->settings->get('fof-oauth.oauth_only')
            && !empty($this->container->make('fof-oauth.providers.forum'));
    }

    public function allowsPasswordAuth(?User $user): bool
    {
        return !$this->active() || ($user !== null && $user->isAdmin());
    }
}
