<?php

namespace Mohammadhprp\IPToCountryFlagColumn;

use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Livewire\ComponentHookRegistry;
use Mohammadhprp\IPToCountryFlagColumn\Livewire\IPToCountryFlagColumnHook;
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

    /**
     * Registers the component hook during Laravel's REGISTER phase, not boot - this
     * is load-bearing.
     *
     * Livewire\ComponentHookRegistry::boot() wires each hook's listeners once, from
     * whatever is in its static $componentHooks list at that moment; a hook added
     * afterwards never gets those listeners and is silently never invoked again.
     * Laravel runs register() on every provider before boot() on any provider, so
     * registering here is the only placement safe regardless of this provider's
     * position relative to LivewireServiceProvider in the app's provider list.
     */
    public function packageRegistered(): void
    {
        ComponentHookRegistry::register(IPToCountryFlagColumnHook::class);
    }

    public function packageBooted(): void
    {
        // Not ->hasTranslations(): spatie/laravel-package-tools registers
        // translations under Package::shortName(), which is the full package name
        // here ("filament-ip-to-country-flag-column"), not the shorter namespace
        // the placeholder view and BatchResolver use. Registering explicitly keeps
        // the translation namespace aligned.
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'ip-to-country-flag-column');

        FilamentAsset::register([
            Js::make('ip-to-country-flag-column', __DIR__.'/../resources/js/ip-to-country-flag-column.js'),
            Css::make('ip-to-country-flag-column', __DIR__.'/../resources/css/ip-to-country-flag-column.css'),
        ], package: 'mohammadhprp/filament-ip-to-country-flag-column');
    }
}
