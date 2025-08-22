<?php

/**
 * @prettier
 */
@require_once __DIR__ . '/lib/Options.php';
@require_once __DIR__ . '/lib/Image.php';

use Fefi\Image\Image;
use Kirby\Toolkit\Collection;

Kirby\Cms\App::plugin('femundfilou/image-snippet', [
    'options' => [
        'cache' => true
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
        'toImageInterfaces' => function (Kirby\Content\Field $field, array $options = []) {
            $images = new Collection();
            foreach ($field->toFiles() as $image) :
                $imageObject = Image::getImageInterface($image, $options);
                $images->append($imageObject);
            endforeach;
            return $images;
        },
        'toImageInterface' => function (Kirby\Content\Field $field, array $options = []) {
            return Image::getImageInterface($field->toFiles()->first(), $options);
        },
    ],
    'assetMethods' => [
        'toImageInterface' => function (array $options = []) {
            return Image::getImageInterface($this, $options);
        },
    ],
]);
