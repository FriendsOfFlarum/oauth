<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Avatar;

use GuzzleHttp\Psr7\StreamDecoratorTrait;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\StreamInterface;

/**
 * A response body that stops accepting data past a size limit, which makes the transfer fail instead of buffering
 * an oversized response.
 */
class CappedBuffer implements StreamInterface
{
    use StreamDecoratorTrait;

    /** @var StreamInterface */
    protected $stream;

    protected bool $exceeded = false;

    public function __construct(protected int $maxBytes)
    {
        $this->stream = Utils::streamFor('');
    }

    public function write($string): int
    {
        $string = (string) $string;

        if ($this->exceeded || ($this->stream->getSize() ?? 0) + strlen($string) > $this->maxBytes) {
            $this->exceeded = true;

            return 0;
        }

        return $this->stream->write($string);
    }

    public function exceeded(): bool
    {
        return $this->exceeded;
    }
}
