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

use Flarum\Group\Group;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Event\RegisteringFromProvider;
use Flarum\User\User;

class AssignGroupToUser
{
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @param SettingsRepositoryInterface $settings
     */
    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @param RegisteringFromProvider $event
     */
    public function handle(RegisteringFromProvider $event)
    {
        $provider = $event->provider;
        $user = $event->user;

        // Get the group ID for this provider
        $groupId = $this->settings->get("fof-oauth.{$provider}.group");

        // If a group is specified, assign it to the user
        if ($groupId && is_numeric($groupId)) {
            $user->afterSave(function (User $user) use ($groupId) {
                // Attach the group to the user
                $user->groups()->attach($groupId);
            });
        }
    }
}
