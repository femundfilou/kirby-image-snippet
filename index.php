<?php

/**
 * @prettier
 */
@require_once __DIR__ . '/lib/Image.php';

use Fefi\Image\Image;
use Kirby\Cms\Field;
use Kirby\Toolkit\Collection;

Kirby\Cms\App::plugin('femundfilou/image-snippet', [
    'options' => [
        'placeholder' => [
            'width' => 50,
            'blur' => 10,
            'quality' => 50
        ],
        'defaults' => [
            'ratio' => 0,
            'quality' => 80,
            'blur' => 0,
            'grayscale' => false,
            'lazy' => false,
            'formats' => ['webp', 'jpg'],
            'dimensions' => [400, 800, 1140],
        ],
    ],
    'snippets' => [
        'image' => __DIR__ . '/snippets/image.php',
    ],
    'filesMethods' => [
        'toImageInterfaces' => function (array $options = []) {
            $images = new Collection();
            foreach ($this as $image) :
                $imageObject = Image::getImageInterface($image, $options);
                $images->append($imageObject);
            endforeach;
            return $images;
        },
    ],
    'fileMethods' => [
        'toImageInterface' => function (array $options = []) {
            return Image::getImageInterface($this, $options);
        },
    ],
    'fieldMethods' => [
        'toImageInterfaces' => function (Field $field, array $options = []) {
            $images = new Collection();
            foreach ($field->toFiles() as $image) :
                $imageObject = Image::getImageInterface($image, $options);
                $images->append($imageObject);
            endforeach;
            return $images;
        },
        'toImageInterface' => function (Field $field, array $options = []) {
            return Image::getImageInterface($field->toFiles()->first(), $options);
        },
    ],
]);
