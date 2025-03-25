<?php
// src/Controllers/PresetImageController.php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PresetImageController extends BaseController
{
    // For demo purposes, preset images are hard-coded. In a real app you might load from config.
    protected $images = [
        ['id' => 1, 'name' => 'Avatar 1', 'url' => '/images/presets/avatar1.png'],
        ['id' => 2, 'name' => 'Avatar 2', 'url' => '/images/presets/avatar2.png'],
        ['id' => 3, 'name' => 'Avatar 3', 'url' => '/images/presets/avatar3.png'],
    ];

    public function list(Request $request, Response $response, array $args): Response
    {
        return $this->successResponse($response, $this->images);
    }
}