<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Tests\unit\Errors;

use FoF\OAuth\Errors\AuthenticationException;
use PHPUnit\Framework\TestCase;

class AuthenticationExceptionTest extends TestCase
{
    public function test_known_short_code_is_returned_as_is(): void
    {
        $this->assertSame('invalid_state', (new AuthenticationException('invalid_state'))->getShortCode());
    }

    public function test_raw_invalid_state_message_maps_to_short_code(): void
    {
        $this->assertSame('invalid_state', (new AuthenticationException('Invalid state'))->getShortCode());
    }

    public function test_expired_code_message_maps_to_bad_verification_code(): void
    {
        $e = new AuthenticationException('OAuthException: This authorization code has expired.');

        $this->assertSame('bad_verification_code', $e->getShortCode());
    }

    public function test_twitter_unavailable_message_maps_to_bad_verification_code(): void
    {
        $e = new AuthenticationException('Received HTTP status code [401] with message "This feature is temporarily unavailable" when getting token credentials.');

        $this->assertSame('bad_verification_code', $e->getShortCode());
    }

    public function test_unknown_message_falls_back_to_message(): void
    {
        $this->assertSame('invalid_email', (new AuthenticationException('invalid_email'))->getShortCode());
    }

    public function test_invalid_state_is_not_reported(): void
    {
        $this->assertFalse((new AuthenticationException('Invalid state'))->shouldBeReported());
    }

    public function test_bad_verification_code_is_not_reported(): void
    {
        $e = new AuthenticationException('OAuthException: This authorization code has expired.');

        $this->assertFalse($e->shouldBeReported());
    }

    public function test_unknown_errors_are_reported(): void
    {
        $this->assertTrue((new AuthenticationException('something_unexpected'))->shouldBeReported());
    }

    public function test_type_is_authentication_error(): void
    {
        $this->assertSame('authentication_error', (new AuthenticationException('invalid_state'))->getType());
    }
}
