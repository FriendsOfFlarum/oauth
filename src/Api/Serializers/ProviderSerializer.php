<?php

namespace FoF\OAuth\Api\Serializers;

use Flarum\Api\Serializer\AbstractSerializer;

class ProviderSerializer extends AbstractSerializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'linked-accounts';
    
    public function getDefaultAttributes($provider): array
    {
        return [
            'name' => $provider->name,
            'icon' => $provider->icon,
            'priority' => $provider->priority,
            'linked' => $provider->linked,
            'identifier' => $provider->identifier,
            'providerIdentifier' => $provider->providerIdentifier,
            'firstLogin' => $this->formatDate($provider->createdAt),
            'lastLogin' => $this->formatDate($provider->lastLogin),
        ];
    }

    public function getId($provider): string
    {
        return $provider->identifier?? "$provider->userId-$provider->name";
    }
}
