<?php

namespace Nitsnets\FilamentWysiwygJsonFormatter;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentWysiwygJsonFormatterServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-wysiwyg-json-formatter';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile('wysiwyg')
            ->hasViews()
            ->hasTranslations();
    }

    public function packageBooted(): void
    {
        FilamentAsset::register([
            Css::make('wysiwyg', __DIR__.'/../resources/dist/wysiwyg.css')->loadedOnRequest(),
        ], 'nitsnets/filament-wysiwyg-json-formatter');
    }
}

