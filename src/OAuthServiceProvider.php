<?php


namespace FoF\OAuth;


use Illuminate\Support\ServiceProvider;

class OAuthServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->tag([
            Providers\Discord::class,
            Providers\Facebook::class,
            Providers\GitHub::class,
            Providers\GitLab::class,
            Providers\Twitter::class
        ], 'fof-oauth.providers');

        $this->app->singleton('fof-oauth.providers.forum', $this->map('toForumPayload', true));
        $this->app->singleton('fof-oauth.providers.admin', $this->map('toAdminPayload'));
    }

    protected function map($method, $checkIfEnabled = false) {
        return function () use ($checkIfEnabled, $method) {
            $providers = $this->app->tagged('fof-oauth.providers');

            return array_map(function ($provider) use ($checkIfEnabled, $method) {
                if ($checkIfEnabled && !$provider->enabled()) {
                    return null;
                }

                return $provider->$method();
            }, $providers);
        };
    }
}
