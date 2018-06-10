<?php

namespace Jeffersonmartin\Releasehat;

use Illuminate\Support\ServiceProvider;

class ReleasehatServiceProvider extends ServiceProvider
{

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\ComposerUpdate::class,
                Commands\EnvoyDeploy::class,
                Commands\GitRelease::class,
            ]);
        }
    }

    public function register()
    {
        $this->registerConfig();
    }

    protected function registerConfig()
    {

        //
        // Merge config file into application config
        //
        // This allows users to override any module configuration values with
        // their own values in the application config file.
        //
        $this->mergeConfigFrom(
            __DIR__.'/Config/config.php', 'releasehat'
        );

        if (! $this->app->runningInConsole()) {
            return;
        }

        //
        // Publish config file to application
        //
        // Once the `php artisan vendor::publish` command is run, you can use
        // the configuration file values `$value = config('releasehat.option');`
        //
        $this->publishes([
            __DIR__.'/Config/config.php' => config_path('releasehat.php'),
        ], 'releasehat');

    }

}
