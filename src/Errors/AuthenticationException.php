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
use Flarum\Foundation\KnownError;
use Illuminate\Support\Arr;

class AuthenticationException extends Exception implements KnownError
{
    /**
     * Map raw exception messages to short codes used for translations and reporting decisions.
     * If the message is already a known short code it is returned as-is.
     */
    const MESSAGE_TYPES = [
        'bad_verification_code' => [
            'OAuthException: This authorization code has expired.',
        ],
        'invalid_state'  => [],
        'already_linked' => [],
    ];

    public function getShortCode(): string
    {
        $message = trim($this->getMessage());

        // If the message itself is already a known short code, use it directly.
        if (Arr::has(self::MESSAGE_TYPES, $message)) {
            return $message;
        }

        // Otherwise scan the alias lists.
        foreach (self::MESSAGE_TYPES as $type => $aliases) {
            if (in_array($message, $aliases, true)) {
                return $type;
            }
        }

        return $message;
    }

    public function getType(): string
    {
        return 'authentication_error';
    }

    public function shouldBeReported(): bool
    {
        $code = $this->getShortCode();

        return !in_array($code, ['invalid_state', 'bad_verification_code', 'already_linked']);
    }
}
