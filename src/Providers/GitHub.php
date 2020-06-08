<?php


namespace FoF\OAuth\Providers;


use FoF\OAuth\Controllers;
use FoF\OAuth\Provider;

class GitHub extends Provider
{
    protected function name(): string
    {
        return 'github';
    }

    protected function link(): string
    {
        return 'https://github.com/settings/developers';
    }

    protected function package(): string
    {
        return 'league/oauth2-github';
    }

    protected function controller(): string
    {
        return Controllers\GitHubAuthController::class;
    }

    protected function fields(): array
    {
        return [
            'client_id' => 'required',
            'client_secret' => 'required',
        ];
    }

    protected function available(): bool
    {
        return class_exists(\League\OAuth2\Client\Provider\Github::class);
    }
}
