<?php

namespace BrenPop\LaravelIpRateLimiter\Models;

use Illuminate\Database\Eloquent\Model;

class RateLimitedIpAddress extends Model
{
    protected $fillable = [
        'redis_id',
        'ip',
        'url',
        'path',
        'method',
        'headers',
        'query',
        'body',
        'attempts'
    ];
}