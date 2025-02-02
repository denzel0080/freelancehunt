<?php

/**
 * Project Repository Implementation
 *
 * Provides database access layer for project-related operations
 * including filtering, sorting, and pagination capabilities.
 *
 * PHP version 8.2
 *
 * @category  Infrastructure
 * @package   App\Infrastructure\Repo
 * @author    Denys Liubynovskyi <denys.liubynovskyi@gmail.com>
 * @copyright 2024 Your Organization
 * @license   http://www.php.net/license/3_01.txt PHP License 3.01
 * @version   1.0.0
 * @link      http://yourproject.com
 */

namespace App\Infrastructure\Repo;

use PDO;
use App\Interfaces\IRepository;

/**
 * Repository Class
 *
 * Implements database operations for project management
 */
class Repo implements IRepository 
{
    /**
     * Database connection instance
     *
     * @var PDO
     */
    private PDO $db;

    /**
     * Constructor
     *
     * @param PDO $db Database connection instance
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Counts total number of projects matching the filters
     *
     * @param array $filters Filter criteria for projects
     * 
     * @return int Total number of matching projects
     */
    public function countProjects(array $filters): int
    {
        $countSql = "
            SELECT COUNT(DISTINCT p.id) as total
            FROM projects p
            LEFT JOIN project_skills ps ON p.id = ps.project_id
            LEFT JOIN skills s ON ps.skill_id = s.id
            WHERE 1=1
        ";

        $params = [];
        $countSql = $this->applyFilters($countSql, $filters, $params);

        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        return (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    /**
     * Retrieves projects matching the specified filters with pagination
     *
     * @param array $filters Filter criteria for projects
     * @param int   $page    Current page number
     * @param int   $perPage Items per page
     * 
     * @return array List of matching projects
     */
    public function findProjects(array $filters, int $page, int $perPage): array
    {
        $sql = "
            SELECT 
                p.id,
                p.name,
                p.budget_amount,
                p.budget_currency,
                p.published_at,
                e.login as employer_login,
                e.first_name as employer_first_name,
                e.last_name as employer_last_name,
                GROUP_CONCAT(DISTINCT s.name) as skills
            FROM projects p
            LEFT JOIN employers e ON p.employer_id = e.id
            LEFT JOIN project_skills ps ON p.id = ps.project_id
            LEFT JOIN skills s ON ps.skill_id = s.id
            WHERE 1=1
        ";

        $params = [];
        $sql = $this->applyFilters($sql, $filters, $params);

        $sql .= " GROUP BY p.id, p.name, p.budget_amount, p.budget_currency, p.published_at,
                  e.login, e.first_name, e.last_name";

        $sql = $this->applySorting($sql, $filters);

        $offset = ($page - 1) * $perPage;
        $sql .= " LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Applies filter conditions to the SQL query
     *
     * @param string $sql     Base SQL query
     * @param array  $filters Filter criteria
     * @param array  $params  Query parameters (passed by reference)
     * 
     * @return string Modified SQL query with filters
     */
    private function applyFilters(string $sql, array $filters, array &$params): string
    {
        if (!empty($filters['category'])) {
            $sql .= " AND EXISTS (
                SELECT 1 FROM project_skills ps2 
                JOIN skills s2 ON ps2.skill_id = s2.id
                WHERE ps2.project_id = p.id AND s2.name = :category
            )";
            $params[':category'] = $filters['category'];
        }

        if (!empty($filters['currency'])) {
            $sql .= " AND p.budget_currency = :currency";
            $params[':currency'] = $filters['currency'];
        }

        return $sql;
    }

    /**
     * Applies sorting to the SQL query
     *
     * @param string $sql     SQL query to modify
     * @param array  $filters Filter criteria containing sort parameters
     * 
     * @return string Modified SQL query with sorting
     */
    private function applySorting(string $sql, array $filters): string
    {
        $allowedSortFields = ['published_at', 'budget_amount', 'name'];
        $sortBy = in_array($filters['sortBy'] ?? '', $allowedSortFields) ? $filters['sortBy'] : 'published_at';
        $sortOrder = strtoupper($filters['sortOrder'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        return $sql . " ORDER BY p.$sortBy $sortOrder";
    }
}