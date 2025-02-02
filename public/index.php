<?php
/**
 * Vite Assets Handler
 *
 * This script manages asset loading for Vite in both development and production environments.
 * It provides functionality to resolve asset URLs based on the current environment and Vite's manifest.
 *
 * PHP version 8.2
 *
 * @category  Utils
 * @package   App\Utils
 * @author    Denys Liubynovskyi <denys.liubynovskyi@gmail.com>
 * @copyright 2024 Your Organization
 * @license   http://www.php.net/license/3_01.txt PHP License 3.01
 * @version   1.0.0
 * @link      http://yourproject.com
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Initialize environment variables
$dotenv = Dotenv\Dotenv::createImmutable('/var/www/html' . '');
$dotenv->load();

// Determine environment type
$isDev = $_ENV['APP_ENV'] === 'dev';
$viteDevServer = 'http://localhost:5173';

/**
 * Resolves the URL for a Vite asset based on the current environment
 *
 * In development, returns the URL from the Vite dev server.
 * In production, reads from the manifest file and returns the hashed filename.
 *
 * @param string $entry The entry point or asset path to resolve
 * 
 * @return string The resolved URL for the asset
 * @throws RuntimeException If manifest.json cannot be read in production
 */
function vite_asset($entry)
{
    global $isDev, $viteDevServer;

    if ($isDev) {
        return "{$viteDevServer}/{$entry}";
    }

    // Read and parse the manifest file in production
    $manifest = json_decode(file_get_contents(__DIR__ . '/dist/manifest.json'), true);
    return '/dist/' . ($manifest[$entry]['file'] ?? $entry);
}
?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Freelancehunt Projects</title>
        <?php if ($isDev): ?>
            <script type="module" src="<?= $viteDevServer ?>/@vite/client"></script>
        <?php endif; ?>
    </head>
    <body class="bg-gray-100">
        <div id="app"></div>
        <script type="module" src="<?= vite_asset('js/app.ts') ?>"></script>
    </body>
    </html>