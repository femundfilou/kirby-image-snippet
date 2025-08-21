<?php

namespace Fefi\Image;

use Kirby\Cms\App;

/**
 * Options management class for the image snippet plugin
 * Centralizes option handling with proper defaults and validation
 */
class Options
{
    private const NAMESPACE = 'femundfilou.image-snippet';

    private static ?array $cachedDefaults = null;
    private static ?array $cachedPlaceholder = null;

    /**
     * Get plugin option with fallback to default
     */
    public static function option(string $key, mixed $default = null): mixed
    {
        $kirby = App::instance();
        return $kirby->option(self::NAMESPACE . '.' . $key, $default);
    }

    /**
     * Get default processing options
     */
    public static function defaults(): array
    {
        if (self::$cachedDefaults === null) {
            self::$cachedDefaults = self::option('defaults', [
                'ratio' => 0,
                'quality' => 70,
                'blur' => 0,
                'grayscale' => false,
                'lazy' => true,
                'formats' => ['avif', 'webp'],
                'dimensions' => [400, 800, 1140],
                'sizes' => '100vw'
            ]);
        }

        return self::$cachedDefaults;
    }

    /**
     * Get placeholder options
     */
    public static function placeholder(): array
    {
        if (self::$cachedPlaceholder === null) {
            self::$cachedPlaceholder = self::option('placeholder', [
                'width' => 50,
                'blur' => 10,
                'quality' => 50,
                'sampleMaxSize' => 100,
                'blurRadius' => 1
            ]);
        }

        return self::$cachedPlaceholder;
    }

    /**
     * Get merged options with defaults applied
     */
    public static function merge(array $userOptions = []): array
    {
        return array_merge(self::defaults(), $userOptions);
    }

    /**
     * Get specific placeholder option
     */
    public static function placeholderOption(string $key, mixed $default = null): mixed
    {
        $placeholderOptions = self::placeholder();
        return $placeholderOptions[$key] ?? $default;
    }

    /**
     * Get specific default option
     */
    public static function defaultOption(string $key, mixed $default = null): mixed
    {
        $defaultOptions = self::defaults();
        return $defaultOptions[$key] ?? $default;
    }

    /**
     * Normalize snippet options from variables
     */
    public static function normalizeSnippetOptions(array $vars): array
    {
        $defaults = self::defaults();

        return [
            'lazy' => $vars['lazy'] ?? $defaults['lazy'],
            'ratio' => $vars['ratio'] ?? $defaults['ratio'],
            'quality' => $vars['quality'] ?? $defaults['quality'],
            'grayscale' => $vars['grayscale'] ?? $defaults['grayscale'],
            'blur' => $vars['blur'] ?? $defaults['blur'],
            'formats' => $vars['formats'] ?? $defaults['formats'],
            'dimensions' => $vars['dimensions'] ?? $defaults['dimensions'],
            'sizes' => $vars['sizes'] ?? $defaults['sizes']
        ];
    }

    /**
     * Clear cached options (useful for testing)
     */
    public static function clearCache(): void
    {
        self::$cachedDefaults = null;
        self::$cachedPlaceholder = null;
    }
}
