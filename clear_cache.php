<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
/**
 * Clear cache files in batches (optimized for millions of files)
 *
 * This script removes cache files 1000 at a time, processing oldest files first.
 * Uses directory handles to avoid loading all filenames into memory.
 *
 * Standalone script - does not require Finis framework.
 *
 * Usage CLI:
 *   php clear_cache.php [cache_directory] [batch_size] [--all] [--verbose]
 *
 * Usage Web:
 *   clear_cache.php?batch=1000&all=1&verbose=1&path=./misc/_cache
 *
 * Default cache path: ./misc/_cache
 *
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 */

// Default configuration
define('DEFAULT_CACHE_PATH', './misc/_cache');
define('DEFAULT_BATCH_SIZE', 1000);

/**
 * Compare function for sorting by mtime (oldest first)
 */
function compareMtime($a, $b)
{
    return $a - $b;
}

/**
 * Clear cache files in batches, oldest first
 *
 * @param string $cache_dir Cache directory path
 * @param int $batch_size Number of files to delete per batch (default 1000)
 * @param bool $verbose Output progress information
 * @param bool $is_web Whether running in web context
 * @return array Statistics about the operation
 */
function clearCacheBatch($cache_dir, $batch_size = 1000, $verbose = false, $is_web = false)
{
    $nl = $is_web ? "<br>\n" : "\n";

    $stats = array(
        'deleted' => 0,
        'failed' => 0,
        'has_more' => false,
        'batch_size' => $batch_size,
        'cache_dir' => $cache_dir
    );

    if (!file_exists($cache_dir) || !is_dir($cache_dir)) {
        if ($verbose) {
            echo "Cache directory does not exist: " . htmlspecialchars($cache_dir) . $nl;
        }
        return $stats;
    }

    // Collect batch_size files with their modification time (oldest first)
    $files = array();
    collectCacheFiles($cache_dir, $files, $batch_size + 1);

    // Sort by modification time (oldest first)
    uasort($files, 'compareMtime');

    // Check if there are more files than batch size
    if (count($files) > $batch_size) {
        $stats['has_more'] = true;
        $files = array_slice($files, 0, $batch_size, true);
    }

    // Delete files
    foreach ($files as $file => $mtime) {
        if (@unlink($file)) {
            $stats['deleted']++;
            if ($verbose) {
                $display_file = $is_web ? htmlspecialchars($file) : $file;
                echo "Deleted: $display_file (age: " . date('Y-m-d H:i:s', $mtime) . ")" . $nl;
            }
        } else {
            $stats['failed']++;
            if ($verbose) {
                $display_file = $is_web ? htmlspecialchars($file) : $file;
                echo "Failed: $display_file" . $nl;
            }
        }
    }

    // Clean up empty directories
    removeEmptyDirs($cache_dir);

    if ($verbose) {
        echo $nl . "--- Batch Summary ---" . $nl;
        echo "Deleted: {$stats['deleted']}" . $nl;
        echo "Failed: {$stats['failed']}" . $nl;
        echo "More files: " . ($stats['has_more'] ? "Yes" : "No") . $nl;
    }

    return $stats;
}

/**
 * Collect files from directory using directory handle (memory efficient)
 * Stops after collecting $limit files
 *
 * @param string $dir Directory to scan
 * @param array &$files Reference to files array (path => mtime)
 * @param int $limit Maximum files to collect
 * @return void
 */
function collectCacheFiles($dir, &$files, $limit)
{
    if (count($files) >= $limit) {
        return;
    }

    $dh = @opendir($dir);
    if (!$dh) {
        return;
    }

    while (($entry = readdir($dh)) !== false) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }

        $path = "$dir/$entry";

        if (is_dir($path)) {
            collectCacheFiles($path, $files, $limit);
        } elseif (is_file($path)) {
            $files[$path] = filemtime($path);
        }

        if (count($files) >= $limit) {
            break;
        }
    }

    closedir($dh);
}

/**
 * Remove empty directories recursively
 *
 * @param string $dir Directory to clean
 * @param string $root_dir Root directory (will not be removed)
 * @return bool True if directory was removed
 */
function removeEmptyDirs($dir, $root_dir = null)
{
    if ($root_dir === null) {
        $root_dir = $dir;
    }

    if (!is_dir($dir)) {
        return false;
    }

    $dh = @opendir($dir);
    if (!$dh) {
        return false;
    }

    $is_empty = true;
    while (($entry = readdir($dh)) !== false) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }

        $path = "$dir/$entry";
        if (is_dir($path)) {
            removeEmptyDirs($path, $root_dir);
            // Check again if subdir still exists
            if (is_dir($path)) {
                $is_empty = false;
            }
        } else {
            $is_empty = false;
        }
    }

    closedir($dh);

    // Don't remove the root cache directory
    if ($is_empty && realpath($dir) !== realpath($root_dir)) {
        @rmdir($dir);
        return true;
    }

    return false;
}

