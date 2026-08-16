# Filament IP Country Flag Example

This is a small Laravel 12 and Filament 5 application using the local
`mohammadhprp/filament-ip-to-country-flag-column` package.

## Run It

From this directory:

```bash
composer install
php artisan migrate:fresh --seed
php artisan make:filament-user
php artisan serve
```

Open `http://127.0.0.1:8000/admin` and visit the **Visits** resource. The
seeded IP addresses demonstrate country lookup, flag rendering, and the
localhost behavior. Public IP addresses are resolved through the package's
default `iplocation.com` provider, so network access is required for those
rows.

The package is linked from the parent repository using a Composer path
repository in `composer.json`. This makes edits to the package immediately
available in the example application.
