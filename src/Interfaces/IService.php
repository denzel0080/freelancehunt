<?php

/**
 * Service Interface
 *
 * Defines the contract for project service implementations.
 * Provides methods for retrieving filtered project data.
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
 * Service Interface
 *
 * Interface for implementing project business logic operations
 */
interface IService
{
    /**
     * Retrieves filtered projects with pagination information
     *
     * @param array $filters Associative array of filter criteria:
     *                      - category: string Project category to filter by
     *                      - currency: string Currency code to filter by
     *                      - sortBy: string Field to sort by
     *                      - sortOrder: string Sort direction ('ASC' or 'DESC')
     *                      - page: int Current page number
     *                      - perPage: int Items per page
     *
     * @return array Associative array containing:
     *               - projects: array List of filtered projects
     *               - total: int Total number of matching projects
     *               - currentPage: int Current page number
     *               - perPage: int Items per page
     *               - lastPage: int Last available page number
     */
    public function getFilteredProjects(array $filters): array;
}