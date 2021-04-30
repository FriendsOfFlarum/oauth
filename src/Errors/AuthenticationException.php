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
    const MESSAGE_TYPES = [
        'bad_verification_code' => [
            'OAuthException: This authorization code has expired.',
            'Received HTTP status code [401] with message "This feature is temporarily unavailable" when getting token credentials.',
        ],

        'invalid_state' => [
            'Invalid state',
        ],
    ];

    public function getShortCode(): string
    {
        $message = $this->getMessage();

        if (!Arr::has(self::MESSAGE_TYPES, $message)) {
            foreach (self::MESSAGE_TYPES as $type => $messages) {
                if (in_array($message, $messages)) {
                    return $type;
                }
            }
        }

        return $message;
    }

    public function getType(): string
    {
        return 'authentication_error';
    }

    public function shouldBeReported()
    {
        $code = $this->getShortCode();

        return $code !== 'invalid_state' && $code !== 'bad_verification_code';
    }
}
