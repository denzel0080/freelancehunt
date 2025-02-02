<?php

/**
 * Database Configuration Class
 *
 * Provides singleton access to the database connection using PDO.
 * Manages connection creation and reuse throughout the application.
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

/**
 * Database Connection Manager
 *
 * Implements singleton pattern for PDO database connections.
 * Ensures a single database connection is reused across the application.
 */
class Database
{
    /**
     * Stores the singleton PDO connection instance
     *
     * @var \PDO|null
     */
    private static ?\PDO $connection = null;

    /**
     * Returns a PDO database connection instance
     *
     * Creates a new connection if one doesn't exist, otherwise returns
     * the existing connection. Uses environment variables for configuration.
     *
     * @return \PDO The database connection instance
     * @throws \PDOException When connection fails
     */
    public static function getConnection(): \PDO
    {
        if (self::$connection === null) {
            // Parse DATABASE_URL from environment
            $url = parse_url($_ENV['DATABASE_URL']);
            
            // Build proper MySQL DSN
            $dsn = sprintf(
                "mysql:host=%s;port=%s;dbname=%s",
                $url['host'],
                $url['port'] ?? 3306,
                ltrim($url['path'], '/')
            );
            
            // Create PDO connection with specific attributes:
            // - ERRMODE_EXCEPTION: Throw exceptions on errors
            // - FETCH_ASSOC: Return results as associative arrays
            // - SET NAMES utf8mb4: Ensure proper character encoding
            self::$connection = new \PDO(
                $dsn,
                $url['user'],
                $url['pass'],
                [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]
            );
        }

        return self::$connection;
    }
}