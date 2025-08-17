<?php

namespace Tests\Stubs;

use Kirby\Cms\File;
use Kirby\Image\Dimensions;

class TestFile extends File
{
    private $testDimensions;
    private $testWidth;
    private $testHeight;

    public function __construct(int $width = 800, int $height = 600)
    {
        $this->testDimensions = new Dimensions($width, $height);
        $this->testWidth = $width;
        $this->testHeight = $height;

        // Don't call parent constructor to avoid complex setup
    }

    public function dimensions(): Dimensions
    {
        return $this->testDimensions;
    }

    public function width(): int
    {
        return $this->testWidth;
    }

    public function height(): int
    {
        return $this->testHeight;
    }

    public function name(): string
    {
        return 'test-image.jpg';
    }

    public function filename(): string
    {
        return 'test-image.jpg';
    }

    public function thumb(array|string|null $options = null): \Kirby\Cms\FileVersion|\Kirby\Cms\File|\Kirby\Filesystem\Asset
    {
        // Return self for testing purposes - in a real scenario this would create a FileVersion
        return $this;
    }

    public function url(): string
    {
        return 'http://test.com/thumb.jpg';
    }

    public function srcset(array|string|null $sizes = null): ?string
    {
        return 'test-srcset';
    }
}
