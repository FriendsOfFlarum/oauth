<?php


namespace FoF\OAuth;


use Flarum\Forum\Auth\Registration;
use Flarum\Settings\SettingsRepositoryInterface;
use FoF\OAuth\Errors\AuthenticationException;
use League\OAuth2\Client\Provider\AbstractProvider;

abstract class Provider
{
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    // Provider data

    abstract public function name(): string;

    abstract public function link(): string;

    abstract public function package(): string;

    abstract public function fields(): array;

    abstract public function available(): bool;

    public function icon(): string {
        return "fab fa-{$this->name()}";
    }


    // Controller options

    public function provider(string $redirectUri): AbstractProvider {
        //
    }

    public function options(): array {
        return [];
    }

    public function suggestions(Registration $registration, $user, string $token) {
        //
    }


    // Helpers

    public function enabled() {
        $enabled = $this->settings->get("fof-oauth.{$this->name()}");

        return $enabled && $this->available();
    }

    protected function getSetting($key): string
    {
        return $this->settings->get("fof-oauth.{$this->name()}.{$key}");
    }

    protected function verifyEmail($email)
    {
        if (!$email || empty($email)) {
            throw new AuthenticationException('invalid_email');
        }
    }
}
