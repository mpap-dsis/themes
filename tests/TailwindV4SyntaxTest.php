<?php

describe('Tailwind v4 CSS Syntax', function () {
    it('validates built-in theme CSS files use Tailwind v4 syntax', function () {
        $cssFiles = glob(__DIR__ . '/../resources/css/*.css');

        foreach ($cssFiles as $cssFile) {
            $content = file_get_contents($cssFile);

            // Must use @import "tailwindcss" (v4 syntax)
            expect($content)->toContain('@import "tailwindcss"');

            // Must NOT use @tailwind directives (v3 syntax)
            expect($content)->not->toContain('@tailwind base');
            expect($content)->not->toContain('@tailwind components');
            expect($content)->not->toContain('@tailwind utilities');
        }
    });

    it('validates CSS stub uses Tailwind v4 syntax', function () {
        $stubContent = file_get_contents(__DIR__ . '/../stubs/ThemeCss.stub');

        // Must use @import "tailwindcss" (v4 syntax)
        expect($stubContent)->toContain('@import "tailwindcss"');

        // Must use @source directive (v4 syntax)
        expect($stubContent)->toContain('@source');

        // Must NOT use @tailwind directives (v3 syntax)
        expect($stubContent)->not->toContain('@tailwind base');
        expect($stubContent)->not->toContain('@tailwind components');
        expect($stubContent)->not->toContain('@tailwind utilities');
    });

    it('validates PostCSS config stub uses Tailwind v4 plugin', function () {
        $stubContent = file_get_contents(__DIR__ . '/../stubs/ThemePostcssConfig.stub');

        // Must use @tailwindcss/postcss (v4)
        expect($stubContent)->toContain('@tailwindcss/postcss');

        // Must NOT use old plugins (v3)
        expect($stubContent)->not->toContain('postcss-import');
        expect($stubContent)->not->toContain('tailwindcss/nesting');
        expect($stubContent)->not->toContain('autoprefixer');
    });

    it('validates package postcss.config.cjs uses Tailwind v4', function () {
        $configContent = file_get_contents(__DIR__ . '/../postcss.config.cjs');

        // Must use @tailwindcss/postcss (v4)
        expect($configContent)->toContain('@tailwindcss/postcss');

        // Must NOT use old plugins (v3)
        expect($configContent)->not->toContain('postcss-import');
        expect($configContent)->not->toContain('tailwindcss/nesting');
        expect($configContent)->not->toContain('autoprefixer');
    });

    it('validates package.json has Tailwind v4 dependencies', function () {
        $packageJson = json_decode(file_get_contents(__DIR__ . '/../package.json'), true);

        $devDeps = $packageJson['devDependencies'] ?? [];

        // Must have Tailwind v4 packages
        expect($devDeps)->toHaveKey('tailwindcss');
        expect($devDeps)->toHaveKey('@tailwindcss/cli');
        expect($devDeps)->toHaveKey('@tailwindcss/postcss');

        // Version must be ^4.x
        expect($devDeps['tailwindcss'])->toMatch('/^\^4\./');
        expect($devDeps['@tailwindcss/cli'])->toMatch('/^\^4\./');
        expect($devDeps['@tailwindcss/postcss'])->toMatch('/^\^4\./');
    });
});

describe('Tailwind v4 CSS Structure', function () {
    it('validates @source directives are present in theme CSS', function () {
        $cssFiles = glob(__DIR__ . '/../resources/css/*.css');

        foreach ($cssFiles as $cssFile) {
            $content = file_get_contents($cssFile);

            // Must have @source directive for content scanning
            expect($content)->toContain('@source');
        }
    });

    it('validates theme CSS imports Filament index.css for Filament 4', function () {
        $cssFiles = glob(__DIR__ . '/../resources/css/*.css');

        foreach ($cssFiles as $cssFile) {
            $content = file_get_contents($cssFile);

            // Must import Filament's index.css (Filament 4 structure)
            expect($content)->toContain('filament/filament/resources/css/index.css');

            // Must NOT import theme.css directly (causes double tailwind import)
            expect($content)->not->toContain('filament/filament/resources/css/theme.css');
        }
    });

    it('validates theme CSS has dark variant directive for Filament 4', function () {
        $cssFiles = glob(__DIR__ . '/../resources/css/*.css');

        foreach ($cssFiles as $cssFile) {
            $content = file_get_contents($cssFile);

            // Must have @variant dark directive for dark mode support
            expect($content)->toContain('@variant dark');
        }
    });

    it('validates theme CSS does not use deprecated !important syntax', function () {
        $cssFiles = glob(__DIR__ . '/../resources/css/*.css');

        foreach ($cssFiles as $cssFile) {
            $content = file_get_contents($cssFile);

            // Must NOT use "!important" as separate keyword (Tailwind v3 syntax)
            // Should use "!" prefix instead (e.g., !p-4 instead of p-4 !important)
            expect($content)->not->toMatch('/@apply[^;]*\s+!important/');
        }
    });
});
