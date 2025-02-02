<?php

namespace Tests\Integration;

use App\Infrastructure\Repo\Repo;
use App\Config\Database;
use PHPUnit\Framework\TestCase;
use PDO;

class RepoIntegrationTest extends TestCase
{
    private PDO $db;
    private Repo $repo;
    private array $testData;
    private static int $testCounter = 100000000; // Base for generating unique IDs

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::getConnection();
        $this->repo = new Repo($this->db);
        
        // Start transaction for test isolation
        $this->db->beginTransaction();
        
        // Clean up existing test data before setup
        $this->cleanupTestData();
        $this->setupTestData();
    }

    protected function tearDown(): void
    {
        // Rollback all changes after test
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
        parent::tearDown();
    }

    private function getUniqueId(): int
    {
        return self::$testCounter++;
    }

    private function cleanupTestData(): void
    {
        // Remove existing projects with test-related skills and names
        $this->db->exec("DELETE FROM project_skills WHERE project_id IN (
            SELECT id FROM projects 
            WHERE name LIKE 'Test%' OR name LIKE 'Test Pagination%'
        )");
        $this->db->exec("DELETE FROM projects WHERE name LIKE 'Test%' OR name LIKE 'Test Pagination%'");
        $this->db->exec("DELETE FROM skills WHERE name IN ('PHP Test', 'MySQL Test')");
        $this->db->exec("DELETE FROM employers WHERE login LIKE 'testuser_%'");
    }

    private function setupTestData(): void
    {
        // Generate unique IDs for this test run
        $employerId = $this->getUniqueId();
        $projectId = $this->getUniqueId();
        $skillId1 = $this->getUniqueId();
        $skillId2 = $this->getUniqueId();

        // Insert test employer
        $this->db->exec("INSERT INTO employers (id, login, first_name, last_name) 
                        VALUES ({$employerId}, 'testuser_{$employerId}', 'Test', 'User')");

        // Insert test skills with test marker
        $this->db->exec("INSERT INTO skills (id, name) VALUES ({$skillId1}, 'PHP Test')");
        $this->db->exec("INSERT INTO skills (id, name) VALUES ({$skillId2}, 'MySQL Test')");

        // Insert test project
        $this->db->exec("INSERT INTO projects (
            id, name, budget_amount, budget_currency, employer_id, published_at
        ) VALUES (
            {$projectId}, 'Test Project Unique', 1000, 'USD', {$employerId}, '2024-02-01 12:00:00'
        )");

        // Link project with skills
        $this->db->exec("INSERT INTO project_skills (project_id, skill_id) 
                        VALUES ({$projectId}, {$skillId1})");
        $this->db->exec("INSERT INTO project_skills (project_id, skill_id) 
                        VALUES ({$projectId}, {$skillId2})");

        // Store test data IDs for later use
        $this->testData = [
            'employerId' => $employerId,
            'projectId' => $projectId,
            'skillId1' => $skillId1,
            'skillId2' => $skillId2
        ];
    }

    public function testFindProjects(): void
    {
        $filters = [
            'category' => 'PHP Test',
            'currency' => 'USD',
            'name' => 'Test Project Unique'
        ];

        $projects = $this->repo->findProjects($filters, 1, 10);

        $this->assertNotEmpty($projects, 'Projects should not be empty');
        $this->assertCount(1, $projects, 'Should find exactly one project');

        $project = $projects[0];
        $this->assertEquals($this->testData['projectId'], $project['id'], 'Project ID should match');
        $this->assertEquals('Test Project Unique', $project['name'], 'Project name should match');
        $this->assertEquals(1000, $project['budget_amount'], 'Budget amount should match');
        $this->assertEquals('USD', $project['budget_currency'], 'Currency should match');
        $this->assertStringContainsString('testuser_', $project['employer_login'], 'Employer login should match');
        $this->assertStringContainsString('PHP Test', $project['skills'], 'Skills should contain PHP Test');
        $this->assertStringContainsString('MySQL Test', $project['skills'], 'Skills should contain MySQL Test');
    }

    public function testCountProjects(): void
    {
        $filters = [
            'category' => 'PHP Test',
            'currency' => 'USD',
            'name' => 'Test Project Unique'
        ];

        $count = $this->repo->countProjects($filters);
        $this->assertEquals(1, $count, 'Should count exactly one project');

        // Test with non-existing category
        $filters['category'] = 'Java Test';
        $count = $this->repo->countProjects($filters);
        $this->assertEquals(0, $count, 'Should find no projects with non-existing category');
    }

    public function testProjectPagination(): void
    {
        // Add additional test projects for pagination
        for ($i = 1; $i <= 5; $i++) {
            $projectId = $this->getUniqueId();
            
            $this->db->exec("INSERT INTO projects (
                id, name, budget_amount, budget_currency, employer_id, published_at
            ) VALUES (
                {$projectId}, 'Test Pagination Project {$i}', 1000, 'USD', 
                {$this->testData['employerId']}, '2024-02-01 12:00:00'
            )");
            
            // Link new projects with skills
            $this->db->exec("INSERT INTO project_skills (project_id, skill_id) 
                           VALUES ({$projectId}, {$this->testData['skillId1']})");
        }

        $filters = [
            'category' => 'PHP Test', 
            'currency' => 'USD'
        ];
        
        // Check first page
        $page1Projects = $this->repo->findProjects($filters, 1, 3);
        $this->assertCount(3, $page1Projects, 'First page should contain 3 projects');

        // Check second page
        $page2Projects = $this->repo->findProjects($filters, 2, 3);
        $this->assertCount(3, $page2Projects, 'Second page should contain 3 projects');

        // Check total count
        $totalCount = $this->repo->countProjects($filters);
        $this->assertEquals(6, $totalCount, 'Total count should be 6 (1 initial + 5 additional)');
    }
}