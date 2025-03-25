<?php
// src/Controllers/GroupController.php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Repositories\GroupRepository;
use App\Repositories\UserRepository;

class GroupController extends BaseController
{
    protected $groupRepository;
    protected $userRepository;
    protected $logger;

    public function __construct(GroupRepository $groupRepository, UserRepository $userRepository, \Monolog\Logger $logger)
    {
        $this->groupRepository = $groupRepository;
        $this->userRepository = $userRepository;
        $this->logger = $logger;
    }

    public function getAll(Request $request, Response $response, array $args): Response
    {
        $queryParams = $request->getQueryParams();
        $paginationCursor = $queryParams['cursor'] ?? null;
        $paginationRel = $queryParams['rel'] ?? null;

        try {
            $result = $this->groupRepository->getAllGroups($paginationCursor, $paginationRel);
            return $this->successResponse($response, $result);
        } catch (\Exception $e) {
            $this->logger->error('Error in fetching groups', ['exception' => $e]);
            return $this->errorResponse($response, 'List groups failed', 500);
        }
    }

    public function create(Request $request, Response $response, array $args): Response
    {
        $params = $this->getRequestData($request);
        $name = trim($params['name'] ?? '');
        $groupImage = trim($params['group_image'] ?? '');

        if (empty($name)) {
            return $this->errorResponse($response, 'Group name is required', 400);
        }

        // Get authenticated user from request attributes
        $user = $request->getAttribute('user');

        try {
            $group = $this->groupRepository->create($name, $user->sub, $groupImage);
            return $this->successResponse($response, $group, 'Group created successfully', 201);
        } catch (\Exception $e) {
            $this->logger->error('Group creation failed', ['exception' => $e]);
            return $this->errorResponse($response, 'Group creation failed', 500);
        }
    }

    public function join(Request $request, Response $response, array $args): Response
    {
        $groupId = (int)$args['group_id'];
        $user = $request->getAttribute('user');

        try {
            $this->groupRepository->join($groupId, $user->sub);
            return $this->successResponse($response, [], 'Joined group successfully');
        } catch (\Exception $e) {
            $this->logger->error('Join group failed', ['exception' => $e]);
            return $this->errorResponse($response, 'Join group failed', 500);
        }
    }
}