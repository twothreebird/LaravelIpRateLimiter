<?php

namespace BrenPop\LaravelIpRateLimiter\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \BrenPop\LaravelIpRateLimiter\LaravelIpRateLimiter
 */
class LaravelIpRateLimiter extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \BrenPop\LaravelIpRateLimiter\LaravelIpRateLimiter::class;
    }
}
