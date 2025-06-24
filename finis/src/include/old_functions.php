<?php
/**
 * @package Finis
 * @author Alessandro Vernassa 
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
defined('_FNEXEC') or die('Restricted access');

/**
 * Function to include PHP scripts from a specified directory
 * 
 * @param string $directoryPath Path to the directory to scan for PHP files
 */
function FN_includeAutoexecFiles($directoryPath) {
    // Check if the directory exists and can be opened
    if (file_exists($directoryPath) && false !== ($handle = opendir($directoryPath))) {
        $filestorun = array();

        // Read files from the directory
        while (false !== ($file = readdir($handle))) {
            // Check if the file is a PHP file and does not start with "none_"
            if (FN_GetFileExtension($file) == "php" && !preg_match("/^none_/si", $file)) {
                $filestorun[] = $file;
            }
        }
        closedir($handle);

        // Sort the files naturally
        FN_NatSort($filestorun);

        // Include each file
        foreach ($filestorun as $runfile) {
            include ("$directoryPath/$runfile");
        }
    }
}

// Include scripts from the Finis source directory
FN_includeAutoexecFiles("{$_FN['src_finis']}/include/autoexec.d/");

// Include scripts from the application source directory if it differs from the Finis source directory
if (realpath($_FN['src_finis']) != realpath($_FN['src_application'])) {
    FN_includeAutoexecFiles("{$_FN['src_application']}/include/autoexec.d/");
}
