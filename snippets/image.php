<?php

/**
 * Image snippet with picture element and lazy loading
 * @param \Kirby\Cms\File|\Kirby\Filesystem\Asset $image Image file object
 * @param array $options Image processing options
 */

use Fefi\Image\Image;
use Kirby\Toolkit\V;

// Get defaults
$defaults = kirby()->option('femundfilou.image-snippet.defaults');

// Merge options with defaults
$options = array_merge($defaults, [
    'lazy' => $lazy ?? $defaults['lazy'] ?? false,
    'ratio' => $ratio ?? $defaults['ratio'] ?? null,
    'quality' => $quality ?? $defaults['quality'] ?? null,
    'grayscale' => $grayscale ?? $defaults['grayscale'] ?? false,
    'blur' => $blur ?? $defaults['blur'] ?? null,
    'formats' => $formats ?? $defaults['formats'] ?? [],
    'dimensions' => $dimensions ?? $defaults['dimensions'] ?? null,
    'sizes' => $sizes ?? $defaults['sizes'] ?? '100vw'
]);

// Get alt text
$alt = $alt ?? (method_exists($image, 'alt') && is_callable([$image, 'alt'])
    ? $image->alt()->escape()->or($image->name())
    : $image->name());

// Generate image data
$placeholder = Image::getPlaceholder($image, $options);
$srcsets = Image::getSrcsets($image, $options);

// Calculate height based on ratio or image height
$height = $options['ratio'] && V::num($options['ratio'])
    ? $image->width() * $options['ratio']
    : $image->height();
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
        data-src="<?= $placeholder ?>"
        alt="<?= $alt ?>"
        <?= $attrs ?? '' ?> />
</picture>