<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Errors;

use Exception;

/**
 * Thrown when a provider only suggests an email (it is not verified) that an existing account already uses.
 * The controller turns it into a popup response asking the user to log in and link the provider; it is not an error.
 */
class UnverifiedEmailInUseException extends Exception
{
    /**
     * @var string
     */
    public $provider;

    /**
     * @var string
     */
    public $email;

    public function __construct(string $provider, string $email)
    {
        parent::__construct('An existing account uses the unverified email reported by the provider.');

        $this->provider = $provider;
        $this->email = $email;
    }
}
