<?php


namespace FoF\OAuth\Providers;


use Flarum\Forum\Auth\Registration;
use FoF\OAuth\Provider;
use League\OAuth2\Client\Provider\AbstractProvider;
use Omines\OAuth2\Client\Provider\Gitlab as GitlabProvider;

class GitLab extends Provider
{
    public function name(): string
    {
        return 'gitlab';
    }

    public function link(): string
    {
        return 'https://gitlab.com/profile/applications';
    }

    public function package(): string
    {
        return 'omines/oauth2-gitlab';
    }

    public function fields(): array
    {
        return [
            'client_id' => 'required',
            'client_secret' => 'required',
        ];
    }

    public function available(): bool
    {
        return class_exists(GitlabProvider::class);
    }



    public function provider(string $redirectUri): AbstractProvider
    {
        return new GitlabProvider([
            'clientId' => $this->getSetting('client_id'),
            'clientSecret' => $this->getSetting('client_secret'),
            'redirectUri' => $redirectUri,
        ]);
    }

    public function options(): array
    {
        return ['scope' => 'read_user'];
    }

    public function suggestions(Registration $registration, $user, string $token)
    {
        $this->verifyEmail($email = $user->getEmail());

        $registration
            ->provideTrustedEmail($email)
            ->provideAvatar($user->getAvatarUrl() ?: '')
            ->suggestUsername($user->getUsername() ?: '')
            ->setPayload($user->toArray());
    }
}
