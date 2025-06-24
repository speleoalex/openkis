<?php
/**
 * @package Finis
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2024
 */
defined('_FNEXEC') or die('Restricted access');

// Function to execute PHP files in a specified directory
function executeAutoexecFiles($directoryPath) {
    if (file_exists($directoryPath) && false !== ($handle = opendir($directoryPath))) {
        $filesToRun = array();
        while (false !== ($file = readdir($handle))) {
            // Check if file is a PHP file and does not start with 'none_'
            if (FN_GetFileExtension($file) == "php") {
                $filesToRun[] = $file;
            }
        }
        closedir($handle);
        FN_NatSort($filesToRun);
        foreach ($filesToRun as $runFile) {
            include ("$directoryPath/$runFile");
        }
    }
}

// Execute files in 'src_finis' directory
executeAutoexecFiles("{$_FN['src_finis']}/include/autoexec.d/");

// Check if 'src_finis' and 'src_application' are different
if (realpath($_FN['src_finis']) != realpath($_FN['src_application'])) {
    // Execute files in 'src_application' directory
    executeAutoexecFiles("{$_FN['src_application']}/include/autoexec.d/");
}
