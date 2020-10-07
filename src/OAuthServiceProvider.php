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

        $this->app->singleton('fof-oauth.providers.forum', $this->map(function (Provider $provider) {
            if (!$provider->enabled()) {
                return null;
            }

            return [
                'name' => $provider->name(),
                'icon' => $provider->icon()
            ];
        }));

        $this->app->singleton('fof-oauth.providers.admin', $this->map(function (Provider $provider) {
            return [
                'name' => $provider->name(),
                'icon' => $provider->icon(),
                'link' => $provider->link(),
                'package' => $provider->package(),
                'fields' => $provider->fields(),
                'available' => $provider->available(),
            ];
        }));
    }

    protected function map(callable $cb) {
        return function () use ($cb) {
            $providers = $this->app->tagged('fof-oauth.providers');

            return array_map(function ($provider) use ($cb) {
                return $cb($provider);
            }, iterator_to_array($providers));
        };
    }
}
