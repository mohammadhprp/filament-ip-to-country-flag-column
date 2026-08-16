<?php

use Filament\Tables\Columns\TextColumn;
use Illuminate\Contracts\View\View;
use Mohammadhprp\IPToCountryFlagColumn\Columns\IPToCountryFlagColumn;

it('creates a filament text column', function () {
    $column = IPToCountryFlagColumn::make('ip');

    expect($column)->toBeInstanceOf(TextColumn::class)
        ->and($column->getFlagPosition())->toBe('right')
        ->and($column->getLocationPosition())->toBe('below')
        ->and($column->getHideIP())->toBeFalse()
        ->and($column->getHideFlag())->toBeFalse()
        ->and($column->getHideLocation())->toBeFalse();
});

it('supports fluent display configuration', function () {
    $column = IPToCountryFlagColumn::make('ip')
        ->hideIP()
        ->hideFlag()
        ->hideLocation()
        ->flagPosition('left')
        ->location('above', ' - ');

    expect($column->getHideIP())->toBeTrue()
        ->and($column->getHideFlag())->toBeTrue()
        ->and($column->getHideLocation())->toBeTrue()
        ->and($column->getFlagPosition())->toBe('left')
        ->and($column->getLocationPosition())->toBe('above');
});

it('resolves an IP without making a network request', function () {
    $column = IPToCountryFlagColumn::make('ip')
        ->record(['ip' => '8.8.8.8'])
        ->locationResolver(fn (string $ip): array => [
            'country_code' => 'US',
            'country_name' => 'United States',
            'city' => 'Mountain View',
        ]);

    expect($column->getIP())->toBe('8.8.8.8')
        ->and($column->getFlag())->toBe('🇺🇸')
        ->and($column->getLocation())->toBe('Mountain View, United States');
});

it('handles invalid and localhost addresses', function (string $ip, string $expected) {
    $column = IPToCountryFlagColumn::make('ip')->record(['ip' => $ip]);

    expect($column->getIP())->toBe($expected);
})->with([
    ['not-an-ip', 'Invalid IP address'],
    ['127.0.0.1', '127.0.0.1 🏠'],
]);

it('formats locations when city or country is hidden', function () {
    $base = fn (): IPToCountryFlagColumn => IPToCountryFlagColumn::make('ip')
        ->record(['ip' => '8.8.8.8'])
        ->locationResolver(fn (): array => [
            'country_code' => 'US',
            'country_name' => 'United States',
            'city' => 'Mountain View',
        ]);

    $countryHidden = $base()->hideCountry();
    $countryHidden->getIP();

    $cityHidden = $base()->hideCity();
    $cityHidden->getIP();

    expect($countryHidden->getLocation())->toBe('Mountain View')
        ->and($cityHidden->getLocation())->toBe('United States');
});

it('renders the registered column view', function () {
    expect(view('filament-ip-to-country-flag-column::columns.ip-to-country-flag-column'))
        ->toBeInstanceOf(View::class);
});
