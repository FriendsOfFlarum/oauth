<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Api\Serializers;

use Flarum\Api\Serializer\AbstractSerializer;

class ProviderSerializer extends AbstractSerializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'linked-accounts';

    /**
     * @param \FoF\OAuth\LoginProviderStatus $provider
     *
     * @return array
     */
    public function getDefaultAttributes($provider): array
    {
        return [
            'name'               => $provider->name,
            'icon'               => $provider->icon,
            'priority'           => $provider->priority,
            'orphaned'           => $provider->orphaned,
            'linked'             => $provider->linked,
            'identifier'         => $provider->identifier,
            'providerIdentifier' => $provider->providerIdentifier,
            'firstLogin'         => $this->formatDate($provider->createdAt),
            'lastLogin'          => $this->formatDate($provider->lastLogin),
        ];
    }

    public function getId($provider): string
    {
        return $provider->identifier ?? "$provider->userId-$provider->name";
    }
}
