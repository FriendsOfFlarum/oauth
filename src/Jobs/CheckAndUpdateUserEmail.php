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

use Carbon\Carbon;
use Flarum\Mail\Job\SendRawEmailJob;
use Flarum\User\EmailToken;
use Flarum\User\LoginProvider;
use Flarum\User\User;
use Flarum\User\UserValidator;
use FoF\OAuth\Mail\ProviderEmailChangeNotice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Validation\ValidationException;

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

    public function handle(UserValidator $validator, Dispatcher $events, Queue $queue, ProviderEmailChangeNotice $notice)
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

            try {
                $validator->assertValid([
                    'email' => $this->providedEmail,
                ]);
            } catch (ValidationException $e) {
                // E.g. the address already belongs to another account. Skip the sync rather than let the
                // error escape: with a sync queue it would otherwise fail the OAuth login itself.
                return;
            }

            if ($this->changeIsPending($user)) {
                return;
            }

            // Use core's email change flow: a confirmation link goes to the new address, and the account's
            // email only changes once it is followed.
            $user->requestEmailChange($this->providedEmail);

            foreach ($user->releaseEvents() as $event) {
                $events->dispatch($event);
            }

            // Tell the current address, so the owner learns of a change made through a compromised provider account.
            $message = $notice->compose($user, $this->providerName, $this->providedEmail);

            $queue->push(new SendRawEmailJob($message['to'], $message['subject'], $message['body']));
        }
    }

    /**
     * Whether a confirmation for this address is still outstanding, so repeat logins don't send it again.
     * Core accepts an email token for less than a day.
     */
    protected function changeIsPending(User $user): bool
    {
        return EmailToken::query()
            ->where('user_id', $user->id)
            ->where('email', $this->providedEmail)
            ->where('created_at', '>', Carbon::now()->subDay())
            ->exists();
    }
}
