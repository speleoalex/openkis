<?php
// http://localhost/speleoalex/techmakers/applications.techmakers.it/finis/test_cron.php
// php /home/speleoalex/public_html/techmakers/applications.techmakers.it/finis/test_cron.php
error_reporting(E_ALL);
ini_set("display_errors","on");
require_once(__DIR__ . '/FINIS.php');

// Include the cron text field class
require_once(__DIR__ . '/include/xmetadbfrm_fields/xmetadbfrm_field_fn_cron_text.php');

$FINIS = new FINIS(); // include functions


// For debugging
function debugParts($schedule) {
    $parts = preg_split('/\s+/', $schedule);
    $result = "<div style='color: #666; font-size: 0.8em;'>";
    $result .= "Parts: [" . implode("], [", $parts) . "]<br/>";
    if (count($parts) >= 3) {
        $result .= "Date: " . $parts[0] . ", Time: " . $parts[1] . ", DOW: " . $parts[2];
    }
    $result .= "</div>";
    return $result;
}

// Create an instance of the class
$cronField = new xmetadbfrm_field_fn_cron_text();

// Display the test results
echo "<html><body>";
echo "<h1>Cron Schedule To Natural Language Test</h1>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Cron Schedule</th><th>Natural Language</th><th>Debug</th></tr>";
// Sample cron schedules to test
$schedules = [
    "*-*-* 13:30:0 5 # questo è un commento",      // Friday at 13:30:00
    "*-*-* 13:30:0 4 # questo è un commento",      // Thursday at 13:30:00
    "*-*-* 13:30:0 * # questo è un commento",      // Every day at 13:30:00
    "*-*-1 13:30:0 * # questo è un commento",      // On day 1 at 13:30:00
    "*-*-* *:*:* * # questo è un commento",        // Every day
    "*-12-25 0:0:0 * # questo è un commento",      // Christmas at midnight
    "*-*-* 9:0:0 1 # questo è un commento",        // Monday at 9am
    "*-*-* */1:20:10 * # questo è un commento",    // Every hour at minute 20 and second 10        
    "*-*-* *:10:10 *",                             // Every hour at minute 10 and second 10   
    "2025-2-7 3:0:0 *",                            // Specific date
    "*-*-* 14:30:45 0",                            // Sunday with seconds
    "*-6-15 12:0:0 *",                             // June 15th at noon
    "*-*-* */2:0:0 *"                              // Every 2 hours

    
];
foreach ($schedules as $schedule) {
    $naturalLanguage = $cronField->scheduleToNaturalLanguage($schedule);
    echo "<tr><td>$schedule</td><td>$naturalLanguage</td><td>" . debugParts($schedule) . "</td></tr>";
}

echo "</table>";
echo "</body></html>";
