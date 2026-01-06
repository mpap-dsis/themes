<?php

namespace Hasnayeen\Themes\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MigrateToTailwindV4Command extends Command
{
    protected $description = 'Migrate existing themes to Tailwind CSS v4 syntax';

    protected $signature = 'themes:migrate-v4 {--dry-run : Show what would be changed without making changes}';

    protected Filesystem $filesystem;

    protected array $migratedFiles = [];

    protected array $skippedFiles = [];

    public function __construct()
    {
        parent::__construct();
        $this->filesystem = new Filesystem;
    }

    public function handle(): int
    {
        $this->components->info('Migrating themes to Tailwind CSS v4...');

        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->components->warn('Running in dry-run mode. No changes will be made.');
        }

        // Step 1: Find all theme CSS files
        $this->migrateThemeCssFiles($isDryRun);

        // Step 2: Update postcss.config.js/cjs if exists
        $this->migratePostcssConfig($isDryRun);

        // Step 3: Update package.json dependencies
        $this->migratePackageJson($isDryRun);

        // Step 4: Update tailwind.config.js if exists
        $this->migrateTailwindConfig($isDryRun);

        // Summary
        $this->printSummary($isDryRun);

        if (! $isDryRun && count($this->migratedFiles) > 0) {
            $this->components->info('');
            $this->components->warn('Next steps:');
            $this->components->bulletList([
                'Run `npm install` to install new dependencies',
                'Run `npm run build` to rebuild your themes',
                'Test your themes in the browser',
            ]);
        }

        return static::SUCCESS;
    }

    protected function migrateThemeCssFiles(bool $isDryRun): void
    {
        $cssPatterns = [
            resource_path('css/filament/*/themes/*.css'),
            resource_path('css/filament/*/*/themes/*.css'),
        ];

        foreach ($cssPatterns as $pattern) {
            $files = glob($pattern);

            foreach ($files as $file) {
                $this->migrateCssFile($file, $isDryRun);
            }
        }
    }

    protected function migrateCssFile(string $path, bool $isDryRun): void
    {
        if (! $this->filesystem->exists($path)) {
            return;
        }

        $content = $this->filesystem->get($path);
        $originalContent = $content;

        // Check if already using v4 syntax
        if (Str::contains($content, '@import "tailwindcss"') && Str::contains($content, '@source')) {
            $this->skippedFiles[] = [
                'path' => $path,
                'reason' => 'Already using Tailwind v4 syntax',
            ];

            return;
        }

        $changes = [];

        // Replace @tailwind directives with @import "tailwindcss"
        if (Str::contains($content, '@tailwind')) {
            $content = preg_replace(
                '/@tailwind\s+base;\s*\n?/',
                '',
                $content
            );
            $content = preg_replace(
                '/@tailwind\s+components;\s*\n?/',
                '',
                $content
            );
            $content = preg_replace(
                '/@tailwind\s+utilities;\s*\n?/',
                '',
                $content
            );

            // Add v4 import at the beginning
            $content = "@import \"tailwindcss\";\n" . $content;
            $changes[] = 'Replaced @tailwind directives with @import "tailwindcss"';
        }

        // Ensure Filament theme import exists
        if (! Str::contains($content, 'filament/filament/resources/css/theme.css')) {
            // Add Filament theme import after tailwindcss import
            $content = preg_replace(
                '/(@import "tailwindcss";\n)/',
                "$1@import '../../../../../vendor/filament/filament/resources/css/theme.css';\n",
                $content
            );
            $changes[] = 'Added Filament theme CSS import';
        }

        // Add @source directives if not present
        if (! Str::contains($content, '@source')) {
            // Determine relative path based on file location
            $relativePath = $this->calculateRelativePath($path);

            $sourceDirectives = "\n@source '{$relativePath}app/Filament';\n@source '{$relativePath}resources/views/filament';\n";

            // Add after imports
            if (preg_match('/(@import[^;]+;\n)+/', $content, $matches)) {
                $lastImport = $matches[0];
                $content = str_replace($lastImport, $lastImport . $sourceDirectives, $content);
            } else {
                $content .= $sourceDirectives;
            }
            $changes[] = 'Added @source directives for content scanning';
        }

        // Clean up multiple blank lines
        $content = preg_replace('/\n{3,}/', "\n\n", $content);

        if ($content !== $originalContent) {
            if (! $isDryRun) {
                $this->filesystem->put($path, $content);
            }

            $this->migratedFiles[] = [
                'path' => $path,
                'changes' => $changes,
            ];
        }
    }

    protected function calculateRelativePath(string $cssPath): string
    {
        // Calculate how many levels up we need to go from the CSS file to the project root
        $resourcesPath = resource_path();
        $relativeToCss = str_replace($resourcesPath, '', dirname($cssPath));
        $depth = substr_count($relativeToCss, DIRECTORY_SEPARATOR) + 1;

        return str_repeat('../', $depth + 1);
    }

    protected function migratePostcssConfig(bool $isDryRun): void
    {
        $configPaths = [
            base_path('postcss.config.js'),
            base_path('postcss.config.cjs'),
            base_path('postcss.config.mjs'),
        ];

        foreach ($configPaths as $configPath) {
            if (! $this->filesystem->exists($configPath)) {
                continue;
            }

            $content = $this->filesystem->get($configPath);
            $originalContent = $content;

            // Check if already using v4
            if (Str::contains($content, '@tailwindcss/postcss')) {
                $this->skippedFiles[] = [
                    'path' => $configPath,
                    'reason' => 'Already using @tailwindcss/postcss',
                ];

                return;
            }

            $changes = [];

            // Check for old plugins and replace
            if (Str::contains($content, 'postcss-import') ||
                Str::contains($content, 'tailwindcss/nesting') ||
                Str::contains($content, ['tailwindcss:', "'tailwindcss'", '"tailwindcss"']) ||
                Str::contains($content, 'autoprefixer')) {

                // Create new v4 config
                $newContent = $this->getV4PostcssConfig($configPath);

                if (! $isDryRun) {
                    $this->filesystem->put($configPath, $newContent);
                }

                $this->migratedFiles[] = [
                    'path' => $configPath,
                    'changes' => ['Replaced with Tailwind v4 PostCSS configuration'],
                ];

                return;
            }
        }
    }

    protected function getV4PostcssConfig(string $path): string
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        if ($extension === 'cjs') {
            return <<<'JS'
module.exports = {
    plugins: {
        "@tailwindcss/postcss": {},
    },
}
JS;
        }

        return <<<'JS'
