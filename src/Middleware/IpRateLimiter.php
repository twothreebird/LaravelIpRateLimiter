<?php

namespace BrenPop\LaravelIpRateLimiter\Middleware;

use BrenPop\LaravelIpRateLimiter\Models\RateLimitedIpAddress;
use Illuminate\Http\Request;
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
        $ip = $request->ip();

        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            abort(500, "Invalid IP address");
        }

        $whitelistIps = config("laravelIpRateLimiter.whitelist_ips");

        if (in_array((string) $ip, $whitelistIps)) {
            return $next($request);
        }

        $path = $request->path();
        $whitelistPaths = config("laravelIpRateLimiter.whitelist_paths");

        if (in_array($path, $whitelistPaths)) {
            return $next($request);
        }

        $key = "ip:{$ip}:{$path}";

        if (! Cache::has($key)) {
            Cache::put($key, 0, config("laravelIpRateLimiter.ttl_minutes"));
        }

        $attempts = Cache::increment($key);

        if ($attempts == config("laravelIpRateLimiter.max_attempts")) {
            $url = $request->url();
            $this->storeIpData($request, $key);
            Log::warning("Rate limit exceeded for IP: {$ip} - Url: {$url}");
        }
    
        if ($attempts >= config("laravelIpRateLimiter.max_attempts")) {
            $this->updateAttemtsCount($key, $attempts);
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
    protected function storeIpData(Request $request, $redisId): RateLimitedIpAddress
    {
        return RateLimitedIpAddress::create([
            'redis_id' => $redisId,
            'ip' => $request->ip(),
            'url' => $request->url(),
            'path' => $request->path(),
            'method' => $request->method(),
            'headers' => json_encode($request->headers->all()),
            'query' => json_encode($request->query()),
            'body' => json_encode($request->all()),
        ]);
    }

    /**
     * Update the attempts count in the rate limited ip addresses table
     * 
     * @param mixed $request
     * @param int $attempts
     * @return void
     */
    protected function updateAttemtsCount($redisId, int $attempts)
    {
        RateLimitedIpAddress::where('redis_id', $redisId)
            ->update(['attempts' => $attempts]);
    }
}