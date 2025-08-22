# Kirby Image Snippet

Snippet + Helper methods for handling images in Kirby 4+.


## Installation

### Install via composer

To use the snippet and helper methods you need to require this package via composer.

```php
composer require femundfilou/kirby-image-snippet
```

## Usage

### Snippet

```php
<?php snippet('image', ['image' => $page->images()->first()]); ?>
```

### Field methods

```php
<div data-cover='<?= $page->images()->first()->toImageInterface()->toJson(); ?>' data-images='<?= $page->images()->toImageInterfaces()->toJson(); ?>'></div>
```

## Options

You can override the default options on a per image basis.

```php
<?php snippet('image', ['image' => $page->images()->first(), 'ratio' => 1, 'dimensions' => [200]]); ?>
```

```php
<div data-cover='<?= $page->images()->first()->toImageInterface(['ratio' => 1, 'dimensions' => [200]])->toJson(); ?>'></div>
```

## Lazy Loading

You can activate lazy loading by defining `lazy => true` inside the options. The snippet will then add `data-lazyload` to the `picture` element and add the srcset as `data-srcset` to each `<source>`. You then have to use your own javascript implementation to lazyload the images.

## Configuration

### Default

You can override the default configuration in your websites `site/config.php`.

```php
<?php

return [
  'femundfilou.image-snippet' => [
    'placeholder' => [
        'width' => 50,
        'blur' => 10,
        'quality' => 50
    ],
    'ratio' => 0,
    'quality' => 80,
    'blur' => 0,
    'grayscale' => false,
    'lazy' => false,
    'formats' => ['webp', 'jpg'],
    'dimensions' => [400, 800, 1140],
    'sizes' => '100vw'
  ]
];

?>
```
