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
use Illuminate\Contracts\Bus\Dispatcher;

class UpdateEmailFromProvider
{
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var Dispatcher
     */
    protected $bus;

    public function __construct(SettingsRepositoryInterface $settings, Dispatcher $bus)
    {
        $this->settings = $settings;
        $this->bus = $bus;
    }

    public function handle(OAuthLoginSuccessful $event)
    {
        if ((bool) $this->settings->get('fof-oauth.update_email_from_provider') && method_exists($event->userResource, 'getEmail')) {
            $this->bus->dispatch(new CheckAndUpdateUserEmail(
                $event->providerName,
                $event->userResource->getId(),
                $event->userResource->getEmail()
            ));
        }
    }
}
