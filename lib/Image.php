<?php

/**
 * @prettier
 */

namespace Fefi\Image;

use Kirby\Toolkit\A;
use Kirby\Toolkit\Obj;
use Kirby\Toolkit\V;

class Image
{
    /**
     * @var array THUMB_OPTIONS valid keys for kirby thumb method.
     */
    public const THUMB_OPTIONS = [
        'autoOrient',
        'crop',
        'blur',
        'grayscale',
        'height',
        'quality',
        'width',
    ];

    /**
     * Generated placeholder image url
     *
     * @param \Kirby\Cms\File $image
     * @param array $options
     * @return string
     */
    public static function getPlaceholder($image, $options): string
    {
        $placeholderOptions = kirby()->option('femundfilou.image-snippet.placeholder');
        if ($options['ratio'] && V::num($options['ratio'])) {
            $height = floor($placeholderOptions['width'] * $options['ratio']);
        } else {
            $height = $image->dimensions()->fitWidth($placeholderOptions['width'], true)->height();
        }
        return $image->thumb([
            'width' => $placeholderOptions['width'],
            'height' => $height,
            'quality' => $placeholderOptions['quality'],
            'blur' => $placeholderOptions['blur']
        ])->url();
    }

    /**
     * Check if Focus plugin is installed
     *
     * @return string srcset Method name
     */
    public static function getSrcsetMethod(): string
    {
        return class_exists("Flokosiol\Focus") ? 'focusSrcset' : 'srcset';
    }

    /**
     * Get options valid for kirby thumbs
     *
     * @param array $options
     * @return array
     */
    public static function getThumbOptions(array $options): array
    {
        return A::without($options, array_keys(A::without($options, self::THUMB_OPTIONS)));
    }

    /**
     * Generate srcset
     * @param \Kirby\Cms\File|\Kirby\Filesystem\Asset $image Image
     * @param array $dimensions Width or Width and Height, e.g. [400, 600] or [[400, 300], [600, 450]]
     * @param array $formats Array of image formats
     * @param float $ratio Aspect Ratio
     */
    public static function getSrcsets(\Kirby\Cms\File|\Kirby\Filesystem\Asset $image, array $options): array
    {
        $options = array_merge(kirby()->option('femundfilou.image-snippet.defaults'), $options);
        $srcsets = [];
        $thumboptions = self::getThumbOptions($options);
        foreach ($options['formats'] as $format) {
            $srcset = [];
            foreach ($options['dimensions'] as $dimension) {
                // Check for [['width'=> 300, 'height' => 200]]
                if ($dimension && is_array($dimension) && A::isAssociative($dimension)) {
                    $width = A::get($dimension, 'width');
                    $height = A::get($dimension, 'height');
                    if (!$width) :
                        throw new \Exception('Width missing.');
                    endif;
                    if (!$height) :
                        throw new \Exception('Height missing.');
                    endif;
                    // width and height given
                    $srcset[$dimension['width'] . 'w'] = array_merge($thumboptions, ['width' => $width, 'height' => $height, 'crop' => true, 'format' => $format]);
                    // [400]
                } else {
                    if (!V::integer($dimension)) :
                        throw new \Exception('Width needs to be an integer.');
                    endif;
                    if ($options['ratio'] && V::num($options['ratio'])) {
                        $height = floor($dimension * $options['ratio']);
                    } else {
                        $height = $image->dimensions()->fitWidth($dimension, true)->height();
                    }
                    $srcset["$dimension" . 'w'] = array_merge([
                        'width' => $dimension,
                        'height' => $height,
                        'crop' => true,
                        'format' => $format,
                    ], $thumboptions);
                }
            }

            $srcsets[$format] = $srcset;
        }

        return $srcsets;
    }
    /**
     * Convert Image to ImageInterface to be used in Javascript
     *
     * @param \Kirby\Cms\File|\Kirby\Filesystem\Asset $image
     * @param array $dimensions Set dimensions for srcset. Possible values are an array of only width [300, 500] or width and height [['width'=> 300, 'height' => 150], ['width' => 500, 'height' => 250]]
     * @param array $options
     * @return \Kirby\Toolkit\Obj
     */
    public static function getImageInterface(\Kirby\Cms\File|\Kirby\Filesystem\Asset $image, array $options): \Kirby\Toolkit\Obj
    {
        $method = self::getSrcsetMethod($image);
        $options = array_merge(kirby()->option('femundfilou.image-snippet.defaults'), $options);
        $srcsetOptions = self::getSrcsets($image, $options);
        $sources = [];
        $thumboptions = self::getThumbOptions($options);
        $urlThumbOptions = array_merge($thumboptions, [
            'width' => $image->dimensions()->width(),
            'height' => $options['ratio'] && V::num($options['ratio']) ? floor($image->dimensions()->width() * $options['ratio']) : $image->dimensions()->fitWidth($image->dimensions()->width(), true)->height(),
            'crop' => true,
        ]);

        foreach ($srcsetOptions as $format => $srcset) {
            $sources[] = ['type' => "image/$format", 'srcset' => $image->$method($srcset)];
        }

        $object = new Obj([
            'width' => $image->dimensions()->width(),
            'height' => $image->dimensions()->height(),
            'url' => $image->thumb($urlThumbOptions)->url(),
            'alt' => $image->alt()->escape()->value() ?? $image->filename(),
            'filename' => $image->filename(),
            'placeholder' => self::getPlaceholder($image, $options),
            'sources' => $sources,
            'x' => $image->focusPercentageX() ?? 0,
            'y' => $image->focusPercentageY() ?? 0,
            'objectFit' => $image->objectfit() && $image->objectfit()->isNotEmpty() ? $image->objectfit()->value() : 'cover',
        ]);
        return $object;
    }
}
