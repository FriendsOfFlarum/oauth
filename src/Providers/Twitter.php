<?php


namespace FoF\OAuth\Providers;


use FoF\OAuth\Controllers;
use FoF\OAuth\Provider;

class Twitter extends Provider
{
    public function name(): string
    {
        return 'twitter';
    }

    public function link(): string
    {
        return 'https://developer.twitter.com/en/apps';
    }

    public function package(): string
    {
        return 'league/oauth1-client';
    }

    public function fields(): array
    {
        return [
            'api_key' => 'required',
            'api_secret' => 'required',
        ];
    }

    public function available(): bool
    {
        return class_exists(\League\OAuth1\Client\Server\Twitter::class);
    }
}