/**
 * Clear all cache files (runs in batches until complete)
 *
 * @param string $cache_dir Cache directory path
 * @param int $batch_size Number of files to delete per batch
 * @param bool $verbose Output progress information
 * @param bool $is_web Whether running in web context
 * @return array Final statistics
 */
function clearCacheAll($cache_dir, $batch_size = 1000, $verbose = false, $is_web = false)
{
    $nl = $is_web ? "<br>\n" : "\n";

    $total_stats = array(
        'deleted' => 0,
        'failed' => 0,
        'batches' => 0
    );

    do {
        $stats = clearCacheBatch($cache_dir, $batch_size, $verbose, $is_web);
        $total_stats['deleted'] += $stats['deleted'];
        $total_stats['failed'] += $stats['failed'];
        $total_stats['batches']++;

        if ($verbose && $stats['has_more']) {
            echo $nl . "--- Running batch " . ($total_stats['batches'] + 1) . " ---" . $nl;
        }

        // Flush output for web
        if ($is_web) {
            @ob_flush();
            @flush();
        }
    } while ($stats['has_more'] && $stats['deleted'] > 0);

    if ($verbose) {
        echo $nl . "=== Cache Clear Complete ===" . $nl;
        echo "Total deleted: {$total_stats['deleted']}" . $nl;
        echo "Total failed: {$total_stats['failed']}" . $nl;
        echo "Batches: {$total_stats['batches']}" . $nl;
    }

    return $total_stats;
}

/**
 * Print CLI usage information
 */
function printUsage()
{
    echo "Cache Cleaner - Standalone utility\n\n";
    echo "Usage:\n";
    echo "  php clear_cache.php [cache_directory] [batch_size] [options]\n\n";
    echo "Arguments:\n";
    echo "  cache_directory  Path to the cache directory (default: ./misc/_cache)\n";
    echo "  batch_size       Number of files to delete per batch (default: 1000)\n\n";
    echo "Options:\n";
    echo "  --all            Clear all files (run batches until complete)\n";
    echo "  --verbose, -v    Show detailed output\n";
    echo "  --help, -h       Show this help message\n\n";
    echo "Examples:\n";
    echo "  php clear_cache.php\n";
    echo "  php clear_cache.php ./misc/_cache\n";
    echo "  php clear_cache.php ./misc/_cache 500\n";
    echo "  php clear_cache.php ./misc/_cache 1000 --all\n";
    echo "  php clear_cache.php --all --verbose\n";
}

/**
 * Output web page header
 */
function webHeader($title = "Cache Cleaner")
{
    echo "<!DOCTYPE html>\n<html>\n<head>\n";
    echo "<meta charset=\"UTF-8\">\n";
    echo "<title>" . htmlspecialchars($title) . "</title>\n";
    echo "<style>\n";
    echo "body { font-family: monospace; padding: 20px; background: #f5f5f5; }\n";
    echo ".container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }\n";
    echo "h1 { color: #333; }\n";
    echo ".stats { background: #e8f5e9; padding: 15px; border-radius: 4px; margin: 10px 0; }\n";
    echo ".error { background: #ffebee; padding: 15px; border-radius: 4px; margin: 10px 0; color: #c62828; }\n";
    echo ".form-group { margin: 10px 0; }\n";
    echo "label { display: inline-block; width: 120px; }\n";
    echo "input[type=text], input[type=number] { padding: 5px; width: 300px; }\n";
    echo "button { padding: 10px 20px; background: #1976d2; color: white; border: none; border-radius: 4px; cursor: pointer; margin-top: 10px; }\n";
    echo "button:hover { background: #1565c0; }\n";
    echo ".log { background: #263238; color: #aed581; padding: 15px; border-radius: 4px; max-height: 400px; overflow-y: auto; font-size: 12px; }\n";
    echo "</style>\n";
    echo "</head>\n<body>\n<div class=\"container\">\n";
}

/**
 * Output web page footer
 */
function webFooter()
{
    echo "</div>\n</body>\n</html>";
}

// Detect execution context
$is_cli = (php_sapi_name() === 'cli');
$is_web = !$is_cli;

