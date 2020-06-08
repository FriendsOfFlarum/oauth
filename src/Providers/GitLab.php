<?php


namespace FoF\OAuth\Providers;


use FoF\OAuth\Controllers;
use FoF\OAuth\Provider;

class GitLab extends Provider
{
    protected function name(): string
    {
        return 'gitlab';
    }

    protected function link(): string
    {
        return 'https://gitlab.com/profile/applications';
    }

    protected function package(): string
    {
        return 'omines/oauth2-gitlab';
    }

    protected function controller(): string
    {
        return Controllers\GitLabAuthController::class;
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
        return class_exists(\Omines\OAuth2\Client\Provider\Gitlab::class);
    }
}
