<?php

declare(strict_types=1);

namespace AsimNet\FilamentUmmulqura;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentUmmulquraServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-ummulqura-date-picker';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasViews(static::$name)
            ->hasTranslations();
    }

    public function packageBooted(): void
    {
        FilamentAsset::register([
            AlpineComponent::make('hijri-date-time-picker', __DIR__.'/../dist/hijri-date-time-picker.js'),
            Css::make('filament-ummulqura-date-picker', __DIR__.'/../resources/css/hijri-date-time-picker.css'),
        ], 'asimnet/filament-ummulqura-date-picker');
    }
}
