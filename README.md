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

Returns the attachment ID (if upload was a success).
