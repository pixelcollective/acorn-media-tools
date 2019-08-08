## acorn-media-tools

Work-in-progress.

Currently only includes one utility, which provides a simple API for uploading to the WordPress media library from site/plugin code.

More to come.

## Usage example

```php
$image = 'https://tinypixel.dev/app/uploads/2019/03/divider-tpc-large.png';

$this->app->make('tinypixel.media.remote')->import($image, [
    'title'       => 'An image',
    'caption'     => 'This image came from far away',
    'altText'     => 'An image example',
    'description' => 'Uploaded natch.',
])->download();
```

## Bug Reports

Should you discover a bug in AcornMediaTools please [open an issue](https://github.com/pixelcollective/acorn-media-tools/issues).

## Contributing

Contributing, whether it be through PRs, reporting an issue, or suggesting an idea is encouraged and appreciated.

All contributors absolutely must strictly adhere to our [Code of Conduct](https://github.com/pixelcollective/acorn-media-tools/blob/master/LICENSE.md).

## License

AcornMediaTools is provided under the [MIT License](https://github.com/pixelcollective/acorn-media-tools/blob/master/LICENSE.md).