if ($is_cli) {
    // CLI execution
    $args = array_slice($argv, 1);

    // Check for help
    if (in_array('--help', $args) || in_array('-h', $args)) {
        printUsage();
        exit(0);
    }

    // Parse options
    $all = in_array('--all', $args);
    $verbose = in_array('--verbose', $args) || in_array('-v', $args);

    // Remove options from args
    $filtered_args = array();
    foreach ($args as $arg) {
        if (!in_array($arg, array('--all', '--verbose', '-v', '--help', '-h'))) {
            $filtered_args[] = $arg;
        }
    }
    $args = $filtered_args;

    // Get cache directory (optional, default ./misc/_cache)
    $cache_dir = !empty($args[0]) ? $args[0] : DEFAULT_CACHE_PATH;

    // Validate cache directory
    if (!file_exists($cache_dir)) {
        echo "Error: Directory does not exist: $cache_dir\n";
        exit(1);
    }

    if (!is_dir($cache_dir)) {
        echo "Error: Path is not a directory: $cache_dir\n";
        exit(1);
    }

    // Security check: path must contain "_cache"
    if (strpos($cache_dir, '_cache') === false) {
        echo "Error: For safety, directory path must contain '_cache'\n";
        exit(1);
    }

    // Get batch size (optional, default 1000)
    $batch_size = isset($args[1]) ? intval($args[1]) : DEFAULT_BATCH_SIZE;
    if ($batch_size <= 0) {
        $batch_size = DEFAULT_BATCH_SIZE;
    }

    echo "Cache Cleaner\n";
    echo "Directory: $cache_dir\n";
    echo "Batch size: $batch_size\n";
    echo "Processing oldest files first\n\n";

    if ($all) {
        clearCacheAll($cache_dir, $batch_size, $verbose, false);
    } else {
        $stats = clearCacheBatch($cache_dir, $batch_size, $verbose, false);

        if (!$verbose) {
            echo "Deleted: {$stats['deleted']}\n";
            echo "Failed: {$stats['failed']}\n";
        }

        if ($stats['has_more']) {
            echo "\nMore files remaining. Run again or use --all flag.\n";
        }
    }
} else {
    // Web execution
    header('Content-Type: text/html; charset=UTF-8');

    // Get parameters from GET/POST
    $cache_dir = isset($_REQUEST['path']) ? $_REQUEST['path'] : DEFAULT_CACHE_PATH;
    $batch_size = isset($_REQUEST['batch']) ? intval($_REQUEST['batch']) : DEFAULT_BATCH_SIZE;
    $all = isset($_REQUEST['all']) && $_REQUEST['all'];
    $verbose = isset($_REQUEST['verbose']) && $_REQUEST['verbose'];
    $run = isset($_REQUEST['run']);

    // Sanitize path - prevent directory traversal
    $cache_dir = str_replace('..', '', $cache_dir);

    webHeader("Cache Cleaner");

    echo "<h1>Cache Cleaner</h1>\n";

    // Show form
    echo "<form method=\"get\">\n";
    echo "<div class=\"form-group\">\n";
    echo "<label for=\"path\">Cache path:</label>\n";
    echo "<input type=\"text\" id=\"path\" name=\"path\" value=\"" . htmlspecialchars($cache_dir) . "\">\n";
    echo "</div>\n";
    echo "<div class=\"form-group\">\n";
    echo "<label for=\"batch\">Batch size:</label>\n";
    echo "<input type=\"number\" id=\"batch\" name=\"batch\" value=\"" . intval($batch_size) . "\" min=\"1\" max=\"10000\">\n";
    echo "</div>\n";
    echo "<div class=\"form-group\">\n";
    echo "<label>Options:</label>\n";
    echo "<input type=\"checkbox\" id=\"all\" name=\"all\" value=\"1\"" . ($all ? " checked" : "") . "> <label for=\"all\" style=\"width:auto\">Clear all</label>\n";
    echo "<input type=\"checkbox\" id=\"verbose\" name=\"verbose\" value=\"1\"" . ($verbose ? " checked" : "") . "> <label for=\"verbose\" style=\"width:auto\">Verbose</label>\n";
    echo "</div>\n";
    echo "<input type=\"hidden\" name=\"run\" value=\"1\">\n";
    echo "<button type=\"submit\">Clear Cache</button>\n";
    echo "</form>\n";

    // Execute if requested
    if ($run) {
        echo "<h2>Results</h2>\n";

        // Security check: path must contain "_cache"
        if (strpos($cache_dir, '_cache') === false) {
            echo "<div class=\"error\">Error: For safety, directory path must contain '_cache'</div>\n";
        } elseif (!file_exists($cache_dir) || !is_dir($cache_dir)) {
            echo "<div class=\"error\">Error: Directory does not exist: " . htmlspecialchars($cache_dir) . "</div>\n";
        } else {
            if ($verbose) {
                echo "<div class=\"log\">\n";
            }

            if ($all) {
                $stats = clearCacheAll($cache_dir, $batch_size, $verbose, true);
            } else {
                $stats = clearCacheBatch($cache_dir, $batch_size, $verbose, true);
            }

            if ($verbose) {
                echo "</div>\n";
            }

            echo "<div class=\"stats\">\n";
            echo "<strong>Deleted:</strong> {$stats['deleted']}<br>\n";
            echo "<strong>Failed:</strong> {$stats['failed']}<br>\n";
            if (!$all && !empty($stats['has_more'])) {
                echo "<strong>More files remaining:</strong> Yes (run again or check 'Clear all')<br>\n";
            }
            echo "</div>\n";
        }
    }

    webFooter();
}
