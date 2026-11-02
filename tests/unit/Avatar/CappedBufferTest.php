<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Tests\unit\Avatar;

use Flarum\Testing\unit\TestCase;
use FoF\OAuth\Avatar\CappedBuffer;
use PHPUnit\Framework\Attributes\Test;

class CappedBufferTest extends TestCase
{
    #[Test]
    public function writes_up_to_the_cap_are_kept(): void
    {
        $buffer = new CappedBuffer(10);

        $this->assertSame(6, $buffer->write('abcdef'));
        $this->assertSame(4, $buffer->write('ghij'));
        $this->assertFalse($buffer->exceeded());
        $this->assertSame('abcdefghij', (string) $buffer);
    }

    #[Test]
    public function a_write_past_the_cap_is_refused_and_so_is_everything_after_it(): void
    {
        $buffer = new CappedBuffer(10);
        $buffer->write('abcdef');

        $this->assertSame(0, $buffer->write('ghijk'), 'Writing nothing makes cURL abort the transfer');
        $this->assertTrue($buffer->exceeded());
        $this->assertSame(0, $buffer->write('g'));
        $this->assertSame('abcdef', (string) $buffer);
    }
}
