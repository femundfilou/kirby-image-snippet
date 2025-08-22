<?php

/**
 * Image snippet with picture element and lazy loading
 * @param \Kirby\Cms\File|\Kirby\Filesystem\Asset $image Image file object
 * @param array $options Image processing options
 */

use Fefi\Image\Image;
use Fefi\Image\Options;

// Merge options with defaults
$options = Options::normalizeSnippetOptions([
    'lazy' => $lazy ?? null,
    'ratio' => $ratio ?? null,
    'quality' => $quality ?? null,
    'grayscale' => $grayscale ?? null,
    'blur' => $blur ?? null,
    'formats' => $formats ?? null,
    'dimensions' => $dimensions ?? null,
    'sizes' => $sizes ?? null
]);

// Get alt text
$alt = $alt ?? (method_exists($image, 'alt') && is_callable([$image, 'alt'])
    ? $image->alt()->escape()->or($image->name())
    : $image->name());

// Generate image data
$placeholder = Image::getPlaceholder($image, $options);
$srcsets = Image::getSrcsets($image, $options);
$jpgSrcset = Image::getJpgSrcset($image, $options);

$imageInterface = Image::getImageInterface($image, $options);
$height = $imageInterface->height;
?>

<picture <?= $options['lazy'] ? 'data-lazyload' : '' ?>>
    <?php foreach ($options['formats'] as $format): ?>
        <source
            type="image/<?= $format ?>"
            <?= e($options['lazy'], 'data-') ?>srcset="<?= $image->srcset($srcsets[$format]) ?>" sizes="<?= $options['sizes'] ?>" />
    <?php endforeach ?>
    <img
        <?= $options['lazy'] ? 'loading="lazy" decoding="async" fetchpriority="auto"' : '' ?>
        width="<?= $image->width() ?>"
        height="<?= $height ?>"
        src="<?= $placeholder ?>"
        <?= e($options['lazy'], 'data-') ?>srcset="<?= $image->srcset($jpgSrcset) ?>"
        sizes="<?= $options['sizes'] ?>"
        alt="<?= $alt ?>"
        <?= $attrs ?? '' ?> />
</picture>