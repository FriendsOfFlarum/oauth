<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Tests\unit\Providers;

use Flarum\Forum\Auth\Registration;
use FoF\OAuth\Errors\AuthenticationException;
use FoF\OAuth\Providers\GitHub;
use GuzzleHttp\Psr7\Response;
use League\OAuth2\Client\Provider\Github as GitHubClient;
use League\OAuth2\Client\Provider\GithubResourceOwner;
use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;

class GitHubTest extends ProviderTestCase
{
    /**
     * @param mixed $emails Decoded body of GET /user/emails
     */
    private function client(mixed $emails): GitHubClient
    {
        $client = $this->getMockBuilder(GitHubClient::class)
            ->setConstructorArgs([['clientId' => 'test', 'clientSecret' => 'test', 'redirectUri' => 'http://localhost']])
            ->onlyMethods(['getResponse'])
            ->getMock();

        $client->expects($this->once())->method('getResponse')->willReturn(new Response(200, [], (string) json_encode($emails)));

        return $client;
    }

    /**
     * A provider whose client was already built, as during the OAuth callback.
     */
    private function provider(mixed $emails): GitHub
    {
        $provider = new GitHub($this->settings());
        (new ReflectionProperty(GitHub::class, 'provider'))->setValue($provider, $this->client($emails));

        return $provider;
    }

    private function owner(?string $publicEmail): GithubResourceOwner
    {
        return new GithubResourceOwner(['id' => 1, 'login' => 'user', 'email' => $publicEmail]);
    }

    private function suggest(?string $publicEmail, array $emails): Registration
    {
        $registration = new Registration();

        $this->provider($emails)->suggestions($registration, $this->owner($publicEmail), 'token');

        return $registration;
    }

    // getProviderVerifiedEmail()

    #[Test]
    public function verified_email_is_primary_verified_address(): void
    {
        $email = $this->provider([
            ['email' => 'other@example.com', 'primary' => false, 'verified' => true],
            ['email' => 'primary@example.com', 'primary' => true, 'verified' => true],
        ])->getProviderVerifiedEmail($this->owner('public@example.com'), 'token');

        $this->assertSame('primary@example.com', $email);
    }

    #[Test]
    public function no_verified_email_when_primary_is_unverified(): void
    {
        $email = $this->provider([
            ['email' => 'primary@example.com', 'primary' => true, 'verified' => false],
            ['email' => 'other@example.com', 'primary' => false, 'verified' => true],
        ])->getProviderVerifiedEmail($this->owner('primary@example.com'), 'token');

        $this->assertNull($email);
    }

    #[Test]
    public function no_verified_email_when_flags_are_not_booleans(): void
    {
        $email = $this->provider([
            ['email' => 'primary@example.com', 'primary' => 'true', 'verified' => 1],
        ])->getProviderVerifiedEmail($this->owner(null), 'token');

        $this->assertNull($email);
    }

    #[Test]
    public function no_verified_email_for_unexpected_response_shape(): void
    {
        $this->assertNull($this->provider(['message' => 'Not Found'])->getProviderVerifiedEmail($this->owner(null), 'token'));
        $this->assertNull($this->provider(null)->getProviderVerifiedEmail($this->owner(null), 'token'));
    }

    #[Test]
    public function client_is_built_on_demand_when_not_yet_created(): void
    {
        $client = $this->client([['email' => 'primary@example.com', 'primary' => true, 'verified' => true]]);

        $provider = $this->getMockBuilder(GitHub::class)
            ->setConstructorArgs([$this->settings()])
            ->onlyMethods(['provider'])
            ->getMock();
        $provider->expects($this->once())->method('provider')->willReturn($client);

        $this->assertSame('primary@example.com', $provider->getProviderVerifiedEmail($this->owner(null), 'token'));
    }

    // suggestions()

    #[Test]
    public function public_profile_email_is_only_suggested_without_verified_primary(): void
    {
        $this->assertSuggestedEmail('victim@example.com', $this->suggest('victim@example.com', [
            ['email' => 'victim@example.com', 'primary' => true, 'verified' => false],
        ]));
    }

    #[Test]
    public function verified_primary_is_trusted_instead_of_public_email(): void
    {
        $this->assertTrustedEmail('primary@example.com', $this->suggest('public@example.com', [
            ['email' => 'public@example.com', 'primary' => false, 'verified' => false],
            ['email' => 'primary@example.com', 'primary' => true, 'verified' => true],
        ]));
    }

    #[Test]
    public function verified_primary_is_trusted_when_no_public_email(): void
    {
        $this->assertTrustedEmail('primary@example.com', $this->suggest(null, [
            ['email' => 'primary@example.com', 'primary' => true, 'verified' => true],
        ]));
    }

    #[Test]
    public function rejected_when_no_public_email_and_no_verified_primary(): void
    {
        $this->expectException(AuthenticationException::class);

        $this->suggest(null, [
            ['email' => 'primary@example.com', 'primary' => true, 'verified' => false],
        ]);
    }
}
