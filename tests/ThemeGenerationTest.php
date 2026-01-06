<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    // Clean up any test files
    $testThemePath = app_path('Filament/Themes/TestTheme.php');
    $testCssPath = resource_path('css/filament/admin/themes/test-theme.css');

    if (File::exists($testThemePath)) {
        File::delete($testThemePath);
    }
    if (File::exists($testCssPath)) {
        File::delete($testCssPath);
    }
});

afterEach(function () {
    // Clean up test files
    $testThemePath = app_path('Filament/Themes/TestTheme.php');
    $testCssPath = resource_path('css/filament/admin/themes/test-theme.css');

    if (File::exists($testThemePath)) {
        File::delete($testThemePath);
    }
    if (File::exists($testCssPath)) {
        File::delete($testCssPath);
    }
});

describe('Theme CSS Stub Generation', function () {
    it('generates CSS stub with correct Tailwind v4 imports', function () {
        $stubPath = __DIR__ . '/../stubs/ThemeCss.stub';
        $stubContent = file_get_contents($stubPath);

        // Check v4 syntax
        expect($stubContent)
            ->toContain('@import "tailwindcss"')
            ->toContain('@source')
            ->toContain('filament/filament/resources/css/index.css')
            ->toContain('@variant dark')
            ->not->toContain('@tailwind')
            ->not->toContain('filament/filament/resources/css/theme.css');
    });

    it('generates PostCSS config stub with Tailwind v4 plugin', function () {
        $stubPath = __DIR__ . '/../stubs/ThemePostcssConfig.stub';
        $stubContent = file_get_contents($stubPath);

        expect($stubContent)
            ->toContain('@tailwindcss/postcss')
            ->not->toContain('postcss-import')
            ->not->toContain('tailwindcss/nesting')
            ->not->toContain('autoprefixer');
    });

    it('generates Tailwind config stub compatible with v4', function () {
        $stubPath = __DIR__ . '/../stubs/ThemeTailwindConfig.stub';
        $stubContent = file_get_contents($stubPath);

        // v4 tailwind config is simpler - just theme extend
        expect($stubContent)
            ->toContain('export default')
            ->toContain('theme')
            ->toContain('extend')
            ->not->toContain('content:') // v4 uses @source instead
            ->not->toContain('plugins:'); // v4 doesn't require plugins array
    });
});

describe('Theme Stub Structure', function () {
    it('has all required stub files', function () {
        $stubsPath = __DIR__ . '/../stubs';

        expect(File::exists($stubsPath . '/Theme.stub'))->toBeTrue();
        expect(File::exists($stubsPath . '/ThemeCss.stub'))->toBeTrue();
        expect(File::exists($stubsPath . '/ThemePostcssConfig.stub'))->toBeTrue();
        expect(File::exists($stubsPath . '/ThemeTailwindConfig.stub'))->toBeTrue();
    });

    it('Theme stub has correct namespace placeholder', function () {
        $stubContent = file_get_contents(__DIR__ . '/../stubs/Theme.stub');

        expect($stubContent)
            ->toContain('namespace {{ namespace }}')
            ->toContain('class {{ class }}')
            ->toContain('implements CanModifyPanelConfig, Theme');
    });
});
