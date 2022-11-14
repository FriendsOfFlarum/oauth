<?php

namespace FoF\OAuth;

use Flarum\User\LoginProvider;
use Flarum\User\User;

class LoginProviderStatus
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $icon;

    /**
     * @var int
     */
    public $priority;

    /**
     * @var int|null
     */
    public $userId;

    /**
     * @var bool
     */
    public $linked;

    /**
     * @var string|null
     */
    public $identifier;

    /**
     * @var string|null
     */
    public $providerIdentifier;

    /**
     * @var \DateTime|null
     */
    public $createdAt;

    /**
     * @var \DateTime|null
     */
    public $lastLogin;

    public static function build(string $name, string $icon, int $priority, User $user, LoginProvider $provider = null)
    {
        $status = new self();

        $status->name = $name;
        $status->icon = $icon;
        $status->priority = $priority;
        $status->linked = (bool) $provider;
        $status->userId = $user->id;

        if ($provider) {
            $status->identifier = $provider->id;
            $status->providerIdentifier = $provider->identifier;
            $status->createdAt = $provider->created_at;
            $status->lastLogin = $provider->last_login_at;
        }

        return $status;
    }
}
