# A middleware package to track and store IP addresses in Redis and MySQL.

This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Installation

You can install the package via composer:

```bash
composer require brenpop/laravelipratelimiter
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="laravelipratelimiter-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravelipratelimiter-config"
```

This is the contents of the published config file:

```php
return [
    'max_attempts' => 20,
    'ttl_minutes' => 1440, // 24 hours
    'whitelist_routes' => [],
    'whitelist_ips' => []
];
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
