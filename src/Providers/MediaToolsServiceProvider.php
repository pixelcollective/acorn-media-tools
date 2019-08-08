<?php

namespace TinyPixel\Acorn\MediaTools\Providers;

use Roots\Acorn\Application;
use Roots\Acorn\ServiceProvider;
use TinyPixel\Support\MimeTypes;
use TinyPixel\Acorn\MediaTools\RemoteImageImport;

class MediaToolsServiceProvider extends ServiceProvider
{
    /**
     * Register application services.
     *
     * @return void
     */
    public function register() : void
    {
        $this->app->singleton('library.upload', function ($app) {
            return new RemoteImageImport($app);
        });
    }

    /**
     * Boot application services.
     *
     * @return void
     */
    public function boot() : void
    {
        // --
    }
}
