<?php

namespace Tests\Unit\Services;

use App\Services\ProjectService;
use App\Interfaces\IRepository;
use App\Interfaces\ICache;
use PHPUnit\Framework\TestCase;
use Mockery;

class ProjectServiceTest extends TestCase
{
    private $repository;
    private $cache;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();

        $_ENV['CACHED'] = 'yes';
        
        // Create mocks using Mockery
        $this->repository = Mockery::mock(IRepository::class);
        $this->cache = Mockery::mock(ICache::class);
        
        // Create service instance with mocks
        $this->service = new ProjectService($this->repository, $this->cache);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testGetFilteredProjectsWithCache()
    {
        // Test data
        $filters = [
            'page' => 1,
            'perPage' => 25,
            'category' => 'PHP',
            'currency' => 'USD'
        ];

        $cachedResult = [
            'projects' => [
                [
                    'id' => 1,
                    'name' => 'Test Project',
                    'budget_amount' => 1000,
                    'budget_currency' => 'USD'
                ]
            ],
            'total' => 1,
            'currentPage' => 1,
            'perPage' => 25,
            'lastPage' => 1
        ];

        // Set up mock expectations using shouldReceive
        $this->cache->shouldReceive('get')
            ->once()
            ->with(Mockery::any())
            ->andReturn($cachedResult);

        // Execute test
        $result = $this->service->getFilteredProjects($filters);

        // Assertions
        $this->assertEquals($cachedResult, $result);
        $this->assertArrayHasKey('projects', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('currentPage', $result);
        $this->assertArrayHasKey('perPage', $result);
        $this->assertArrayHasKey('lastPage', $result);
    }

    public function testGetFilteredProjectsWithoutCache()
    {
        // Test data
        $filters = [
            'page' => 1,
            'perPage' => 25,
            'category' => 'PHP',
            'currency' => 'USD'
        ];

        $projectData = [
            [
                'id' => 1,
                'name' => 'Test Project',
                'budget_amount' => 1000,
                'budget_currency' => 'USD',
                'published_at' => '2024-02-01',
                'employer_login' => 'testuser',
                'employer_first_name' => 'Test',
                'employer_last_name' => 'User',
                'skills' => 'PHP,MySQL'
            ]
        ];

        // Set up mock expectations
        $this->cache->shouldReceive('get')
            ->once()
            ->with(Mockery::any())
            ->andReturnNull();

        $this->repository->shouldReceive('countProjects')
            ->once()
            ->with(Mockery::any())
            ->andReturn(1);

        $this->repository->shouldReceive('findProjects')
            ->once()
            ->with(Mockery::any(), Mockery::any(), Mockery::any())
            ->andReturn($projectData);

        $this->cache->shouldReceive('set')
            ->once()
            ->with(Mockery::any(), Mockery::any(), Mockery::any())
            ->andReturnTrue();

        // Execute test
        $result = $this->service->getFilteredProjects($filters);

        // Assertions
        $this->assertCount(1, $result['projects']);
        $this->assertEquals(1, $result['total']);
        $this->assertEquals(1, $result['currentPage']);
        $this->assertEquals(25, $result['perPage']);
        $this->assertEquals(1, $result['lastPage']);

        // Check project formatting
        $project = $result['projects'][0];
        $this->assertEquals(1, $project['id']);
        $this->assertEquals('Test Project', $project['name']);
        $this->assertEquals(1000.0, $project['budget_amount']);
        $this->assertEquals('USD', $project['budget_currency']);
        $this->assertEquals('PHP,MySQL', $project['skills']);
    }

    public function testGetFilteredProjectsWithDefaultPagination()
    {
        // Test with minimal filters
        $filters = ['category' => 'PHP'];

        $this->cache->shouldReceive('get')
            ->once()
            ->with(Mockery::any())
            ->andReturnNull();

        $this->repository->shouldReceive('countProjects')
            ->once()
            ->with(Mockery::any())
            ->andReturn(0);

        $this->repository->shouldReceive('findProjects')
            ->once()
            ->with(Mockery::any(), Mockery::any(), Mockery::any())
            ->andReturn([]);

        $this->cache->shouldReceive('set')
            ->once()
            ->with(Mockery::any(), Mockery::any(), Mockery::any())
            ->andReturnTrue();

        $result = $this->service->getFilteredProjects($filters);

        // Verify default pagination values
        $this->assertEquals(1, $result['currentPage']);
        $this->assertEquals(25, $result['perPage']);
        $this->assertEquals(0, $result['lastPage']);
    }
}