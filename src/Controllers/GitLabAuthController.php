<?php


namespace FoF\OAuth\Controllers;


use Flarum\Forum\Auth\Registration;
use FoF\OAuth\Controller;
use League\OAuth2\Client\Provider\AbstractProvider;
use Omines\OAuth2\Client\Provider\Gitlab;

class GitLabAuthController extends Controller
{

    protected function getProvider(string $redirectUri): AbstractProvider
    {
        return new Gitlab([
            'clientId' => $this->getSetting('client_id'),
            'clientSecret' => $this->getSetting('client_secret'),
            'redirectUri' => $redirectUri,
        ]);
    }

    protected function getProviderName(): string
    {
        return 'gitlab';
    }

    protected function getAuthorizationUrlOptions(): array
    {
        return ['scope' => 'read_user'];
    }

    /**
     * @param Registration $registration
     * @param GitlabResourceOwnewr $user
     * @param string $token
     */
    protected function setSuggestions(Registration $registration, $user, string $token)
    {
        $this->verifyEmail($email = $user->getEmail());

        $registration
            ->provideTrustedEmail($email)
            ->provideAvatar($user->getAvatarUrl() ?: '')
            ->suggestUsername($user->getUsername() ?: '')
            ->setPayload($user->toArray());
    }
}
