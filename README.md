# IP to country flag Column for Filament 🚩

[![License](https://img.shields.io/github/license/mohammadhprp/filament-ip-to-country-flag-column)](LICENSE)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/mohammadhprp/filament-ip-to-country-flag-column.svg?style=flat-square)](https://packagist.org/packages/mohammadhprp/filament-ip-to-country-flag-column)
[![Total Downloads](https://img.shields.io/packagist/dt/mohammadhprp/filament-ip-to-country-flag-column.svg?style=flat-square)](https://packagist.org/packages/mohammadhprp/filament-ip-to-country-flag-column)
[![Plumb score](https://plumbphp.dev/badges/mohammadhprp/filament-ip-to-country-flag-column/composite.svg)](https://plumbphp.dev/badges/mohammadhprp/filament-ip-to-country-flag-column/composite.svg)

![image](https://raw.githubusercontent.com/mohammadhprp/filament-ip-to-country-flag-column/master/.github/assets/screenshot.png)

## Installation

Requires Filament 5 and PHP 8.2 or newer.

```bash
composer require mohammadhprp/filament-ip-to-country-flag-column
```

## Usage

```php
use Mohammadhprp\IPToCountryFlagColumn\Columns\IPToCountryFlagColumn;

IPToCountryFlagColumn::make('client_ip');
```

### Options

- `flagPosition('left')` - flag position, `right` (default) or `left`.
- `location(position: 'above', separator: '-')` - location position, `below` (default) or `above`, and separator (default `,`).
- `hideIP()`, `hideFlag()`, `hideLocation()`, `hideCountry()`, `hideCity()`.
- `locationResolver(fn (string $ip): array => [...])` - use another provider, add caching, or point at a self-hosted service. The package never caches lookups itself.
- `whenVisible()` - resolve a cell only once it scrolls into view.
- `loadingState('Locating…')` and `errorState('Unavailable')` - what a cell shows while loading and when a lookup fails (they accept a string, an `Htmlable`, or a closure). Failed cells are clickable to retry; disable with `retryable(false)`. Use `errorStateUsing(fn (Throwable $exception) => ...)` if you need the exception.

## Testing

```bash
composer test
```

## Example Application

The `example/` directory contains a Laravel 12 and Filament 5 application using
this package through a local Composer path repository. See
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
