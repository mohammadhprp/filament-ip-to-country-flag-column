# IP to country flag Column for Filament 🚩

[![License](https://img.shields.io/github/license/mohammadhprp/filament-ip-to-country-flag-column)](LICENSE)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/mohammadhprp/filament-ip-to-country-flag-column.svg?style=flat-square)](https://packagist.org/packages/mohammadhprp/filament-ip-to-country-flag-column)
[![Total Downloads](https://img.shields.io/packagist/dt/mohammadhprp/filament-ip-to-country-flag-column.svg?style=flat-square)](https://packagist.org/packages/mohammadhprp/filament-ip-to-country-flag-column)

Display country flag from IP address in your Filament tables using [laravel-geoip](https://github.com/Torann/laravel-geoip)

## Screenshot

![image](https://raw.githubusercontent.com/mohammadhprp/filament-ip-to-country-flag-column/master/.github/assets/screenshot.png)

## Installation

You can install the package via composer:

```bash
composer require mohammadhprp/filament-ip-to-country-flag-column
```

For Filament v2:

```bash
composer require mohammadhprp/filament-ip-to-country-flag-column:"^0.2.0"
```

**Please note:** it is required that you also setup [laravel-geoip](https://lyften.com/projects/laravel-geoip/doc/). With this package you can choose your preferred provider for getting the country code from the IP address.

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
