<?php

/**
 * Project Controller
 *
 * Handles HTTP requests related to project operations.
 * Provides endpoints for retrieving and filtering project data.
 *
 * PHP version 8.2
 *
 * @category  Controllers
 * @package   App\Controllers
 * @author    Denys Liubynovskyi <denys.liubynovskyi@gmail.com>
 * @copyright 2024 Your Organization
 * @license   http://www.php.net/license/3_01.txt PHP License 3.01
 * @version   1.0.0
 * @link      http://yourproject.com
 */

namespace App\Controllers;

use App\Interfaces\IService;

/**
 * Project Controller Class
 *
 * Manages project-related HTTP endpoints and request handling
 */
class ProjectController
{
    /**
     * Project service instance
     *
     * @var IService
     */
    private IService $projectService;

    /**
     * Constructor
     *
     * @param IService $projectService Service for project operations
     */
    public function __construct(IService $projectService)
    {
        $this->projectService = $projectService;
    }

    /**
     * Retrieves filtered projects with pagination
     *
     * GET Parameters:
     * - category: Filter by project category
     * - currency: Filter by project currency
     * - sortBy: Field to sort by (default: published_at)
     * - sortOrder: Sort direction (default: desc)
     * - page: Page number (default: 1)
     * - perPage: Items per page (default: 10)
     *
     * Response Headers:
     * - Content-Type: application/json
     * - Cache-Control: no-cache
     * - X-Execution-Time: Processing time in milliseconds
     *
     * @return void Outputs JSON response directly
     * @throws \Exception When project retrieval fails
     */
    public function getProjects(): void
    {
        try {
            $startTime = microtime(true);
            
            // Collect and sanitize filter parameters
            $filters = [
                'category' => $_GET['category'] ?? '',
                'currency' => $_GET['currency'] ?? '',
                'sortBy' => $_GET['sortBy'] ?? 'published_at',
                'sortOrder' => $_GET['sortOrder'] ?? 'desc',
                'page' => (int)($_GET['page'] ?? 1),
                'perPage' => (int)($_GET['perPage'] ?? 10)
            ];
    
            // Retrieve filtered projects
            $result = $this->projectService->getFilteredProjects($filters);
            
            // Calculate execution time
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            // Set response headers
            header('Content-Type: application/json');
            header('Cache-Control: no-cache');
            header(sprintf('X-Execution-Time: %.2fms', $executionTime));
            
            // Output success response
            echo json_encode([
                'success' => true,
                'data' => $result['projects'],
                'pagination' => [
                    'total' => $result['total'],
                    'currentPage' => $result['currentPage'],
                    'perPage' => $result['perPage'],
                    'lastPage' => $result['lastPage']
                ],
                'debug' => [
                    'executionTime' => $executionTime . ' ms',
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
        } catch (\Exception $e) {
            // Handle errors with appropriate status code
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}