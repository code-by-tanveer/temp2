<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Repositories\UserRepository;
use Firebase\JWT\JWT;

class AuthController
{
    protected $userRepository;
    protected $logger;
    protected $jwtConfig;

    public function __construct(UserRepository $userRepository, \Monolog\Logger $logger, $jwt_config)
    {
        $this->userRepository = $userRepository;
        $this->logger = $logger;
        $this->jwtConfig = $jwt_config;
    }

    public function register(Request $request, Response $response, array $args): Response
    {
        $params = (array)$request->getParsedBody();
        $username = trim($params['username'] ?? '');
        $profileImage = trim($params['profile_image'] ?? '');

        // Basic validation
        if (empty($username)) {
            return $this->errorResponse($response, 'Username is required', 400);
        }
        
        try {
            $user = $this->userRepository->create($username, $profileImage);
            // Generate JWT
            $payload = [
                'sub' => $user['id'],
                'username' => $user['username'],
                'iat' => time()
            ];
            $jwt = JWT::encode($payload, $this->jwtConfig['secret'], $this->jwtConfig['algo']);
            
            $result = [
                'user' => $user,
                'token' => $jwt
            ];
            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            $this->logger->error('Registration failed', ['exception' => $e]);
            return $this->errorResponse($response, 'Registration failed', 500);
        }
    }

    private function errorResponse(Response $response, string $message, int $status): Response
    {
        $response->getBody()->write(json_encode(['error' => $message]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
