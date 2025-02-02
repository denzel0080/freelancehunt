<?php

/**
 * Database Setup Script
 *
 * This script initializes the database schema for the project management system.
 * It creates all necessary tables and sets up relationships between them.
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
 *
 * Table Structure:
 * - employers: Stores employer information
 * - skills: Stores available project skills
 * - project_statuses: Stores project status options
 * - projects: Main project information
 * - project_skills: Junction table for project-skill relationships
 * - tags: Stores project tags
 * - project_tags: Junction table for project-tag relationships
 */

require_once __DIR__ . '/../vendor/autoload.php';
use App\Config\Database;

// Initialize environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Establish database connection
$db = Database::getConnection();

try {
    /**
     * SQL Schema Definition
     * Creates all necessary tables and relationships for the project management system
     */
    $sql = "
    /* Employers table: stores information about project employers */
    CREATE TABLE IF NOT EXISTS employers (
        id INT PRIMARY KEY,
        login VARCHAR(255) NOT NULL,
        first_name VARCHAR(255),
        last_name VARCHAR(255),
        avatar_small VARCHAR(255),
        avatar_large VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    /* Skills table: stores available project skills/categories */
    CREATE TABLE IF NOT EXISTS skills (
        id INT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    /* Project statuses table: stores possible project states */
    CREATE TABLE IF NOT EXISTS project_statuses (
        id INT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    /* Main projects table: stores core project information */
    CREATE TABLE IF NOT EXISTS projects (
        id INT PRIMARY KEY,
        name VARCHAR(512) NOT NULL,
        description TEXT,
        description_html TEXT,
        budget_amount DECIMAL(10, 2),
        budget_currency VARCHAR(3),
        bid_count INT DEFAULT 0,
        is_remote_job BOOLEAN DEFAULT FALSE,
        is_premium BOOLEAN DEFAULT FALSE,
        is_personal BOOLEAN DEFAULT FALSE,
        safe_type VARCHAR(50),
        employer_id INT,
        published_at TIMESTAMP,
        expired_at TIMESTAMP,
        status_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (employer_id) REFERENCES employers(id),
        FOREIGN KEY (status_id) REFERENCES project_statuses(id)
    );

    /* Junction table for projects and skills: manages many-to-many relationships */
    CREATE TABLE IF NOT EXISTS project_skills (
        project_id INT,
        skill_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (project_id, skill_id),
        FOREIGN KEY (project_id) REFERENCES projects(id),
        FOREIGN KEY (skill_id) REFERENCES skills(id)
    );

    /* Tags table: stores project tags */
    CREATE TABLE IF NOT EXISTS tags (
        id INT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    /* Junction table for projects and tags: manages many-to-many relationships */
    CREATE TABLE IF NOT EXISTS project_tags (
        project_id INT,
        tag_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (project_id, tag_id),
        FOREIGN KEY (project_id) REFERENCES projects(id),
        FOREIGN KEY (tag_id) REFERENCES tags(id)
    );

    /* Performance optimization indexes */
    ALTER TABLE projects
    ADD INDEX idx_projects_published_at (published_at),
    ADD INDEX idx_projects_status_id (status_id),
    ADD INDEX idx_projects_employer_id (employer_id);
    ";

    // Execute SQL schema creation
    $db->exec($sql);
    echo "Database tables created successfully!\n";

} catch (PDOException $e) {
    echo "Error creating database tables: " . $e->getMessage() . "\n";
    exit(1);
}