<?php

/**
 * API Entry Point Script
 *
 * Main entry point for the Projects API. Handles request routing,
 * CORS configuration, error handling, and container initialization.
 *
 * PHP version 8.2
 *
 * @category  API
 * @package   App\API
 * @author    Denys Liubynovskyi <denys.liubynovskyi@gmail.com>
 * @copyright 2024 Your Organization
 * @license   http://www.php.net/license/3_01.txt PHP License 3.01
 * @version   1.0.0
 * @link      http://yourproject.com
 */

require_once __DIR__ . '/../../vendor/autoload.php';

// Enable error reporting for development
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Load environment configuration
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

// Configure CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

try {
    // Initialize dependency container and create controller
    $container = \App\Config\Container::createContainer();
    $controller = $container->get(\App\Controllers\ProjectController::class);
    
    // Handle project requests
    $controller->getProjects();
} catch (Exception $e) {
    // Handle any uncaught exceptions with proper error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}