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

use Flarum\Testing\unit\TestCase;
use FoF\OAuth\Errors\AuthenticationException;
use PHPUnit\Framework\Attributes\Test;

class AuthenticationExceptionTest extends TestCase
{
    #[Test]
    public function short_code_returned_when_message_is_already_a_known_code(): void
    {
        $e = new AuthenticationException('invalid_state');
        $this->assertEquals('invalid_state', $e->getShortCode());
    }

    #[Test]
    public function short_code_returned_when_message_is_already_a_known_code_already_linked(): void
    {
        $e = new AuthenticationException('already_linked');
        $this->assertEquals('already_linked', $e->getShortCode());
    }

    #[Test]
    public function short_code_maps_from_raw_provider_message(): void
    {
        $e = new AuthenticationException('OAuthException: This authorization code has expired.');
        $this->assertEquals('bad_verification_code', $e->getShortCode());
    }

    #[Test]
    public function short_code_falls_back_to_message_for_unknown_errors(): void
    {
        $e = new AuthenticationException('some_unknown_error');
        $this->assertEquals('some_unknown_error', $e->getShortCode());
    }

    #[Test]
    public function invalid_state_is_not_reported(): void
    {
        $e = new AuthenticationException('invalid_state');
        $this->assertFalse($e->shouldBeReported());
    }

    #[Test]
    public function bad_verification_code_is_not_reported(): void
    {
        $e = new AuthenticationException('OAuthException: This authorization code has expired.');
        $this->assertFalse($e->shouldBeReported());
    }

    #[Test]
    public function already_linked_is_not_reported(): void
    {
        $e = new AuthenticationException('already_linked');
        $this->assertFalse($e->shouldBeReported());
    }

    #[Test]
    public function unknown_errors_are_reported(): void
    {
        $e = new AuthenticationException('something_unexpected');
        $this->assertTrue($e->shouldBeReported());
    }

    #[Test]
    public function type_is_authentication_error(): void
    {
        $e = new AuthenticationException('invalid_state');
        $this->assertEquals('authentication_error', $e->getType());
    }
}
