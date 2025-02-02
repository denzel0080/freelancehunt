<?php

/**
 * Project Service Implementation
 *
 * Provides business logic for project operations with caching support.
 * Handles data retrieval, formatting, and cache management.
 *
 * PHP version 8.2
 *
 * @category  Services
 * @package   App\Services
 * @author    Denys Liubynovskyi <denys.liubynovskyi@gmail.com>
 * @copyright 2024 Your Organization
 * @license   http://www.php.net/license/3_01.txt PHP License 3.01
 * @version   1.0.0
 * @link      http://yourproject.com
 */

namespace App\Services;

use App\Interfaces\ICache;
use App\Interfaces\IRepository;
use App\Interfaces\IService;

/**
 * Project Service Class
 *
 * Implements project-related business logic with caching capabilities
 */
class ProjectService implements IService
{
    /**
     * Repository for data access
     *
     * @var IRepository
     */
    private IRepository $repository;

    /**
     * Cache service instance
     *
     * @var ICache
     */
    private ICache $cache;

    /**
     * Cache key prefix for project data
     *
     * @var string
     */
    private const CACHE_PREFIX = 'projects:';

    /**
     * Cache TTL in seconds (10 minutes)
     *
     * @var int
     */
    private const CACHE_TTL = 600;

    private const PROJECTS_PER_PAGE_DEFAULT = 25;

    private const DEFAULT_PAGE_NUMBER = 1;

    /**
     * Constructor
     *
     * @param IRepository $repository Data access repository
     * @param ICache     $cache      Cache service
     */
    public function __construct(
        IRepository $repository,
        ICache $cache
    ) {
        $this->repository = $repository;
        $this->cache = $cache;
    }

    /**
     * Retrieves filtered projects with caching
     *
     * @param array $filters Array of filter criteria:
     *                      - page: int Current page number
     *                      - perPage: int Items per page
     *                      - category: string Project category
     *                      - currency: string Budget currency
     *                      - sortBy: string Sort field
     *                      - sortOrder: string Sort direction
     *
     * @return array Array containing:
     *               - projects: array[] Formatted project data
     *               - total: int Total number of matching projects
     *               - currentPage: int Current page number
     *               - perPage: int Items per page
     *               - lastPage: int Last available page
     */
    public function getFilteredProjects(array $filters): array
    {
        $cached = isset($_ENV['CACHED']) && $_ENV['CACHED'] === 'yes' ? true : false;
        $cacheKey = $this->getCacheKey($filters);
        $cachedData = $this->cache->get($cacheKey);
        
        if ($cachedData !== null && $cached) {
            return $cachedData;
        }

        $page = isset($filters['page']) ? (int)$filters['page'] : self::DEFAULT_PAGE_NUMBER;
        $perPage = isset($filters['perPage']) ? (int)$filters['perPage'] : self::PROJECTS_PER_PAGE_DEFAULT;

        $total = $this->repository->countProjects($filters);
        $projects = $this->repository->findProjects($filters, $page, $perPage);

        $result = [
            'projects' => array_map([$this, 'formatProject'], $projects),
            'total' => $total,
            'currentPage' => $page,
            'perPage' => $perPage,
            'lastPage' => ceil($total / $perPage)
        ];

        $this->cache->set($cacheKey, $result, self::CACHE_TTL);
        
        return $result;
    }

    /**
     * Formats a project record for API response
     *
     * @param array $project Raw project data from repository
     *
     * @return array Formatted project data
     */
    private function formatProject(array $project): array
    {
        $projectId = (int)$project['id'];
        return [
            'id' => $projectId,
            'name' => $project['name'],
            'alias' => (string)$projectId,
            'budget_amount' => $project['budget_amount'] ? (float)$project['budget_amount'] : null,
            'budget_currency' => $project['budget_currency'],
            'published_at' => $project['published_at'],
            'employer' => [
                'login' => $project['employer_login'],
                'first_name' => $project['employer_first_name'],
                'last_name' => $project['employer_last_name']
            ],
            'skills' => $project['skills'] ? $project['skills'] : ''
        ];
    }

    /**
     * Generates a cache key for given filters
     *
     * @param array $filters Filter criteria
     *
     * @return string Cache key
     */
    private function getCacheKey(array $filters): string
    {
        return self::CACHE_PREFIX . md5(json_encode($filters));
    }
}