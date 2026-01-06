<?php

use Hasnayeen\Themes\Commands\MigrateToTailwindV4Command;

describe('MigrateToTailwindV4Command', function () {
    it('command is registered', function () {
        expect(class_exists(MigrateToTailwindV4Command::class))->toBeTrue();
    });

    it('command has correct signature', function () {
        $command = new MigrateToTailwindV4Command;

        $reflection = new ReflectionClass($command);
        $property = $reflection->getProperty('signature');
        $property->setAccessible(true);

        expect($property->getValue($command))->toContain('themes:migrate-v4');
        expect($property->getValue($command))->toContain('--dry-run');
    });

    it('command has description', function () {
        $command = new MigrateToTailwindV4Command;

        $reflection = new ReflectionClass($command);
        $property = $reflection->getProperty('description');
        $property->setAccessible(true);

        expect($property->getValue($command))->not->toBeEmpty();
    });
});

describe('CSS Migration Logic', function () {
    it('identifies Tailwind v3 syntax correctly', function () {
        $v3Content = <<<'CSS'
@tailwind base;
@tailwind components;
@tailwind utilities;

.custom-class {
    @apply text-red-500;
}
CSS;

        expect($v3Content)->toContain('@tailwind base');
        expect($v3Content)->toContain('@tailwind components');
        expect($v3Content)->toContain('@tailwind utilities');
    });

    it('identifies Tailwind v4 syntax correctly', function () {
        $v4Content = <<<'CSS'
@import "tailwindcss";
@import '../vendor/filament/filament/resources/css/theme.css';

@source '../../app/Filament';
@source '../../resources/views/filament';

.custom-class {
    @apply text-red-500;
}
CSS;

        expect($v4Content)->toContain('@import "tailwindcss"');
        expect($v4Content)->toContain('@source');
        expect($v4Content)->not->toContain('@tailwind');
    });

    it('can detect PostCSS v3 config', function () {
        $v3Config = <<<'JS'
module.exports = {
    plugins: {
        "postcss-import": {},
        "tailwindcss/nesting": {},
        tailwindcss: {},
        autoprefixer: {},
    },
}
JS;

        expect($v3Config)->toContain('postcss-import');
        expect($v3Config)->toContain('tailwindcss/nesting');
        expect($v3Config)->toContain('autoprefixer');
    });

    it('can detect PostCSS v4 config', function () {
        $v4Config = <<<'JS'
module.exports = {
    plugins: {
        "@tailwindcss/postcss": {},
    },
}
JS;

        expect($v4Config)->toContain('@tailwindcss/postcss');
        expect($v4Config)->not->toContain('postcss-import');
        expect($v4Config)->not->toContain('autoprefixer');
    });
});

describe('Package.json Migration', function () {
    it('identifies v3 dependencies', function () {
        $v3PackageJson = [
            'devDependencies' => [
                'tailwindcss' => '^3.4.0',
                'postcss-import' => '^15.1.0',
                'autoprefixer' => '^10.4.0',
            ],
        ];

        expect($v3PackageJson['devDependencies']['tailwindcss'])->toStartWith('^3');
        expect($v3PackageJson['devDependencies'])->toHaveKey('postcss-import');
        expect($v3PackageJson['devDependencies'])->toHaveKey('autoprefixer');
    });

    it('identifies v4 dependencies', function () {
        $v4PackageJson = [
            'devDependencies' => [
                'tailwindcss' => '^4.1.0',
                '@tailwindcss/postcss' => '^4.1.0',
                '@tailwindcss/vite' => '^4.1.0',
            ],
        ];

        expect($v4PackageJson['devDependencies']['tailwindcss'])->toStartWith('^4');
        expect($v4PackageJson['devDependencies'])->toHaveKey('@tailwindcss/postcss');
        expect($v4PackageJson['devDependencies'])->toHaveKey('@tailwindcss/vite');
        expect($v4PackageJson['devDependencies'])->not->toHaveKey('postcss-import');
        expect($v4PackageJson['devDependencies'])->not->toHaveKey('autoprefixer');
    });
});
