<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Providers\Custom\Flarum;

use League\OAuth2\Client\Provider\GenericResourceOwner;

/**
 * A user of the provider forum, as described by its UserInfo endpoint.
 */
class FlarumResourceOwner extends GenericResourceOwner
{
    /**
     * The longest subject whose identifier fits the 100 character `login_providers.identifier` column, after the
     * `@` and the 16 character forum key.
     */
    public const MAX_SUBJECT_LENGTH = 83;

    /**
     * @param string $forumKey Identifies the provider forum, see {@see FlarumProvider::forumKey()}.
     */
    public function __construct(array $response, protected string $forumKey)
    {
        parent::__construct($response, 'sub');
    }

    /**
     * The user's ID on the provider forum (`sub`).
     */
    public function getSubject(): string
    {
        return (string) ($this->response['sub'] ?? '');
    }

    /**
     * The identifier the account is linked with: the subject, qualified by the forum it belongs to.
     *
     * A subject is only the user's ID on the provider forum, so after the forum URL is changed to another forum, the
     * same subject is a different person there. Qualifying it keeps them from logging in to the accounts linked
     * through the previous forum.
     */
    public function getId(): string
    {
        return $this->getSubject().'@'.$this->forumKey;
    }
}
