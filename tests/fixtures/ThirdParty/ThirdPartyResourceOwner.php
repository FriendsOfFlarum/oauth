<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Tests\fixtures\ThirdParty;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class ThirdPartyResourceOwner implements ResourceOwnerInterface
{
    public function __construct(private array $response)
    {
    }

    public function getId(): mixed
    {
        return $this->response['id'] ?? null;
    }

    public function getEmail(): ?string
    {
        return $this->response['email'] ?? null;
    }

    public function toArray(): array
    {
        return $this->response;
    }
}
