# Acorn Media Library Tools

## Service: library.upload

Example usage:

```php
$image = 'https://cdn.pixabay.com/photo/2018/07/31/22/08/lion-3576045__340.jpg';

app()->make('library.upload')->import($image, [
    'title'       => 'Title (optional)',
    'caption'     => 'Caption (optional)',
    'altText'     => 'Alt text (optional)',
    'description' => 'Description (optional)',
])->download();
```

## Bug Reports

Should you discover a bug in AcornMediaTools please [open an issue](https://github.com/pixelcollective/acorn-media-tools/issues).

## Contributing

Contributing, whether it be through PRs, reporting an issue, or suggesting an idea is encouraged and appreciated.

All contributors absolutely must strictly adhere to our [Code of Conduct](https://github.com/pixelcollective/acorn-media-tools/blob/master/LICENSE.md).

## License

AcornMediaTools is provided under the [MIT License](https://github.com/pixelcollective/acorn-media-tools/blob/master/LICENSE.md).
