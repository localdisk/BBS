<?php

namespace Localdisk\BBS;

use Illuminate\Support\ServiceProvider;
use GuzzleHttp\Client;

/**
 * BBSServiceProvider
 *
 * @author localdisk
 */
class BBSServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('bbs', function ($app) {
            return new BBSManager($app, new Client(['defaults' => ['headers' => ['User-Agent' => 'yarana.io']]]));
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['bbs'];
    }

}
