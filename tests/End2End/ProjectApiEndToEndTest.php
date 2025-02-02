<?php

namespace Tests\End2End;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use App\Config\Database;
use PDO;

class ProjectApiEndToEndTest extends TestCase
{
    private Client $client;
    private PDO $db;
    private const TEST_PROJECT_ID = 999;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new Client([
            'base_uri' => 'http://localhost:80',
            'http_errors' => false,
            'timeout' => 10
        ]);
        
        $this->db = Database::getConnection();
        $this->cleanupTestData();
        $this->setupTestData();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestData();
        parent::tearDown();
    }

    private function setupTestData(): void
    {
        // Cleanup before insertion
        $this->cleanupTestData();

        // Insert test employer
        $this->db->exec("INSERT INTO employers (id, login, first_name, last_name) 
                        VALUES (" . self::TEST_PROJECT_ID . ", 'testuser', 'Test', 'User')");

        // Insert test skill
        $this->db->exec("INSERT INTO skills (id, name) VALUES (" . self::TEST_PROJECT_ID . ", 'PHP Test')");
        
        // Insert test project
        $this->db->exec("INSERT INTO projects (
            id, name, budget_amount, budget_currency, employer_id, published_at
        ) VALUES (
            " . self::TEST_PROJECT_ID . ", 
            'Test Project Unique', 
            1000, 
            'USD', 
            " . self::TEST_PROJECT_ID . ", 
            '2024-02-01 12:00:00'
        )");

        // Link project with skill
        $this->db->exec("INSERT INTO project_skills (project_id, skill_id) 
                        VALUES (" . self::TEST_PROJECT_ID . ", " . self::TEST_PROJECT_ID . ")");
    }

    private function cleanupTestData(): void
    {
        $this->db->exec("DELETE FROM project_skills WHERE project_id = " . self::TEST_PROJECT_ID);
        $this->db->exec("DELETE FROM projects WHERE id = " . self::TEST_PROJECT_ID);
        $this->db->exec("DELETE FROM skills WHERE id = " . self::TEST_PROJECT_ID);
        $this->db->exec("DELETE FROM employers WHERE id = " . self::TEST_PROJECT_ID);
    }

    public function testGetProjects()
    {
        $response = $this->client->get('/api/projects?category=&currency=&sortBy=published_at&sortOrder=desc&searchCategory=&page=1&perPage=25');
        
        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true);
        
        $this->assertTrue($data['success'], 1);
        $this->assertArrayHasKey('data', $data, 'Response should contain data');
        $this->assertNotEmpty($data['data'], 'Projects data should not be empty');
     
    }

    public function testGetProjectsWithFilters()
    {
        // Filter by existing skill
        $response = $this->client->get('/api/projects', [
            'query' => [
                'category' => 'PHP Test',
                'currency' => 'USD'
            ]
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($data['success'], 'API request should be successful');
        $this->assertNotEmpty($data['data'], 'Should find projects with PHP Test skill');

        // Check for test project
        $testProjectFound = false;
        foreach ($data['data'] as $project) {
            if ($project['id'] == self::TEST_PROJECT_ID) {
                $testProjectFound = true;
                break;
            }
        }
        $this->assertTrue($testProjectFound, 'Test project not found in filtered results');

        // Filter by non-existent skill
        $response = $this->client->get('/api/projects', [
            'query' => [
                'category' => 'Non-Existent Skill'
            ]
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($data['success'], 'API request should be successful');
        $this->assertLessThanOrEqual(0, count($data['data']), 'Should not find projects for non-existent skill');
    }

    public function testPagination()
    {
        // Add additional projects for pagination testing
        try {
            for ($i = 1; $i <= 5; $i++) {
                $this->db->exec("INSERT INTO projects (
                    id, name, budget_amount, budget_currency, employer_id, published_at
                ) VALUES (
                    " . (1000 + $i) . ", 
                    'Pagination Test Project $i', 
                    1000, 
                    'USD', 
                    " . self::TEST_PROJECT_ID . ", 
                    '2024-02-01 12:00:00'
                )");
            }

            // Test first page
            $response = $this->client->get('/api/projects', [
                'query' => [
                    'perPage' => 3,
                    'page' => 1
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $this->assertTrue($data['success'], 'API request should be successful');
            $this->assertCount(3, $data['data'], 'First page should contain 3 projects');
            $this->assertEquals(1, $data['pagination']['currentPage'], 'Current page should be 1');

            // Test second page
            $response = $this->client->get('/api/projects', [
                'query' => [
                    'perPage' => 3,
                    'page' => 2
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $this->assertTrue($data['success'], 'API request should be successful');
            $this->assertNotEmpty($data['data'], 'Second page should not be empty');
            $this->assertEquals(2, $data['pagination']['currentPage'], 'Current page should be 2');

        } finally {
            // Cleanup additional test projects
            for ($i = 1; $i <= 5; $i++) {
                $this->db->exec("DELETE FROM projects WHERE id = " . (1000 + $i));
            }
        }
    }

    public function testErrorHandling()
    {
        // Test with incorrect page number
        $response = $this->client->get('/api/projects', [
            'query' => [
                'page' => 'invalid'
            ]
        ]);

        $this->assertEquals(500, $response->getStatusCode(), 'Should return 500 error');
    }

    public function testResponseHeaders()
    {
        $response = $this->client->get('/api/projects');
        
        // Check for required headers
        $this->assertTrue($response->hasHeader('Content-Type'), 'Content-Type header should be present');
        $this->assertTrue($response->hasHeader('Cache-Control'), 'Cache-Control header should be present');
        $this->assertTrue($response->hasHeader('X-Execution-Time'), 'Execution time header should be present');

        // Check header values
        $this->assertEquals('application/json', $response->getHeader('Content-Type')[0], 'Content-Type should be application/json');
        $this->assertEquals('no-cache', $response->getHeader('Cache-Control')[0], 'Cache-Control should be no-cache');
        
        // Check execution time
        $executionTime = $response->getHeader('X-Execution-Time')[0];
        $this->assertMatchesRegularExpression('/^\d+\.\d+ms$/', $executionTime, 'Execution time should be in ms format');
    }
}