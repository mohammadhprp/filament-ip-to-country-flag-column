# IP to country flag Column for Filament 🚩

[![License](https://img.shields.io/github/license/mohammadhprp/filament-ip-to-country-flag-column)](LICENSE)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/mohammadhprp/filament-ip-to-country-flag-column.svg?style=flat-square)](https://packagist.org/packages/mohammadhprp/filament-ip-to-country-flag-column)
[![Total Downloads](https://img.shields.io/packagist/dt/mohammadhprp/filament-ip-to-country-flag-column.svg?style=flat-square)](https://packagist.org/packages/mohammadhprp/filament-ip-to-country-flag-column)

Display country flag from IP address in your Filament tables

This version supports Filament 5 and requires PHP 8.2 or newer.

> **Warning**
> This plugin may cause a slight delay in page loading due to API calls to [iplocation](https://iplocation.com).

## Screenshot

![image](https://raw.githubusercontent.com/mohammadhprp/filament-ip-to-country-flag-column/master/.github/assets/screenshot.png)

## Installation

You can install the package via composer:

```bash
composer require mohammadhprp/filament-ip-to-country-flag-column
```

## Usage

To use the package, follow these steps:

```php
use Mohammadhprp\IPToCountryFlagColumn\Columns\IPToCountryFlagColumn;

IPToCountryFlagColumn::make('client_ip');
```

### Options

1. **Flag position**: Change the position of the flag using `flagPosition`. Available options: `right` and `left`.

   ```php
   IPToCountryFlagColumn::make('client_ip')->flagPosition('left');
   ```

   > 💡 Note: Default flag position is `right`.

2. **Hide flag**: Hide the flag using `hideFlag`.

   ```php
   IPToCountryFlagColumn::make('client_ip')->hideFlag();
   ```

3. **Location position**: Change the location position using `location()`. Available options: `below` and `above`.

   ```php
   IPToCountryFlagColumn::make('client_ip')->location(position: 'above');
   ```

   > 💡 Note: Default location position is `below`.

4. **Location separator**: Change the location separator using `location()`.

   ```php
   IPToCountryFlagColumn::make('client_ip')->location(separator: '-');
   ```

   > 💡 Note: Default location separator is `,`.

5. **Hide city or country name**: Hide city or country name using `hideCity()` or `hideCountry()`.

   ```php
   IPToCountryFlagColumn::make('client_ip')
        ->hideCountry()
        ->hideCity();
   ```

6. **Custom location resolver**: Use a custom resolver to cache lookups or use another IP location provider.

   ```php
   IPToCountryFlagColumn::make('client_ip')
       ->locationResolver(fn (string $ip): array => [
           'country_code' => 'US',
           'country_name' => 'United States',
           'city' => 'Mountain View',
       ]);
   ```

7. **Lazy loading**: Defer location lookups until the table has loaded. This avoids blocking the initial page response when the column is used with a deferred Filament table.

   ```php
   protected function table(Table $table): Table
   {
       return $table
           ->deferLoading()
           ->columns([
               IPToCountryFlagColumn::make('client_ip')->lazy(),
           ]);
   }
   ```

   Location responses from the default provider are cached for 24 hours. Use `locationResolver()` when you need application-specific caching or a different provider.

## Testing

```bash
composer test
```

## Example Application

The `example/` directory contains a Laravel 12 and Filament 5 application
using this package through a local Composer path repository. See
[`example/README.md`](example/README.md) for setup and login instructions.

## Changelog

Please see [CHANGELOG](https://github.com/mohammadhprp/filament-ip-to-country-flag-column/blob/master/CHANGELOG.md) for more information on what has changed recently.

## Contributing

1. Fork the repository.
2. Create a new branch for your feature.
3. Make your changes and commit them with clear commit messages.
4. Submit a pull request to the `master` branch.

## Credits

- [Mohammadhprp](https://github.com/mohammadhprp)
- [All Contributors](https://github.com/mohammadhprp/filament-ip-to-country-flag-column/contributors)

## License

This project is licensed under the MIT License - see the [License](https://github.com/mohammadhprp/filament-ip-to-country-flag-column/blob/master/LICENSE) file for details.
