<?php

/**
 * @prettier
 */

use Fefi\Image\Image;

$srcsetMethod = Image::getSrcsetMethod();
$defaults = kirby()->option('femundfilou.image-snippet.defaults');

$options = [];

$attrs = $attrs ?? null;

if(isset($lazy)) {
    $options['lazy'] = $lazy;
}
if(isset($ratio)) {
    $options['ratio'] = $ratio;
}
if(isset($quality)) {
    $options['quality'] = $quality;
}
if(isset($grayscale)) {
    $options['grayscale'] = $grayscale;
}
if(isset($blur)) {
    $options['blur'] = $blur;
}
if(isset($lazy)) {
    $options['lazy'] = $lazy;
}
if(isset($formats)) {
    $options['formats'] = $formats;
}
if(isset($dimensions)) {
    $options['dimensions'] = $dimensions;
}


$options = array_merge($defaults, $options);

$placeholder = Image::getPlaceholder($image, $options);

$srcsets = Image::getSrcsets($image, $options);
?>

<picture <?= $options['lazy'] ? 'data-lazyload' : '' ?>>
	<?php foreach ($options['formats'] as $format) : ?>
	<source type="image/<?= $format ?>"
		<?= e($options['lazy'], 'data-')?>srcset="<?= $image->$srcsetMethod($srcsets[$format]) ?>" />
	<?php endforeach; ?>
	<img <?= $options['lazy'] ? 'loading="lazy"' : ''; ?> width="<?= $image->width() ?>"
		height="<?= isset($options['ratio']) ? $image->width() * $options['ratio'] : $image->height() ?>"
		src="<?= $placeholder ?>" alt="<?= $image->alt()->or($image->filename()) ?>" <?= $attrs ?> />
</picture>