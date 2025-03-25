<?php
// src/Controllers/BaseController.php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class BaseController
{
    protected function getRequestData(Request $request): array
    {
        $contentType = $request->getHeaderLine('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $body = $request->getBody()->getContents();
            $params = json_decode($body, true);
            return is_array($params) ? $params : []; // Return empty array if not valid JSON
        } else {
            // For form-urlencoded or other types, fallback to getParsedBody
            return (array)$request->getParsedBody();
        }
    }

    protected function successResponse(Response $response, $data = [], string $message = 'Success', int $status = 200): Response
    {
        $payload = [
            'success' => true,
            'data' => $data,
            'message' => $message,
        ];
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }

    protected function errorResponse(Response $response, string $message, int $status = 400, $data = []): Response
    {
        $payload = [
            'success' => false,
            'data' => $data, // Can include error details if needed
            'message' => $message,
        ];
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}