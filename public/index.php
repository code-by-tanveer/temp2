<?php
require __DIR__ . '/../vendor/autoload.php';

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// Create Container using PHP-DI
$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/../config/dependencies.php');
$container = $containerBuilder->build();

// Create App and set container
AppFactory::setContainer($container);
$app = AppFactory::create();

// Register Middlewares
$app->add($container->get(\App\Middleware\RateLimiterMiddleware::class));
$app->add(\App\Middleware\JwtMiddleware::class);

// Routes
$app->group('', function($group) {
    // Auth routes
    $group->post('/register', \App\Controllers\AuthController::class . ':register');

    // Group routes
    $group->get('/groups', \App\Controllers\GroupController::class . ':getAll');
    $group->post('/groups', \App\Controllers\GroupController::class . ':create');
    $group->post('/groups/{group_id}/join', \App\Controllers\GroupController::class . ':join');

    // Message routes
    $group->post('/groups/{group_id}/messages', \App\Controllers\MessageController::class . ':send');
    $group->get('/groups/{group_id}/messages', \App\Controllers\MessageController::class . ':list');

    // Preset images route
    $group->get('/preset-images', \App\Controllers\PresetImageController::class . ':list');
});

$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($request, $response) {
    $response = new \Slim\Psr7\Response();
    $payload = [
        'success' => false,
        'message' => 'Route not found',
    ];
    $response->getBody()->write(json_encode($payload));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
});

$app->run();
