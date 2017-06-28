<?php

namespace Minhbang\Status;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Illuminate\Foundation\AliasLoader;

/**
 * Class ServiceProvider
 *
 * @package Minhbang\Ebook
 */
class ServiceProvider extends BaseServiceProvider
{
    public function boot()
    {

    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('status', function () {
            return new Manager();
        });

        // add Status alias
        $this->app->booting(
            function () {
                AliasLoader::getInstance()->alias('Status', Facade::class);
            }
        );
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['status'];
    }
}
