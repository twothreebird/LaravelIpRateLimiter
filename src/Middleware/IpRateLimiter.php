<?php

namespace BrenPop\LaravelIpRateLimiter\Middleware;

use BrenPop\LaravelIpRateLimiter\Models\RateLimitedIpAddress;
use BrenPop\LaravelIpRateLimiter\Services\LaravelIpRateLimiterService;
use DateTime;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class IpRateLimiter
{
    /**
     * Handle an incoming request
     * 
     * @param mixed $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        $ip = $request->ip();
        $key = "ip:{$ip}";
        $attempts = Redis::incr($key);

        if ($attempts == 1) {
            Redis::expire($key, config("laravelIpRateLimiter.lifetime"));
        }

        if ($attempts == config("laravelIpRateLimiter.max_attempts")) {
            $this->storeIpData($request, $key);
            Log::warning("Rate limit exceeded for IP: {$ip}");
        }
    
        if ($attempts >= config("laravelIpRateLimiter.max_attempts")) {
            $this->updateAttemtsCount($request, $key, $attempts);
            abort(403, 'Ip rate limit reached. Try again in 24 hours.');
        }

        return $next($request);
    }

    /**
     * Create a record in the rate limited ip addresses table
     *
     * @param mixed $request
     * @return \BrenPop\LaravelIpRateLimiter\Models\RateLimitedIpAddress
     */
    protected function storeIpData($request, $redisId): RateLimitedIpAddress
    {
        return RateLimitedIpAddress::create([
            'redis_id' => $redisId,
            'ip' => $request->ip(),
            'url' => $request->url(),
            'method' => $request->method(),
            'headers' => json_encode($request->headers->all()),
            'query' => json_encode($request->query()),
            'body' => json_encode($request->all()),
            'attempts' => 1,
        ]);
    }

    /**
     * Update the attempts count in the rate limited ip addresses table
     * 
     * @param mixed $request
     * @param int $attempts
     * @return void
     */
    protected function updateAttemtsCount($request, $redisId, int $attempts)
    {
        RateLimitedIpAddress::where('ip', $request->ip())
            ->where('redis_id', $redisId)
            ->update(['attempts' => $attempts]);
    }
}