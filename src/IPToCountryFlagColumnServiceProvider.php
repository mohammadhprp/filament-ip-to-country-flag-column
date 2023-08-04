<?php

namespace Mohammadhprp\IPToCountryFlagColumn;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class IPToCountryFlagColumnServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-ip-to-country-flag-column';

    protected array $styles = [];

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasAssets()
            ->hasViews();
    }

}