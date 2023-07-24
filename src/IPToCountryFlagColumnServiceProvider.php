<?php

namespace Mohammadhprp\IPToCountryFlagColumn;

use Filament\PluginServiceProvider;
use Spatie\LaravelPackageTools\Package;

class IPToCountryFlagColumnServiceProvider extends PluginServiceProvider
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