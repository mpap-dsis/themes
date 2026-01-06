<?php

use Hasnayeen\Themes\Contracts\CanModifyPanelConfig;
use Hasnayeen\Themes\Contracts\HasChangeableColor;
use Hasnayeen\Themes\Contracts\HasOnlyDarkMode;
use Hasnayeen\Themes\Contracts\Theme;
use Hasnayeen\Themes\Themes;
use Hasnayeen\Themes\Themes\DefaultTheme;
use Hasnayeen\Themes\Themes\Dracula;
use Hasnayeen\Themes\Themes\Nord;
use Hasnayeen\Themes\Themes\Sunset;
use Hasnayeen\Themes\ThemesPlugin;

describe('Filament v4 Integration', function () {
    it('ThemesPlugin implements Filament Plugin contract', function () {
        $plugin = ThemesPlugin::make();

        expect($plugin)->toBeInstanceOf(\Filament\Contracts\Plugin::class);
    });

    it('can create ThemesPlugin instance', function () {
        $plugin = ThemesPlugin::make();

        expect($plugin)->toBeInstanceOf(ThemesPlugin::class);
    });

    it('plugin has correct ID', function () {
        $plugin = ThemesPlugin::make();

        expect($plugin->getId())->toBe('themes');
    });

    it('can register custom themes', function () {
        $plugin = ThemesPlugin::make()
            ->registerTheme([
                'custom' => DefaultTheme::class,
            ]);

        expect($plugin)->toBeInstanceOf(ThemesPlugin::class);
    });

    it('can set canViewThemesPage callback', function () {
        $plugin = ThemesPlugin::make()
            ->canViewThemesPage(fn () => true);

        expect($plugin)->toBeInstanceOf(ThemesPlugin::class);
    });
});

describe('Theme Contracts', function () {
    it('DefaultTheme implements required interfaces', function () {
        expect(DefaultTheme::class)
            ->toImplement(Theme::class)
            ->toImplement(HasChangeableColor::class);
    });

    it('Dracula theme implements dark mode only', function () {
        expect(Dracula::class)
            ->toImplement(Theme::class)
            ->toImplement(HasOnlyDarkMode::class);
    });

    it('Nord theme can modify panel config', function () {
        expect(Nord::class)
            ->toImplement(Theme::class)
            ->toImplement(CanModifyPanelConfig::class);
    });

    it('Sunset theme supports changeable color', function () {
        expect(Sunset::class)
            ->toImplement(Theme::class)
            ->toImplement(HasChangeableColor::class);
    });
});

describe('Built-in Themes', function () {
    it('DefaultTheme returns valid name', function () {
        expect(DefaultTheme::getName())->toBe('default');
    });

    it('DefaultTheme returns valid path', function () {
        $path = DefaultTheme::getPath();

        expect($path)->toBeString();
        expect($path)->toEndWith('.css');
    });

    it('Dracula returns valid name', function () {
        expect(Dracula::getName())->toBe('dracula');
    });

    it('Nord returns valid name', function () {
        expect(Nord::getName())->toBe('nord');
    });

    it('Sunset returns valid name', function () {
        expect(Sunset::getName())->toBe('sunset');
    });

    it('all themes have getThemeColor method', function () {
        $themes = [
            new DefaultTheme,
            new Dracula,
            new Nord,
            new Sunset,
        ];

        foreach ($themes as $theme) {
            $colors = $theme->getThemeColor();
            expect($colors)->toBeArray();
        }
    });
});

describe('Themes Registry', function () {
    it('can register themes', function () {
        $registry = app(Themes::class);
        $registry->register([
            'custom' => DefaultTheme::class,
        ]);

        $themes = $registry->getThemes();

        expect($themes)->toBeInstanceOf(\Illuminate\Support\Collection::class);
        expect($themes->has('custom'))->toBeTrue();
    });

    it('can get themes collection', function () {
        $registry = app(Themes::class);
        $themes = $registry->getThemes();

        expect($themes)->toBeInstanceOf(\Illuminate\Support\Collection::class);
        expect($themes->has('default'))->toBeTrue();
        expect($themes->has('dracula'))->toBeTrue();
    });

    it('can override all themes', function () {
        $registry = new Themes;
        $registry->register([
            'custom' => Sunset::class,
        ], override: true);

        $themes = $registry->getThemes();

        expect($themes->count())->toBe(1);
        expect($themes->has('custom'))->toBeTrue();
    });

    it('can make theme instance', function () {
        $registry = app(Themes::class);
        $theme = $registry->make('default');

        expect($theme)->toBeInstanceOf(DefaultTheme::class);
    });
});

describe('Livewire v3 Compatibility', function () {
    it('middleware is compatible with Livewire v3', function () {
        $middleware = new \Hasnayeen\Themes\Http\Middleware\SetTheme;

        expect($middleware)->toBeObject();
        expect(method_exists($middleware, 'handle'))->toBeTrue();
    });

    it('themes page extends Filament Page', function () {
        expect(\Hasnayeen\Themes\Filament\Pages\Themes::class)
            ->toExtend(\Filament\Pages\Page::class);
    });
});
