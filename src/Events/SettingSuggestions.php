<?php

namespace FoF\OAuth\Events;

use Flarum\Forum\Auth\Registration;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class SettingSuggestions
{
    public $provider;
    public $registration;
    public $resourceOwner;
    public $token;

    public function __construct(string $provider, Registration $registration, ResourceOwnerInterface $resourceOwner, ?string $token = null)
    {
        $this->provider = $provider;
        $this->registration = $registration;
        $this->resourceOwner = $resourceOwner;
        $this->token = $token;
    }
}
