<?php

/**
 * Dependency Injection Container Configuration
 *
 * This class configures the dependency injection container for the application,
 * setting up all service bindings and interface implementations.
 *
 * PHP version 8.2
 *
 * @category  Config
 * @package   App\Config
 * @author    Denys Liubynovskyi <denys.liubynovskyi@gmail.com>
 * @copyright 2024 Your Organization
 * @license   http://www.php.net/license/3_01.txt PHP License 3.01
 * @version   1.0.0
 * @link      http://yourproject.com
 */

namespace App\Config;

use App\Interfaces\IRepository;
use App\Interfaces\IService;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use App\Services\ProjectService;
use PDO;
use App\Infrastructure\Repo\Repo;
use App\Interfaces\ICache;

/**
 * Container Configuration Class
 *
 * Manages dependency injection container setup and service registration
 */
class Container
{
    /**
     * Creates and configures the dependency injection container
     *
     * This method sets up all service bindings including:
     * - Database connection (PDO)
     * - Redis client
     * - Repository implementations
     * - Cache service
     * - Project service
     *
     * @return ContainerInterface The configured container instance
     * @throws \Exception When container building fails
     */
    public static function createContainer(): ContainerInterface
    {
        $builder = new ContainerBuilder();
        
        $builder->addDefinitions([
            // Database Connection Configuration
            PDO::class => function (ContainerInterface $c) {
                $url = parse_url($_ENV['DATABASE_URL']);
                $dsn = sprintf(
                    "mysql:host=%s;port=%s;dbname=%s",
                    $url['host'],
                    $url['port'] ?? 3306,
                    ltrim($url['path'], '/')
                );
                
                return new PDO(
                    $dsn,
                    $url['user'],
                    $url['pass'],
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                    ]
                );
            },

            // Redis Client Configuration
            \Predis\Client::class => function (ContainerInterface $c) {
                return new \Predis\Client([
                    'scheme' => 'tcp',
                    'host'   => $_ENV['REDIS_HOST'] ?? 'redis',
                    'port'   => $_ENV['REDIS_PORT'] ?? 6379,
                ]);
            },

            // Repository Implementation Binding
            IRepository::class => function (ContainerInterface $c) {
                return new Repo($c->get(PDO::class));
            },
        
            // Cache Implementation Binding
            ICache::class => function (ContainerInterface $c) {
                return new \App\Infrastructure\Cache\RedisCache();
            },

            // Service Implementation Binding
            IService::class => function (ContainerInterface $c) {
                return new ProjectService(
                    $c->get(IRepository::class),
                    $c->get(ICache::class)
                );
            },
        ]);

        return $builder->build();
    }
}