export default {
    plugins: {
        '@tailwindcss/postcss': {},
    },
}
JS;
    }

    protected function migratePackageJson(bool $isDryRun): void
    {
        $packagePath = base_path('package.json');

        if (! $this->filesystem->exists($packagePath)) {
            return;
        }

        $content = $this->filesystem->get($packagePath);
        $package = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->skippedFiles[] = [
                'path' => $packagePath,
                'reason' => 'Invalid JSON',
            ];

            return;
        }

        $changes = [];
        $devDeps = $package['devDependencies'] ?? [];

        // Check if already on v4
        if (isset($devDeps['tailwindcss']) && Str::startsWith($devDeps['tailwindcss'], '^4')) {
            $this->skippedFiles[] = [
                'path' => $packagePath,
                'reason' => 'Already using Tailwind v4',
            ];

            return;
        }

        // Update/add Tailwind v4 packages
        $v4Packages = [
            'tailwindcss' => '^4.1.0',
            '@tailwindcss/postcss' => '^4.1.0',
            '@tailwindcss/vite' => '^4.1.0',
        ];

        // Remove old packages
        $oldPackages = ['postcss-import', 'autoprefixer'];
        foreach ($oldPackages as $oldPackage) {
            if (isset($devDeps[$oldPackage])) {
                unset($package['devDependencies'][$oldPackage]);
                $changes[] = "Removed {$oldPackage}";
            }
        }

        // Add new packages
        foreach ($v4Packages as $name => $version) {
            if (! isset($devDeps[$name]) || ! Str::startsWith($devDeps[$name], '^4')) {
                $package['devDependencies'][$name] = $version;
                $changes[] = "Added/updated {$name} to {$version}";
            }
        }

        if (count($changes) > 0) {
            if (! $isDryRun) {
                $newContent = json_encode($package, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
                $this->filesystem->put($packagePath, $newContent);
            }

            $this->migratedFiles[] = [
                'path' => $packagePath,
                'changes' => $changes,
            ];
        }
    }

    protected function migrateTailwindConfig(bool $isDryRun): void
    {
        $configPaths = [
            base_path('tailwind.config.js'),
            base_path('tailwind.config.cjs'),
            base_path('tailwind.config.mjs'),
            base_path('tailwind.config.ts'),
        ];

        foreach ($configPaths as $configPath) {
            if (! $this->filesystem->exists($configPath)) {
                continue;
            }

            $content = $this->filesystem->get($configPath);

            // v4 doesn't need content array - it uses @source in CSS
            // v4 doesn't need plugins for most cases
            // We just inform the user they may need to simplify their config

            if (Str::contains($content, 'content:') || Str::contains($content, 'plugins:')) {
                $this->components->warn("Found Tailwind config at {$configPath}");
                $this->components->bulletList([
                    'Tailwind v4 uses @source directives in CSS instead of content array',
                    'Most plugins are now built-in or replaced with CSS features',
                    'Consider simplifying your tailwind.config.js',
                    'See: https://tailwindcss.com/docs/upgrade-guide',
                ]);
            }
        }
    }

    protected function printSummary(bool $isDryRun): void
    {
        $this->newLine();

        if (count($this->migratedFiles) > 0) {
            $action = $isDryRun ? 'Would migrate' : 'Migrated';
            $this->components->info("{$action} " . count($this->migratedFiles) . ' file(s):');

            foreach ($this->migratedFiles as $file) {
                $this->components->twoColumnDetail(
                    $file['path'],
                    '<fg=green>' . implode(', ', $file['changes']) . '</>'
                );
            }
        }

        if (count($this->skippedFiles) > 0) {
            $this->newLine();
            $this->components->info('Skipped ' . count($this->skippedFiles) . ' file(s):');

            foreach ($this->skippedFiles as $file) {
                $this->components->twoColumnDetail(
                    $file['path'],
                    '<fg=yellow>' . $file['reason'] . '</>'
                );
            }
        }

        if (count($this->migratedFiles) === 0 && count($this->skippedFiles) === 0) {
            $this->components->info('No theme files found to migrate.');
        }
    }
}
