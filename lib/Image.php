<?php

namespace Fefi\Image;

use Kirby\Cms\File;
use Kirby\Filesystem\Asset;
use Kirby\Toolkit\A;
use Kirby\Toolkit\Obj;
use Kirby\Toolkit\V;

/**
 * Image processing helper class
 * @remarks Handles image transformations and srcset generation
 */
class Image
{
    private const THUMB_OPTIONS = [
        'autoOrient',
        'crop',
        'blur',
        'grayscale',
        'height',
        'quality',
        'width',
    ];

    /**
     * Generates placeholder image url
     */
    public static function getPlaceholder(File|Asset $image, array $options): string
    {
        $imageDimensions = clone $image->dimensions();
        $placeholderOptions = kirby()->option('femundfilou.image-snippet.placeholder');
        $height = $options['ratio'] && V::num($options['ratio'])
            ? floor($placeholderOptions['width'] * $options['ratio'])
            : $imageDimensions->fitWidth($placeholderOptions['width'], true)->height();

        return $image->thumb([
            'width' => $placeholderOptions['width'],
            'height' => $height,
            'quality' => $placeholderOptions['quality'],
            'blur' => $placeholderOptions['blur']
        ])->url();
    }

    /**
     * Gets options valid for kirby thumbs
     */
    private static function getThumbOptions(array $options): array
    {
        return A::without($options, array_keys(A::without($options, self::THUMB_OPTIONS)));
    }

    /**
     * Generates srcset configurations
     */
    public static function getSrcsets(File|Asset $image, array $options): array
    {
        $imageDimensions = clone $image->dimensions();
        $options = array_merge(kirby()->option('femundfilou.image-snippet.defaults'), $options);
        $thumbOptions = self::getThumbOptions($options);
        $srcsets = [];

        foreach ($options['formats'] as $format) {
            $srcset = [];
            foreach ($options['dimensions'] as $dimension) {
                $srcset = self::processDimension($dimension, $imageDimensions, $options, $thumbOptions, $format, $srcset);
            }
            $srcsets[$format] = $srcset;
        }

        return $srcsets;
    }

    /**
     * Processes single dimension for srcset
     */
    private static function processDimension(
        $dimension,
        $imageDimensions,
        array $options,
        array $thumbOptions,
        string $format,
        array $srcset
    ): array {
        if (is_array($dimension) && A::isAssociative($dimension)) {
            return self::processAssociativeDimension($dimension, $thumbOptions, $format);
        }

        if (!V::integer($dimension)) {
            throw new \Exception('Width needs to be an integer.');
        }

        $height = $options['ratio'] && V::num($options['ratio'])
            ? floor($dimension * $options['ratio'])
            : $imageDimensions->fitWidth($dimension, true)->height();

        $srcset["$dimension" . 'w'] = array_merge([
            'width' => $dimension,
            'height' => $height,
            'crop' => true,
            'format' => $format,
        ], $thumbOptions);

        return $srcset;
    }

    /**
     * Processes associative dimension array
     */
    private static function processAssociativeDimension(array $dimension, array $thumbOptions, string $format): array
    {
        $width = A::get($dimension, 'width');
        $height = A::get($dimension, 'height');

        if (!$width || !$height) {
            throw new \Exception('Width and height required.');
        }

        return [$width . 'w' => array_merge($thumbOptions, [
            'width' => $width,
            'height' => $height,
            'crop' => true,
            'format' => $format
        ])];
    }

    /**
     * Converts Image to ImageInterface
     */
    public static function getImageInterface(File|Asset $image, array $options): Obj
    {
        $imageDimensions = clone $image->dimensions();
        $options = array_merge(kirby()->option('femundfilou.image-snippet.defaults'), $options);
        $srcsetOptions = self::getSrcsets($image, $options);
        $thumbOptions = self::getThumbOptions($options);

        $height = $options['ratio'] && V::num($options['ratio'])
            ? (int)floor($imageDimensions->width() * $options['ratio'])
            : $imageDimensions->fitWidth($imageDimensions->width(), true)->height();

        $urlThumbOptions = array_merge($thumbOptions, [
            'width' => $imageDimensions->width(),
            'height' => $height,
            'crop' => true,
        ]);

        return new Obj([
            'width' => $imageDimensions->width(),
            'height' => $height,
            'url' => $image->thumb($urlThumbOptions)->url(),
            'alt' => method_exists($image, 'alt') && is_callable([$image, 'alt'])
                ? $image->alt()->escape()->value() ?? $image->name()
                : $image->name(),
            'filename' => $image->filename(),
            'placeholder' => self::getPlaceholder($image, $options),
            'sources' => array_map(fn ($format, $srcset) => [
                'type' => "image/$format",
                'srcset' => $image->srcset($srcset)
            ], array_keys($srcsetOptions), $srcsetOptions),
            'focus' => method_exists($image, 'focus') && is_callable([$image, 'focus'])
                ? $image->focus()->value() ?? 'center'
                : 'center',
            'objectFit' => method_exists($image, 'objectfit') && is_callable([$image, 'objectfit'])
                ? $image->objectfit()->value() ?? 'cover'
                : 'cover'
        ]);
    }
}
