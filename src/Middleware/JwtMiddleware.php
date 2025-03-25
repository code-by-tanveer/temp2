<?php
namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Http\Message\ResponseInterface as Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtMiddleware
{
    public function __invoke(Request $request, Handler $handler): Response
    {
        $path = $request->getUri()->getPath();
        // Allow registration and preset images without auth
        if (in_array($path, ['/register', '/preset-images', '/docs'])) {
            return $handler->handle($request);
        }
        
        $authHeader = $request->getHeaderLine('Authorization');
        if (!$authHeader) {
            return $this->unauthorizedResponse('access token required');
        }
        
        list($jwt) = sscanf($authHeader, 'Bearer %s');
        if (!$jwt) {
            return $this->unauthorizedResponse();
        }
        
        $jwtConfig = $request->getAttribute('jwt_config') ?? $GLOBALS['container']->get('jwt_config');
        try {
            $decoded = JWT::decode($jwt, new Key($jwtConfig['secret'], $jwtConfig['algo']));
            // Add user info to request attributes
            $request = $request->withAttribute('user', $decoded);
        } catch (\Exception $e) {
            return $this->unauthorizedResponse();
        }
        
        return $handler->handle($request);
    }
    
    private function unauthorizedResponse($error = 'Unauthorized'): Response
    {
        $response = new \Slim\Psr7\Response();
        $payload = [
            'success' => false,
            'message' => $error,
        ];
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
    }
}
