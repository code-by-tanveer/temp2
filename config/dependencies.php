<?php
use App\Repositories\UserRepository;
use App\Repositories\GroupRepository;
use App\Repositories\MessageRepository;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use App\Controllers\AuthController;
// use PDO;
use Predis\Client as RedisClient;

return [
    // Database
    PDO::class => function() {
        $config = require __DIR__ . '/config.php';
        $db = new PDO($config['db']['dsn'], $config['db']['username'], $config['db']['password']);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    },
    //Redis
    RedisClient::class => function() {
        $config = require __DIR__ . '/config.php';
        return new RedisClient([
            'host' => $config['redis']['host'],
            'port' => $config['redis']['port'],
            // 'password' => $config['redis']['password']
        ]);
    },
    // Repositories
    UserRepository::class => function($container) {
        return new UserRepository($container->get(PDO::class));
    },
    GroupRepository::class => function($container) {
        return new GroupRepository($container->get(PDO::class));
    },
    MessageRepository::class => function($container) {
        return new MessageRepository($container->get(PDO::class));
    },
    // Logger
    Logger::class => function($container) {
        $logger = new Logger($container->get('app_name'));
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/app.log', Logger::DEBUG));
        return $logger;
    },
    // Controllers
    AuthController::class => function ($container) {
        return new AuthController(
            $container->get(UserRepository::class),
            $container->get(Logger::class),
            $container->get('jwt_config')
        );
    },
    MessageController::class => function ($container) { // Update MessageController definition
        return new MessageController(
            $container->get(MessageRepository::class),
            $container->get(GroupRepository::class),
            $container->get(Logger::class),
            $container->get('message_edit_time_limit') // Inject message_edit_time_limit
        );
    },

    'jwt_config' => function() {
        $config = require __DIR__ . '/config.php';
        return $config['jwt'];
    },
    'rate_limit' => function() {
        $config = require __DIR__ . '/config.php';
        return $config['rate_limit'];
    },
    'app_name' => function() {
        return 'chit-chat';
    },
    'message_edit_time_limit' => function() { // Add this
        $config = require __DIR__ . '/config.php';
        return $config['app']['message_edit_time_limit'];
    },
];
