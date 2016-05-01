<?php

namespace Minhbang\Status;

use Minhbang\Kit\Extensions\BaseServiceProvider;
use Illuminate\Foundation\AliasLoader;

/**
 * Class ServiceProvider
 *
 * @package Minhbang\Ebook
 */
class ServiceProvider extends BaseServiceProvider
{
    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->app['status'] = $this->app->share(
            function () {
                return new Status();
            }
        );
        // add Category alias
        $this->app->booting(
            function () {
                AliasLoader::getInstance()->alias('Status', Facade::class);
            }
        );
    }
}
