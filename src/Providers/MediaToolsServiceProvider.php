<?php

namespace TinyPixel\Acorn\Media\Providers;

use Roots\Acorn\Application;
use Roots\Acorn\ServiceProvider;
use TinyPixel\Acorn\Media\RemoteImageImport;

class MediaServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('tinypixel.media.remote', function ($app) {
            return new RemoteImageImport($app);
        });
    }

    public function boot()
    {
        $image = 'https://tinypixel.dev/app/uploads/2019/03/divider-tpc-large.png';

        /*
        $this->app->make('Media.remoteimages')->import($image, [
            'title'       => 'United States History of Mass Incarceration',
            'caption'     => 'United States History of Mass Incarceration Caption',
            'altText'     => 'Alt Text for image',
            'description' => 'This is a description of this image',
        ])->download();
        */
    }
}
