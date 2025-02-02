<?php

/**
 * Project Import Script
 *
 * This script handles the automated import of projects from FreelanceHunt API
 * into the local database.
 *
 * PHP version 8.2
 *
 * @category  Scripts
 * @package   App\Scripts
 * @author    Denys Liubynovskyi <denys.liubynovskyi@gmail.com>
 * @copyright 2024 Your Organization
 * @license   http://www.php.net/license/3_01.txt PHP License 3.01
 * @version   1.0.0
 * @link      http://yourproject.com
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use App\Services\ProjectImporter;

// Initialize environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Get database connection
$db = Database::getConnection();

// Initialize importer and start import process
$importer = new ProjectImporter($db, $_ENV['FREELANCEHUNT_API_KEY']);

try {
    $importer->import();
    echo "Import completed successfully\n";
} catch (Exception $e) {
    echo "Error during import: " . $e->getMessage() . "\n";
}