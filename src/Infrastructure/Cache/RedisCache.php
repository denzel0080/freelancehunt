<?php

/**
 * Redis Cache Implementation
 *
 * Provides a Redis-based caching implementation with JSON serialization
 * and singleton pattern for connection management.
 *
 * PHP version 8.2
 *
 * @category  Infrastructure
 * @package   App\Infrastructure\Cache
 * @author    Denys Liubynovskyi <denys.liubynovskyi@gmail.com>
 * @copyright 2024 Your Organization
 * @license   http://www.php.net/license/3_01.txt PHP License 3.01
 * @version   1.0.0
 * @link      http://yourproject.com
 */

namespace App\Infrastructure\Cache;

use Predis\Client;
use App\Interfaces\ICache;

/**
 * Redis Cache Class
 *
 * Implements caching functionality using Redis with JSON serialization
 */
class RedisCache implements ICache
{
    /**
     * Redis client instance
     *
     * @var Client|null
     */
    private static ?Client $instance = null;

    /**
     * Default cache TTL in seconds
     *
     * @var integer
     */
    private static int $defaultTtl = 3600;

    /**
     * Gets Redis client instance (singleton pattern)
     *
     * @return Client Redis client instance
     */
    public static function getInstance(): Client
    {
        if (self::$instance === null) {
            self::$instance = new Client([
                'scheme' => 'tcp',
                'host'   => $_ENV['REDIS_HOST'] ?? 'redis',
                'port'   => $_ENV['REDIS_PORT'] ?? 6379,
            ]);
        }
        return self::$instance;
    }

    /**
     * Retrieves a value from cache
     *
     * @param string $key Cache key
     * 
     * @return mixed Cached value or null if not found
     */
    public static function get(string $key): mixed 
    {
        $data = self::getInstance()->get($key);
        return $data ? json_decode($data, true) : null;
    }

    /**
     * Stores a value in cache
     *
     * @param string   $key   Cache key
     * @param mixed    $value Value to store
     * @param int|null $ttl   Time to live in seconds (null for default)
     * 
     * @return bool True if successful, false otherwise
     */
    public static function set(string $key, $value, ?int $ttl = null): bool
    {
        $ttl = $ttl ?? self::$defaultTtl;
        $serialized = json_encode($value, JSON_UNESCAPED_UNICODE);
        return self::getInstance()->setex($key, $ttl, $serialized) === 'OK';
    }

    /**
     * Deletes a value from cache
     *
     * @param string $key Cache key
     * 
     * @return bool True if key was deleted, false if key didn't exist
     */
    public static function delete(string $key): bool
    {
        return self::getInstance()->del([$key]) > 0;
    }

    /**
     * Deletes all keys matching a pattern
     *
     * @param string $pattern Pattern to match (Redis pattern syntax)
     * 
     * @return void
     */
    public static function deletePattern(string $pattern): void
    {
        $keys = self::getInstance()->keys($pattern);
        if (!empty($keys)) {
            self::getInstance()->del($keys);
        }
    }

    /**
     * Checks if a key exists in cache
     *
     * @param string $key Cache key
     * 
     * @return bool True if key exists, false otherwise
     */
    public static function exists(string $key): bool
    {
        return self::getInstance()->exists($key) > 0;
    }

    /**
     * Gets debug information for a cache key
     *
     * @param string $key Cache key
     * 
     * @return array Debug information including existence, TTL, and type
     */
    public static function debug(string $key): array
    {
        $redis = self::getInstance();
        return [
            'exists' => $redis->exists($key) > 0,
            'ttl' => $redis->ttl($key),
            'type' => $redis->type($key)
        ];
    }
}