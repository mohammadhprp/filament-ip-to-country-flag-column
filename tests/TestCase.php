<?php

namespace Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\QueryBuilder\QueryBuilderServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\Livewire\Partials\DataStoreOverride;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\LivewireServiceProvider;
use Livewire\Mechanisms\DataStore;
use Mohammadhprp\IPToCountryFlagColumn\IPToCountryFlagColumnServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            ActionsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeIconsServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            LivewireServiceProvider::class,
            NotificationsServiceProvider::class,
            QueryBuilderServiceProvider::class,
            SchemasServiceProvider::class,
            SupportServiceProvider::class,
            TablesServiceProvider::class,
            IPToCountryFlagColumnServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        $app['view']->addNamespace('ip-to-country-flag-column-tests', __DIR__.'/Fixtures/views');

        // Filament\Support\SupportServiceProvider rebinds Livewire's DataStore
        // mechanism via a plain (non-shared) ->bind(), which drops the singleton
        // Livewire's own Mechanism::register() relies on. Every store($component)
        // ->get()/set() call then resolves a fresh, empty instance, so component
        // state never round-trips and Livewire::test() fails on every render.
        // Re-binding it as a singleton restores one shared instance per test.
        $app->singleton(DataStore::class, DataStoreOverride::class);
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('visits', function (Blueprint $table): void {
            $table->id();
            $table->string('ip_address');
            $table->timestamps();
        });
    }
}
