<?php

namespace TinyPixel\Acorn\MediaTools\Providers;

use Roots\Acorn\Application;
use Roots\Acorn\ServiceProvider;
use TinyPixel\Acorn\MediaTools\RemoteImageImport;

class MediaToolsServiceProvider
{
    public function register()
    {
        $this->app->singleton('mediatools.remoteimages', function ($app) {
            return new RemoteImageImport($app);
        });
    }

    public function boot()
    {
        $remoteUpload = $this->app->make('mediatools.remoteimages');

        $remoteUpload->import('https://scontent-sea1-1.cdninstagram.com/vp/0e515e757e054fcb61c1f3a4039b6ce6/5DECA144/t51.2885-15/e35/67830568_733915700398961_6645683865155736407_n.jpg?_nc_ht=scontent-sea1-1.cdninstagram.com', [
            'title'       => 'United States History of Mass Incarceration',
            'caption'     => 'United States History of Mass Incarceration Caption',
            'altText'     => 'Alt Text for image',
            'description' => 'This is a description of this image',
        ]);
    }
}
