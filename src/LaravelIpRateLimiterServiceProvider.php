<?php

namespace BrenPop\LaravelIpRateLimiter;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use BrenPop\LaravelIpRateLimiter\Commands\LaravelIpRateLimiterCommand;

class LaravelIpRateLimiterServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravelIpRateLimiter')
            ->hasConfigFile('laravelIpRateLimiter')
            ->hasMigration('create_rate_limited_ip_addresses_table.php');
    }
}
