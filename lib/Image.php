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
     * Normalizes ratio input to float value
     * Supports string format like "16/9", "4:3", and numeric values
     */
    private static function normalizeRatio($ratio): ?float
    {
        if (!$ratio) {
            return null;
        }

        // Handle Kirby Field objects
        if (is_object($ratio) && method_exists($ratio, 'value') && is_callable([$ratio, 'value'])) {
            return self::normalizeRatio($ratio->value());
        }

        // Handle string ratios like "16/9" or "4:3"
        if (is_string($ratio)) {
            // Support both "/" and ":" separators
            if (str_contains($ratio, '/')) {
                $parts = explode('/', $ratio, 2);
            } elseif (str_contains($ratio, ':')) {
                $parts = explode(':', $ratio, 2);
            } else {
                // Try to parse as numeric string
                $parts = [(float)$ratio];
            }

            if (count($parts) === 2) {
                $width = (float)trim($parts[0]);
                $height = (float)trim($parts[1]);

                if ($width > 0 && $height > 0) {
                    return $width / $height;
                }
            } elseif (count($parts) === 1 && is_numeric($parts[0])) {
                $value = (float)$parts[0];
                return $value > 0 ? $value : null;
            }
        }

        // Handle numeric values
        if (is_numeric($ratio)) {
            $value = (float)$ratio;
            return $value > 0 ? $value : null;
        }



        return null;
    }


    /**
     * Generates placeholder data URI (ThumbHash if available, fallback otherwise)
     */
    public static function getPlaceholder(File|Asset $image, array $options): string
    {
        $kirby = kirby();
        $id = self::getId($image);
        $cache = $kirby->cache(Options::NAMESPACE);

        if (($cacheData = $cache->get($id)) !== null) {
            return $cacheData;
        }

        $ratio = self::normalizeRatio($options['ratio']) ?? $image->ratio();
        $maxSize = Options::placeholderOption('sampleMaxSize', 100);

        // Handle division by zero and invalid ratios
        if (!$ratio || $ratio <= 0) {
            $ratio = $image->width() / max($image->height(), 1);
        }

        $height = round($image->height() > $image->width() ? $maxSize : $maxSize / $ratio);
        $width = round($image->width() > $image->height() ? $maxSize : $maxSize * $ratio);

        // Ensure minimum dimensions
        $width = max($width, 1);
        $height = max($height, 1);

        // Try ThumbHash if available, otherwise use fallback
        if (class_exists('\Thumbhash\Thumbhash')) {
            $dataUri = self::generateThumbHashPlaceholder($image, $width, $height);
        } else {
            $dataUri = self::generateFallbackPlaceholder($image, $width, $height);
        }

        $cache->set($id, $dataUri);
        return $dataUri;
    }

    /**
     * Generates ThumbHash placeholder when library is available
     */
    private static function generateThumbHashPlaceholder(File|Asset $image, int $width, int $height): string
    {
        $thumbOptions = [
            'width' => $width,
            'height' => $height,
            'crop' => true,
            'quality' => 70,
        ];

        try {
            $imageData = $image->thumb($thumbOptions)->read();
            $imageResource = imagecreatefromstring($imageData);

            if ($imageResource === false) {
                return self::generateFallbackPlaceholder($image, $width, $height);
            }

            $imageHeight = imagesy($imageResource);
            $imageWidth = imagesx($imageResource);
            $pixels = [];

            for ($y = 0; $y < $imageHeight; $y++) {
                for ($x = 0; $x < $imageWidth; $x++) {
                    $colorIndex = imagecolorat($imageResource, $x, $y);
                    $color = imagecolorsforindex($imageResource, $colorIndex);
                    $alpha = 255 - ceil($color['alpha'] * (255 / 127));
                    $pixels[] = $color['red'];
                    $pixels[] = $color['green'];
                    $pixels[] = $color['blue'];
                    $pixels[] = $alpha;
                }
            }

            $hashArray = \Thumbhash\Thumbhash::RGBAToHash($imageWidth, $imageHeight, $pixels);
            $decodedImage = \Thumbhash\Thumbhash::hashToRGBA($hashArray);

            $transparent = array_reduce(array_chunk($decodedImage['rgba'], 4), function ($carry, $item) {
                return $carry || $item[3] < 255;
            }, false);

            $dataUri = \Thumbhash\Thumbhash::rgbaToDataURL($decodedImage['w'], $decodedImage['h'], $decodedImage['rgba']);
            $blurRadius = Options::placeholderOption('blurRadius', 1);

            if ($blurRadius !== 0) {
                $svg = self::createBlurredSvg($dataUri, $decodedImage['w'], $decodedImage['h'], $blurRadius, $transparent);
                $dataUri = self::svgToUri($svg);
            }

            return $dataUri;
        } catch (\Exception) {
            return self::generateFallbackPlaceholder($image, $width, $height);
        }
    }

    /**
     * Generates fallback placeholder when ThumbHash is not available
     */
    private static function generateFallbackPlaceholder(File|Asset $image, int $width, int $height): string
    {
        $thumbOptions = [
            'width' => $width,
            'height' => $height,
            'crop' => true,
            'quality' => Options::placeholderOption('quality', 20),
        ];

        try {
            // Read the thumbnail data directly to create a base64 data URI
            $thumbData = $image->thumb($thumbOptions)->read();
            $base64 = base64_encode($thumbData);
            $mimeType = $image->thumb($thumbOptions)->mime();
            $dataUri = "data:{$mimeType};base64,{$base64}";

            $blurRadius = Options::placeholderOption('blurRadius', 1);

            if ($blurRadius !== 0) {
                $svg = self::createBlurredSvg($dataUri, $width, $height, $blurRadius);
                return self::svgToUri($svg);
            }

            // Return the thumbnail as base64 data URI wrapped in SVG
            return self::svgToUri(
                '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 ' . $width . ' ' . $height . '">' .
                    '<image width="100%" height="100%" xlink:href="' . $dataUri . '"></image>' .
                    '</svg>'
            );
        } catch (\Exception) {
            // Ultimate fallback to solid color
            $fallbackSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $width . ' ' . $height . '"><rect width="100%" height="100%" fill="#f0f0f0"/></svg>';
            return self::svgToUri($fallbackSvg);
        }
    }

    /**
     * Creates SVG with blurred image using data URI
     */
    private static function createBlurredSvg(string $dataUri, float $width, float $height, float $blurRadius, bool $transparent = true): string
    {
        $svgHeight = number_format($height, 2, '.', '');
        $svgWidth = number_format($width, 2, '.', '');

        $alphaFilter = '';
        if (!$transparent) {
            $alphaFilter = <<<EOD
            <feComponentTransfer>
                <feFuncA type="discrete" tableValues="1 1"></feFuncA>
            </feComponentTransfer>
            EOD;
        }
        // Overshoot to avoid blurry edges
        return <<<EOD
        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 {$svgWidth} {$svgHeight}">
          <filter id="b" color-interpolation-filters="sRGB">
            <feGaussianBlur stdDeviation="{$blurRadius}"></feGaussianBlur>
            {$alphaFilter}
          </filter>
          <image filter="url(#b)" x="-2.5%" y="-2.5%" width="105%" height="105%" href="{$dataUri}"></image>
        </svg>
        EOD;
    }

    /**
     * Returns the uuid for a File, or its mediaHash for Assets
     */
    private static function getId(File|Asset $file): string
    {
        if ($file instanceof Asset) {
            return $file->mediaHash();
        }

        return $file->uuid()?->id() ?? $file->id();
    }


    /**
     * Converts SVG to optimized URI-encoded string
     */
    private static function svgToUri(string $data): string
    {
        $data = preg_replace('/\s+/', ' ', $data);
        $data = preg_replace('/> </', '><', $data);
        $data = rawurlencode($data);
        $data = str_replace(['%2F', '%3A', '%3D'], ['/', ':', '='], $data);

        return 'data:image/svg+xml;charset=utf-8,' . $data;
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
        $options = Options::merge($options);
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
     * Generates JPG-only srcset for img fallback
     */
    public static function getJpgSrcset(File|Asset $image, array $options): array
    {
        $imageDimensions = clone $image->dimensions();
        $options = Options::merge($options);
        $thumbOptions = self::getThumbOptions($options);
        $srcset = [];

        foreach ($options['dimensions'] as $dimension) {
            $srcset = self::processDimension($dimension, $imageDimensions, $options, $thumbOptions, 'jpg', $srcset);
        }

        return $srcset;
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

        $normalizedRatio = self::normalizeRatio($options['ratio']);
        if ($normalizedRatio && $normalizedRatio > 0) {
            // Calculate target dimensions based on the desired width and aspect ratio
            $targetWidth = min($dimension, $imageDimensions->width()); // Don't exceed original width
            $targetHeight = (int)floor($targetWidth / $normalizedRatio);

            // If calculated height exceeds original height, fit by height instead
            if ($targetHeight > $imageDimensions->height()) {
                $targetHeight = $imageDimensions->height();
                $targetWidth = (int)floor($targetHeight * $normalizedRatio);
            }

            $width = $targetWidth;
            $height = $targetHeight;
        } else {
            $width = $dimension;
            $height = $imageDimensions->fitWidth($dimension, true)->height();
        }

        $srcset["$dimension" . 'w'] = array_merge([
            'width' => $width,
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
        $options = Options::merge($options);
        $srcsetOptions = self::getSrcsets($image, $options);
        $thumbOptions = self::getThumbOptions($options);

        $normalizedRatio = self::normalizeRatio($options['ratio']);
        if ($normalizedRatio && $normalizedRatio > 0) {
            // Calculate target dimensions based on original image width and aspect ratio
            $targetWidth = $imageDimensions->width();
            $targetHeight = (int)floor($targetWidth / $normalizedRatio);

            // If calculated height exceeds original height, fit by height instead
            if ($targetHeight > $imageDimensions->height()) {
                $targetHeight = $imageDimensions->height();
                $targetWidth = (int)floor($targetHeight * $normalizedRatio);
            }

            $width = $targetWidth;
            $height = $targetHeight;
        } else {
            $width = $imageDimensions->width();
            $height = $imageDimensions->fitWidth($imageDimensions->width(), true)->height();
        }

        $urlThumbOptions = array_merge($thumbOptions, [
            'width' => $width,
            'height' => $height,
            'crop' => true,
        ]);

        return new Obj([
            'width' => $width,
            'height' => $height,
            'url' => $image->thumb($urlThumbOptions)->url(),
            'alt' => method_exists($image, 'alt') && is_callable([$image, 'alt'])
                ? $image->alt()->escape()->value() ?? $image->name()
                : $image->name(),
            'filename' => $image->filename(),
            'placeholder' => self::getPlaceholder($image, $options),
            'sources' => array_map(fn($format, $srcset) => [
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
