<?php

/**
 * Cache Interface
 *
 * Defines the contract for cache implementations in the application.
 * Provides methods for basic cache operations: get, set, and delete.
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
 * Cache Interface
 *
 * Interface for implementing caching functionality
 */
interface ICache
{
    /**
     * Retrieves a value from cache
     *
     * @param string $key The cache key to retrieve
     *
     * @return mixed The cached value or null if not found
     */
    public static function get(string $key): mixed;

    /**
     * Stores a value in cache
     *
     * @param string $key   The cache key to store
     * @param mixed  $value The value to cache
     * @param int    $ttl   Time to live in seconds (0 for infinite)
     *
     * @return bool True if value was successfully stored, false otherwise
     */
    public static function set(string $key, mixed $value, int $ttl = 0): bool;

    /**
     * Deletes value(s) from cache
     *
     * @param string $pattern The key or pattern to delete
     *
     * @return bool True if deletion was successful, false otherwise
     */
    public static function delete(string $pattern): bool;
}