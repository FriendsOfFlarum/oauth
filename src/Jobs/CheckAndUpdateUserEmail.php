<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\OAuth\Jobs;

use Flarum\User\LoginProvider;
use Flarum\User\User;
use Flarum\User\UserValidator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;

class CheckAndUpdateUserEmail implements ShouldQueue
{
    use Queueable;

    use SerializesModels;

    /**
     * @var string
     */
    public $providerName;

    /**
     * @var mixed
     */
    public $identifier;

    /**
     * @var string|null
     */
    public $providedEmail;

    /**
     * @param string      $providerName
     * @param mixed       $identifier
     * @param string|null $providedEmail
     */
    public function __construct(string $providerName, $identifier, ?string $providedEmail)
    {
        $this->providerName = $providerName;
        $this->identifier = $identifier;
        $this->providedEmail = $providedEmail;
    }

    public function handle(UserValidator $validator, Dispatcher $events)
    {
        $provider = LoginProvider::where('provider', $this->providerName)->where('identifier', $this->identifier)->first();

        if (!$provider) {
            return;
        }

        /** @var User|null $user */
        $user = User::find($provider->user_id);

        if ($user === null) {
            return;
        }

        if (!empty($this->providedEmail) && $user->email !== $this->providedEmail) {
            $validator->setUser($user);

            $validator->assertValid([
                'email' => $this->providedEmail,
            ]);

            $user->changeEmail($this->providedEmail);

            $user->save();
            foreach ($user->releaseEvents() as $event) {
                $events->dispatch($event);
            }
        }
    }
}
