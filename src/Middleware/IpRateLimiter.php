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

        if (in_array($ip, $whitelistIps)) {
            return $next($request);
        }

        $route = $request->route();
        $whitelistRoutes = config("laravelIpRateLimiter.whitelist_routes");

        if (in_array($route, $whitelistRoutes)) {
            return $next($request);
        }

        $key = "ip:{$ip}:{$route}";

        if (! Cache::has($key)) {
            Cache::put($key, 0, config("laravelIpRateLimiter.lifetime"));
        }

        $attempts = Cache::increment($key);

        if ($attempts == config("laravelIpRateLimiter.max_attempts")) {
            $this->storeIpData($request, $key);
            Log::warning("Rate limit exceeded for IP: {$ip}");
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
            'route' => $request->route(),
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