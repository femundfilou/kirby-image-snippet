# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Commands

### Code Quality & Testing
- `composer run fix` - Run PHP CS Fixer to format code according to project standards
- `composer run test` - Run all tests using Pest PHP
- `composer run test:unit` - Run only unit tests
- `composer run test:feature` - Run only feature tests
- `pnpm release` - Create a new release using release-it with conventional changelog

### Package Management
- Use `pnpm` as the package manager (specified in package.json)
- `composer install` - Install PHP dependencies
- `pnpm install` - Install Node.js dependencies

## Architecture Overview

This is a Kirby CMS plugin that provides image processing functionality with responsive image generation and lazy loading support.

### Core Components

**Plugin Structure:**
- `index.php` - Main plugin registration with Kirby CMS, defines file/field methods and snippet
- `lib/Image.php` - Core image processing class with static methods for srcset generation
- `snippets/image.php` - Template snippet that renders responsive `<picture>` elements

**Key Classes:**
- `Fefi\Image\Image` - Main image processing class that handles:
  - Placeholder image generation with blur/quality options
  - Multi-format srcset generation (webp, jpg) 
  - Responsive dimension calculations with ratio support
  - Image interface object creation for JSON serialization

**Plugin Methods Added to Kirby:**
- `$file->toImageInterface()` - Convert single file to image interface object
- `$files->toImageInterfaces()` - Convert file collection to image interfaces
- `$field->toImageInterface()` - Convert field's first file to image interface
- `$field->toImageInterfaces()` - Convert field's files to image interfaces
- `$asset->toImageInterface()` - Convert asset to image interface

### Configuration

Plugin options are defined in `femundfilou.image-snippet` namespace with:
- `placeholder` settings (width, blur, quality)
- `defaults` for image processing (ratio, quality, formats, dimensions, sizes, lazy loading)

### Code Style

Uses PHP CS Fixer with PSR-12 standards plus additional rules defined in `.php-cs-fixer.dist.php`. The configuration excludes the `kirby/` directory and enforces strict formatting rules.

### Dependencies

- Requires Kirby CMS 4.0+ or 5.0+
- Uses Kirby's built-in image processing and thumb generation
- No external image processing dependencies

### Testing

Tests are implemented using Pest PHP framework with PHPUnit as the underlying test runner. The test suite includes:

- **Unit Tests** (`tests/Unit/`) - Test individual Image class methods in isolation
- **Feature Tests** (`tests/Feature/`) - Test plugin integration with Kirby CMS

Key testing features:
- Mock objects for File and Asset classes to test image processing logic
- Ratio calculation tests covering various aspect ratios (square, landscape, portrait)
- Srcset generation testing with multiple formats and dimensions
- PHPUnit XML configuration with proper source coverage
- Pest configuration with Kirby CMS instance setup for integration testing