<?php


namespace FoF\OAuth\Providers;


use FoF\OAuth\Controllers;
use FoF\OAuth\Provider;

class Twitter extends Provider
{
    protected function name(): string
    {
        return 'twitter';
    }

    protected function link(): string
    {
        return 'https://developer.twitter.com/en/apps';
    }

    protected function package(): string
    {
        return 'league/oauth1-client';
    }

    protected function controller(): string
    {
        return Controllers\TwitterAuthController::class;
    }

    protected function fields(): array
    {
        return [
            'api_key' => 'required',
            'api_secret' => 'required',
        ];
    }

    protected function available(): bool
    {
        return class_exists(\League\OAuth1\Client\Server\Twitter::class);
    }
}
