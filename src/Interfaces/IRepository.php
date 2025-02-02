<?php

/**
 * Repository Interface
 *
 * Defines the contract for project repository implementations.
 * Provides methods for querying and counting projects with filtering and pagination.
 *
 * PHP version 8.2
 *
 * @category  Interfaces
 * @package   App\Interfaces
 * @author    Denys Liubynovskyi <denys.liubynovskyi@gmail.com>
 * @copyright 2024 Your Organization
 * @license   http://www.php.net/license/3_01.txt PHP License 3.01
 * @version   1.0.0
 * @link      http://yourproject.com
 */

namespace App\Interfaces;

/**
 * Repository Interface
 *
 * Interface for implementing project data access operations
 */
interface IRepository
{
    /**
     * Counts total number of projects matching the specified filters
     *
     * @param array $filters Associative array of filter criteria:
     *                      - category: string Project category to filter by
     *                      - currency: string Currency code to filter by
     *                      - sortBy: string Field to sort by
     *                      - sortOrder: string Sort direction ('ASC' or 'DESC')
     *
     * @return int Total number of matching projects
     */
    public function countProjects(array $filters): int;

    /**
     * Retrieves a paginated list of projects matching the specified filters
     *
     * @param array $filters Associative array of filter criteria:
     *                      - category: string Project category to filter by
     *                      - currency: string Currency code to filter by
     *                      - sortBy: string Field to sort by
     *                      - sortOrder: string Sort direction ('ASC' or 'DESC')
     * @param int   $page    Current page number (1-based)
     * @param int   $perPage Number of items per page
     *
     * @return array List of projects matching the criteria
     */
    public function findProjects(array $filters, int $page, int $perPage): array;
}