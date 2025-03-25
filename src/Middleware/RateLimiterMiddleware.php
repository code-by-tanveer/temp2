<?php
namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Http\Message\ResponseInterface as Response;
use Predis\Client as RedisClient;

class RateLimiterMiddleware
{
    protected $redis;

    public function __construct(RedisClient $redis) // Inject Redis client
    {
        $this->redis = $redis;
    }

    public function __invoke(Request $request, Handler $handler): Response
    {
        $config = $request->getAttribute('rate_limit') ?? $GLOBALS['container']->get('rate_limit');
        $limit = $config['limit'];
        $window = $config['window'];

        $ip = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';
        $key = "rate_limit:" . $ip; // Unique key for each IP

        $count = $this->redis->get($key);
        $count = $count ? (int)$count : 0;

        if ($count >= $limit) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(['error' => 'Rate limit exceeded']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(429);
        }

        $this->redis->incr($key); // Increment the count
        if ($count === 0) { // Set expiry only on the first request in the window
            $this->redis->expire($key, $window); // Expire key after the window (in seconds)
        }

        return $handler->handle($request);
    }
}