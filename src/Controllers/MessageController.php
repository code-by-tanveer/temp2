<?php
// src/Controllers/MessageController.php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Repositories\MessageRepository;
use App\Repositories\GroupRepository;

class MessageController extends BaseController
{
    protected $messageRepository;
    protected $groupRepository;
    protected $logger;

    public function __construct(MessageRepository $messageRepository, GroupRepository $groupRepository, \Monolog\Logger $logger)
    {
        $this->messageRepository = $messageRepository;
        $this->groupRepository = $groupRepository;
        $this->logger = $logger;
    }

    public function send(Request $request, Response $response, array $args): Response
    {
        $groupId = (int)$args['group_id'];
        $params = $this->getRequestData($request);
        $content = trim($params['content'] ?? '');
        $replyTo = isset($params['reply_to']) ? (int)$params['reply_to'] : null;

        // Enforce max message length (1000 characters)
        if (strlen($content) > 1000) {
            return $this->errorResponse($response, 'Message exceeds maximum length', 400);
        }

        if (empty($content)) {
            return $this->errorResponse($response, 'Content is required', 400);
        }

        $user = $request->getAttribute('user');
        $replyPreview = null;

        if ($replyTo) {
            // Fetch original message to get preview
            $original = $this->messageRepository->findById($replyTo);
            if ($original) {
                $replyPreview = substr($original['content'], 0, 50);
            }
        }

        try {
            $message = $this->messageRepository->create($groupId, $user->sub, $content, $replyTo, $replyPreview);
            return $this->successResponse($response, $message, 'Message sent successfully', 201);
        } catch (\Exception $e) {
            $this->logger->error('Send message failed', ['exception' => $e]);
            return $this->errorResponse($response, 'Send message failed', 500);
        }
    }

    public function list(Request $request, Response $response, array $args): Response
    {
        $groupId = (int)$args['group_id'];
        $queryParams = $request->getQueryParams();
        $paginationToken = $queryParams['token'] ?? null;

        try {
            $result = $this->messageRepository->listByGroup($groupId, $paginationToken);
            return $this->successResponse($response, $result);
        } catch (\Exception $e) {
            $this->logger->error('List messages failed', ['exception' => $e]);
            return $this->errorResponse($response, 'List messages failed', 500);
        }
    }
}