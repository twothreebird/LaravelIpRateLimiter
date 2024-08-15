<?php

namespace BrenPop\LaravelIpRateLimiter\Middleware;

use BrenPop\LaravelIpRateLimiter\Models\RateLimitedIpAddress;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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
        $route = $request->route();
        $ip = $request->ip();

        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            abort(500, "Invalid IP address");
        }

        $whitelistIps = config("laravelIpRateLimiter.whitelist_ips");

        if (in_array($ip, $whitelistIps)) {
            return $next($request);
        }

        $whitelistRoutes = config("laravelIpRateLimiter.whitelist_routes");

        if (in_array($route, $whitelistRoutes)) {
            return $next($request);
        }

        $key = "ip:{$ip}";

        if (!Cache::has($key)) {
            Cache::put($key, 0, config("laravelIpRateLimiter.lifetime"));
        }

        $attempts = Cache::increment($key);

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