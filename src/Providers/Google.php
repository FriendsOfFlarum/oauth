<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Providers;

use Flarum\Forum\Auth\Registration;
use FoF\OAuth\Provider;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Google as GoogleProvider;
use League\OAuth2\Client\Provider\GoogleUser;

class Google extends Provider
{
    public function name(): string
    {
        return 'google';
    }

    public function link(): string
    {
        return 'https://console.developers.google.com/apis/credentials';
    }

    public function fields(): array
    {
        return [
            'client_id'     => 'required',
            'client_secret' => 'required',
            'hosted_domain' => '',
        ];
    }

    public function provider(string $redirectUri): AbstractProvider
    {
        return new GoogleProvider([
            'clientId'       => $this->getSetting('client_id'),
            'clientSecret'   => $this->getSetting('client_secret'),
            'redirectUri'    => $redirectUri,
            'approvalPrompt' => 'force',
            'hostedDomain'   => $this->getHostedDomain(),
            'accessType'     => 'offline',
        ]);
    }

    /**
     * @return string|null
     */
    protected function getHostedDomain()
    {
        $hostedDomain = $this->getSetting('hosted_domain');

        // Return null if $hostedDomain is an empty string
        return $hostedDomain !== '' ? $hostedDomain : null;
    }

    /**
     * @param GoogleUser $user
     */
    public function suggestions(Registration $registration, $user, string $token)
    {
        $this->verifyEmail($email = $user->getEmail());

        if ($this->getProviderVerifiedEmail($user, $token) !== null) {
            $registration->provideTrustedEmail($email);
        } else {
            $registration->suggestEmail($email);
        }

        $registration
            ->suggestUsername($user->getName())
            ->setPayload($user->toArray());

        $this->provideAvatar($registration, $user->getAvatar());
    }

    /**
     * @param GoogleUser $user
     */
    public function getProviderVerifiedEmail($user, string $token): ?string
    {
        // Google's guidance (`isEmailTrustworthy()`) is stricter: it only trusts @gmail.com addresses, or verified
        // Workspace addresses (with an `hd` claim), because Google is only authoritative for those. A personal
        // Google account registered with any other address, e.g. user@example.com, reports `email_verified: true`
        // once the user proved control of that mailbox, but Google cannot vouch that they still control it: the
        // domain may since have changed hands.
        //
        // We also trust `email_verified` on its own. Flarum already treats control of a mailbox as proof of
        // identity (a password reset goes to it), so this adds little beyond that, and it matches how other
        // providers' verified flags are treated. Without it, users of such accounts could never link or sign in
        // to an existing forum account with Google.
        $emailVerified = $user->toArray()['email_verified'] ?? null;

        if ($user->isEmailTrustworthy() || $emailVerified === true) {
            return $user->getEmail() ?: null;
        }

        return null;
    }
}
