<?php

use Fefi\Image\Image;

describe('Image Ratio Calculations', function () {

    test('calculates correct height with square aspect ratio (1:1)', function () {
        $file = mockFile(800, 600);
        $options = ['ratio' => 1.0]; // 1:1 aspect ratio (square)

        $imageInterface = Image::getImageInterface($file, $options);

        // 1:1 aspect ratio: height = width / 1.0 = 800, but limited by original height of 600
        // So it fits by height: width = 600 * 1.0 = 600
        expect($imageInterface->height)->toBe(600); // Limited by original height
        expect($imageInterface->width)->toBe(600); // Adjusted to maintain 1:1 ratio
    });

    test('calculates correct height with landscape aspect ratio (16:9)', function () {
        $file = mockFile(1600, 900);
        $options = ['ratio' => 16 / 9]; // 16:9 aspect ratio (landscape)

        $imageInterface = Image::getImageInterface($file, $options);

        // 16:9 aspect ratio: height = width / (16/9) = 1600 * 9/16 = 900
        expect($imageInterface->height)->toBe(900);
        expect($imageInterface->width)->toBe(1600);
    });

    test('calculates correct height with portrait aspect ratio (9:16)', function () {
        $file = mockFile(900, 1600);
        $options = ['ratio' => 9 / 16]; // 9:16 aspect ratio (portrait)

        $imageInterface = Image::getImageInterface($file, $options);

        // 9:16 aspect ratio: height = width / (9/16) = 900 * 16/9 = 1600
        expect($imageInterface->height)->toBe(1600);
        expect($imageInterface->width)->toBe(900);
    });

    test('uses original dimensions when no ratio is provided', function () {
        $file = mockFile(1200, 800);
        $options = ['ratio' => 0]; // No ratio

        $imageInterface = Image::getImageInterface($file, $options);

        expect($imageInterface->width)->toBe(1200);
        expect($imageInterface->height)->toBe(800);
    });

    test('handles placeholder generation with ratio', function () {
        $file = mockFile(800, 600);
        $options = ['ratio' => 1.0];

        $placeholder = Image::getPlaceholder($file, $options);

        expect($placeholder)->toBeString();
        expect($placeholder)->not()->toBeEmpty();
    });

    test('generates srcsets with aspect ratio applied to dimensions', function () {
        $file = mockFile(1600, 900);
        $options = [
            'ratio' => 16 / 9, // 16:9 aspect ratio (landscape)
            'dimensions' => [400, 800, 1200],
            'formats' => ['webp', 'jpg']
        ];

        $srcsets = Image::getSrcsets($file, $options);

        expect($srcsets)->toHaveKey('webp');
        expect($srcsets)->toHaveKey('jpg');
        expect($srcsets['webp'])->toHaveKey('400w');
        expect($srcsets['webp'])->toHaveKey('800w');
        expect($srcsets['webp'])->toHaveKey('1200w');

        // Check that the calculated heights are correct (width / aspectRatio, floored)
        expect($srcsets['webp']['400w']['height'])->toBe(225); // floor(400 / (16/9))
        expect($srcsets['webp']['400w']['width'])->toBe(400);
        expect($srcsets['webp']['800w']['height'])->toBe(450); // floor(800 / (16/9))
        expect($srcsets['webp']['1200w']['height'])->toBe(675); // floor(1200 / (16/9))
    });

    test('handles fractional aspect ratio calculations correctly', function () {
        $file = mockFile(1000, 750);
        $options = ['ratio' => 2 / 3]; // 2:3 aspect ratio (portrait)

        $imageInterface = Image::getImageInterface($file, $options);

        // 2:3 aspect ratio: height = 1000 / (2/3) = 1500, but limited by original height of 750
        // So it fits by height: width = 750 * (2/3) = 500
        expect($imageInterface->height)->toBe(750); // Limited by original height
        expect($imageInterface->width)->toBe(500); // Adjusted to maintain 2:3 ratio
    });

    test('works with Asset objects as well as File objects', function () {
        $asset = mockAsset(800, 600);
        $options = ['ratio' => 1.0]; // 1:1 aspect ratio (square)

        $imageInterface = Image::getImageInterface($asset, $options);

        // 1:1 aspect ratio: height = width / 1.0 = 800, but limited by original height of 600
        // So it fits by height: width = 600 * 1.0 = 600
        expect($imageInterface->height)->toBe(600); // Limited by original height
        expect($imageInterface->width)->toBe(600); // Adjusted to maintain 1:1 ratio
    });

    test('preserves other thumb options when applying ratio', function () {
        $file = mockFile(800, 600);
        $options = [
            'ratio' => 1.0,
            'quality' => 90,
            'blur' => 5,
            'grayscale' => true
        ];

        $srcsets = Image::getSrcsets($file, $options);

        // Verify that dimensions array options include the thumb options
        expect($srcsets['webp']['400w'])->toHaveKey('quality');
        expect($srcsets['webp']['400w'])->toHaveKey('blur');
        expect($srcsets['webp']['400w'])->toHaveKey('grayscale');
        expect($srcsets['webp']['400w']['quality'])->toBe(90);
        expect($srcsets['webp']['400w']['blur'])->toBe(5);
        expect($srcsets['webp']['400w']['grayscale'])->toBe(true);
    });

    test('handles zero and negative ratios gracefully', function () {
        $file = mockFile(800, 600);

        // Zero ratio should use original dimensions (falsy check)
        $options = ['ratio' => 0];
        $imageInterface = Image::getImageInterface($file, $options);
        expect($imageInterface->height)->toBe(600);

        // Negative ratio should be ignored and use original dimensions
        $options = ['ratio' => -1];
        $imageInterface = Image::getImageInterface($file, $options);
        expect($imageInterface->height)->toBe(600);
    });

    test('calculates correct height with aspect ratio 1.7 (landscape)', function () {
        $file = mockFile(1700, 1000);
        $options = ['ratio' => 1.7]; // 1.7:1 aspect ratio (landscape)

        $imageInterface = Image::getImageInterface($file, $options);

        // height = width / aspectRatio = 1700 / 1.7 = 1000
        expect($imageInterface->height)->toBe(1000);
        expect($imageInterface->width)->toBe(1700);
    });

    test('calculates correct height with aspect ratio 2.0 (landscape)', function () {
        $file = mockFile(1600, 800);
        $options = ['ratio' => 2.0]; // 2:1 aspect ratio (landscape)

        $imageInterface = Image::getImageInterface($file, $options);

        // height = width / aspectRatio = 1600 / 2.0 = 800
        expect($imageInterface->height)->toBe(800);
        expect($imageInterface->width)->toBe(1600);
    });

    test('calculates correct height with aspect ratio 16:9 (landscape)', function () {
        $file = mockFile(1920, 1080);
        $options = ['ratio' => 16 / 9]; // 16:9 aspect ratio (landscape)

        $imageInterface = Image::getImageInterface($file, $options);

        // height = width / aspectRatio = 1920 / (16/9) = 1080
        expect($imageInterface->height)->toBe(1080);
        expect($imageInterface->width)->toBe(1920);
    });

    test('generates srcsets with landscape aspect ratios applied to dimensions', function () {
        $file = mockFile(1700, 1000);
        $options = [
            'ratio' => 1.7, // 1.7:1 aspect ratio (landscape)
            'dimensions' => [400, 800, 1200],
            'formats' => ['webp', 'jpg']
        ];

        $srcsets = Image::getSrcsets($file, $options);

        expect($srcsets)->toHaveKey('webp');
        expect($srcsets)->toHaveKey('jpg');

        // Check that the calculated heights are correct (width / aspectRatio, floored)
        expect($srcsets['webp']['400w']['height'])->toBe(235); // floor(400 / 1.7)
        expect($srcsets['webp']['400w']['width'])->toBe(400);
        expect($srcsets['webp']['800w']['height'])->toBe(470); // floor(800 / 1.7)
        expect($srcsets['webp']['1200w']['height'])->toBe(705); // floor(1200 / 1.7)
    });

    test('handles placeholder generation with landscape aspect ratio', function () {
        $file = mockFile(1700, 1000);
        $options = ['ratio' => 1.7];

        $placeholder = Image::getPlaceholder($file, $options);

        expect($placeholder)->toBeString();
        expect($placeholder)->not()->toBeEmpty();
    });

    test('handles bounds checking with portrait aspect ratio', function () {
        $file = mockFile(900, 600); // Original is landscape
        $options = ['ratio' => 9 / 16]; // 9:16 aspect ratio (portrait)

        $imageInterface = Image::getImageInterface($file, $options);

        // 9:16 would need height = 900 / (9/16) = 1600, but original is only 600 tall
        // So it should fit by height: width = 600 * (9/16) = 337.5 -> 337
        expect($imageInterface->height)->toBe(600); // Limited by original height
        expect($imageInterface->width)->toBe(337); // Adjusted width to maintain 9:16 ratio
    });

    test('handles srcset dimensions larger than original image', function () {
        $file = mockFile(1024, 767); // Original image size
        $options = [
            'ratio' => 9 / 16, // 9:16 aspect ratio (portrait)
            'dimensions' => [400, 800, 1140, 1440, 2000, 2560], // Some larger than original
            'formats' => ['jpg']
        ];

        $srcsets = Image::getSrcsets($file, $options);

        // Smaller dimensions should maintain aspect ratio
        expect($srcsets['jpg']['400w']['width'])->toBe(400);
        expect($srcsets['jpg']['400w']['height'])->toBe(711); // floor(400 / (9/16))

        // 800w exceeds what 9:16 ratio allows with 767px height, so it gets limited
        expect($srcsets['jpg']['800w']['width'])->toBe(431); // floor(767 * (9/16))
        expect($srcsets['jpg']['800w']['height'])->toBe(767); // Limited by original height

        // Dimensions larger than original width should be capped
        expect($srcsets['jpg']['2000w']['width'])->toBe(431); // Capped by original bounds
        expect($srcsets['jpg']['2000w']['height'])->toBe(767); // Limited by original height

        expect($srcsets['jpg']['2560w']['width'])->toBe(431); // Same as 2000w, capped by bounds
        expect($srcsets['jpg']['2560w']['height'])->toBe(767); // Limited by original height
    });

});
