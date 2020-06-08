<?php


namespace FoF\OAuth\Controllers;


use Flarum\Forum\Auth\Registration;
use FoF\OAuth\Controller;
use FoF\OAuth\Errors\AuthenticationException;
use Illuminate\Support\Arr;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Github;
use League\OAuth2\Client\Provider\GithubResourceOwner;

class GitHubAuthController extends Controller
{
    protected function getProvider(string $redirectUri): AbstractProvider
    {
        return new Github([
            'clientId'     => $this->getSetting('client_id'),
            'clientSecret' => $this->getSetting('client_secret'),
            'redirectUri'  => $redirectUri,
        ]);
    }

    protected function getProviderName(): string
    {
        return 'github';
    }

    protected function getAuthorizationUrlOptions(): array
    {
        return ['scope' => ['user:email']];
    }

    /**
     * @param Registration $registration
     * @param GithubResourceOwner $user
     * @param string $token
     */
    protected function setSuggestions(Registration $registration, $user, string $token)
    {
        $this->verifyEmail($email = $user->getEmail() ?: $this->getEmailFromApi($token));

        $registration
            ->provideTrustedEmail($email)
            ->suggestUsername($user->getNickname() ?: '')
            ->provideAvatar(Arr::get($user->toArray(), 'avatar_url', ''))
            ->setPayload($user->toArray());
    }

    private function getEmailFromApi(string $token)
    {
        $redirectUri = $this->url->to('forum')->route($this->getRouteName());
        $provider = $this->getProvider($redirectUri);

        $url = $provider->apiDomain.'/user/emails';

        $response = $provider->getResponse(
            $provider->getAuthenticatedRequest('GET', $url, $token)
        );

        $emails = json_decode($response->getBody(), true);

        foreach ($emails as $email) {
            if ($email['primary'] && $email['verified']) {
                return $email['email'];
            }
        }
    }
}
