<?php

/**
 * This file is the entry point for configuring test suites when running Pest tests.
 */
uses()->in('Unit');

// Isolated unit testing - create a minimal Kirby instance only once
if (!function_exists('ensureKirbyForTesting')) {
    function ensureKirbyForTesting()
    {
        if (Kirby\Cms\App::instance(null, true) === null) {
            new Kirby\Cms\App([
                'roots' => [
                    'index' => __DIR__ . '/../',
                ],
                'options' => [
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
                    ],
                ]
            ]);
        }
    }
}

// Initialize Kirby once for all tests
ensureKirbyForTesting();

// Helper function to create test file with dimensions
function mockFile(int $width = 800, int $height = 600): Tests\Stubs\TestFile
{
    return new Tests\Stubs\TestFile($width, $height);
}

// Helper function to create test asset with dimensions
function mockAsset(int $width = 800, int $height = 600): Tests\Stubs\TestAsset
{
    return new Tests\Stubs\TestAsset($width, $height);
}
