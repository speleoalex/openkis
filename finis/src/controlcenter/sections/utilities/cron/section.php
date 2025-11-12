<?php
global $_FN;
FN_Install("misc/fndatabase/fn_cron.php",true);
$params['max_cell_text_lenght']=1024;

FNCC_XMETATableEditor("fn_cron",$params);
echo "<h3>Cron service:</h3>";
echo "<iframe style=\"border:1px solid #dadada;width:100%;height:100px\" src=\"{$_FN['siteurl']}?fnapp=cron\"></iframe><br />";
echo "<h3>Logs:</h3>";
echo "<a href=\"{$_FN['siteurl']}/{$_FN['datadir']}/log/cron.log\">Download cron.log</a>";

// Read the log file into an array
$logfile = "{$_FN['datadir']}/log/cron.log";


$loglines = [];
if (file_exists($logfile))
{
    $loglines = file($logfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
}

// Get the last 100 lines
$last_lines = array_slice($loglines, -100);

// Convert the array to a string
$logstring = implode("\n", $last_lines);

// Sanitize the log string for HTML output
$logstring = htmlentities($logstring);

// Display the log string
echo "<pre>$logstring</pre>";

