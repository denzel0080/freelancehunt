<?php

/**
 * Project Importer Service
 *
 * Handles importing projects and related data from FreelanceHunt API
 * into the local database. Manages relationships between projects,
 * employers, skills, and tags.
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

use GuzzleHttp\Client;
use PDO;

/**
 * Project Importer Class
 *
 * Manages the import process of projects from FreelanceHunt API
 */
class ProjectImporter
{
    /**
     * Database connection instance
     *
     * @var PDO
     */
    private PDO $db;

    /**
     * HTTP client for API requests
     *
     * @var Client
     */
    private Client $client;

    /**
     * FreelanceHunt API key
     *
     * @var string
     */
    private string $apiKey;

    private const API_URI = 'https://api.freelancehunt.com/v2/';

    private const API_RATE_LIMIT_DELEY_MCS = 500000;

    /**
     * Constructor
     *
     * @param PDO    $db     Database connection
     * @param string $apiKey FreelanceHunt API key
     */
    public function __construct(PDO $db, string $apiKey)
    {
        $this->db = $db;
        $this->apiKey = $apiKey;
        $this->client = new Client([
            'base_uri' => self::API_URI,
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey
            ]
        ]);
    }

    /**
     * Imports all projects from the API
     *
     * Fetches and processes projects page by page with rate limiting
     *
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException On API request failure
     */
    public function import(): void
    {
        $currentPage = 1;
        $totalPages = null;

        do {
            echo "Importing page {$currentPage}...\n";
            
            $response = $this->client->get('projects', [
                'query' => [
                    'page[number]' => $currentPage
                ]
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);

            foreach ($data['data'] as $project) {
                $this->importProject($project);
            }

            if (isset($data['links']['next'])) {
                $currentPage++;
                
                if ($totalPages === null && isset($data['links']['last'])) {
                    parse_str(parse_url($data['links']['last'], PHP_URL_QUERY), $params);
                    $totalPages = $params['page']['number'] ?? null;
                    echo "Total pages to import: {$totalPages}\n";
                }
            } else {
                break;
            }

            usleep(self::API_RATE_LIMIT_DELEY_MCS); // Rate limiting delay

        } while ($currentPage <= $totalPages);

        echo "Import completed! Processed {$currentPage} pages.\n";
    }

    /**
     * Imports a single project and its related data
     *
     * @param array $project Project data from API
     *
     * @return void
     * @throws \Exception When import fails
     */
    private function importProject(array $project): void
    {
        $stmt = $this->db->prepare('SELECT id FROM projects WHERE id = ?');
        $projectId = $project['id'];
        $stmt->execute([$projectId]);

        if ($stmt->fetch()) {
            echo "Project with ".$projectId." exist!" . "\n";
            return;
        }

        $this->db->beginTransaction();

        try {
            $this->importEmployer($project['attributes']['employer']);
            $this->importStatus($project['attributes']['status']);

            foreach ($project['attributes']['skills'] as $skill) {
                $this->importSkill($skill);
            }

            if (isset($project['attributes']['tags'])) {
                foreach ($project['attributes']['tags'] as $tag) {
                    $this->importTag($tag);
                }
            }
            
            if (!$stmt->fetch()) {
                $this->insertProject($project);

                foreach ($project['attributes']['skills'] as $skill) {
                    $this->linkProjectSkill($project['id'], $skill['id']);
                }

                if (isset($project['attributes']['tags'])) {
                    foreach ($project['attributes']['tags'] as $tag) {
                        $this->linkProjectTag($project['id'], $tag['id']);
                    }
                }
            }

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            echo "Error importing project {$project['id']}: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Imports employer data
     *
     * @param array $employer Employer data from API
     *
     * @return void
     */
    private function importEmployer(array $employer): void
    {
        $sql = 'INSERT IGNORE INTO employers (id, login, first_name, last_name, avatar_small, avatar_large)
                VALUES (?, ?, ?, ?, ?, ?)';
        
        $this->db->prepare($sql)->execute([
            $employer['id'],
            $employer['login'],
            $employer['first_name'],
            $employer['last_name'],
            $employer['avatar']['small']['url'],
            $employer['avatar']['large']['url']
        ]);
    }

    /**
     * Imports project status
     *
     * @param array $status Status data from API
     *
     * @return void
     */
    private function importStatus(array $status): void
    {
        $sql = 'INSERT IGNORE INTO project_statuses (id, name) VALUES (?, ?)';
        $this->db->prepare($sql)->execute([$status['id'], $status['name']]);
    }

    /**
     * Imports skill data
     *
     * @param array $skill Skill data from API
     *
     * @return void
     */
    private function importSkill(array $skill): void
    {
        $sql = 'INSERT IGNORE INTO skills (id, name) VALUES (?, ?)';
        $this->db->prepare($sql)->execute([$skill['id'], $skill['name']]);
    }

    /**
     * Imports tag data
     *
     * @param array $tag Tag data from API
     *
     * @return void
     */
    private function importTag(array $tag): void
    {
        $sql = 'INSERT IGNORE INTO tags (id, name) VALUES (?, ?)';
        $this->db->prepare($sql)->execute([$tag['id'], $tag['name']]);
    }

    /**
     * Inserts project data into database
     *
     * @param array $project Project data from API
     *
     * @return void
     */
    private function insertProject(array $project): void
    {
        $sql = 'INSERT INTO projects (
                    id, name, description, description_html, 
                    budget_amount, budget_currency, bid_count,
                    is_remote_job, is_premium, is_personal,
                    safe_type, employer_id, published_at, 
                    expired_at, status_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $budget = $project['attributes']['budget'] ?? null;

        $this->db->prepare($sql)->execute([
            $project['id'],
            $project['attributes']['name'],
            $project['attributes']['description'],
            $project['attributes']['description_html'],
            $budget ? $budget['amount'] : null,
            $budget ? $budget['currency'] : null,
            $project['attributes']['bid_count'],
            $project['attributes']['is_remote_job'] ? 1 : 0,
            $project['attributes']['is_premium'] ? 1 : 0,
            $project['attributes']['is_personal'] ? 1 : 0,
            $project['attributes']['safe_type'],
            $project['attributes']['employer']['id'],
            $project['attributes']['published_at'],
            $project['attributes']['expired_at'],
            $project['attributes']['status']['id']
        ]);
    }

    /**
     * Links project with skill
     *
     * @param int $projectId Project identifier
     * @param int $skillId   Skill identifier
     *
     * @return void
     */
    private function linkProjectSkill(int $projectId, int $skillId): void
    {
        $sql = 'INSERT IGNORE INTO project_skills (project_id, skill_id) VALUES (?, ?)';
        $this->db->prepare($sql)->execute([$projectId, $skillId]);
    }

    /**
     * Links project with tag
     *
     * @param int $projectId Project identifier
     * @param int $tagId     Tag identifier
     *
     * @return void
     */
    private function linkProjectTag(int $projectId, int $tagId): void
    {
        $sql = 'INSERT IGNORE INTO project_tags (project_id, tag_id) VALUES (?, ?)';
        $this->db->prepare($sql)->execute([$projectId, $tagId]);
    }
}