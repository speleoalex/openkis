<?php

class xmetadbfrm_field_fn_cron_text
{
    function __construct()
    {
        
    }

    function show($params)
    {
        $html = "";
        $attributes = isset($params["htmlattributes"]) ? $params["htmlattributes"] : "";
        $style = "";
        $tooltip = $params['frm_help'];
        $onkeyup = "";
        $html = "";
        $html .= FN_TPL_ApplyTplFile(__DIR__ . "/xmetadbfrm_field_fn_cron_text.tp.html", $params);
        return $html;
    }

    function view($params)
    {
        $html = htmlspecialchars($this->scheduleToNaturalLanguage($params['value']));
        return $html;
    }

    function gridview($params)
    {
        return $this->view($params);
    }

    function scheduleToNaturalLanguage($scheduleString)
    {
        // Split the input string into separate lines
        $lines = explode("\n", $scheduleString);
        $descriptions = [];

        foreach ($lines as $line)
        {
            $line = trim($line);
            if (empty($line))
            {
                continue;
            }
            
            // Handle comments
            $commentPos = strpos($line, '#');
            $comment = '';
            if ($commentPos !== false) {
                $comment = trim(substr($line, $commentPos + 1));
                $line = trim(substr($line, 0, $commentPos));
            }

            // Parse the schedule parts
            $parts = preg_split('/\s+/', $line);
            // A schedule should have the format: date time dayOfWeek
            // For example: *-*-* 13:30:0 5
            if (count($parts) < 3) {
                $descriptions[] = $line; // Invalid format, return as-is
                continue;
            }
            
            $datePattern = $parts[0]; // *-*-*
            $timePattern = $parts[1]; // 13:30:0
            $weekday = $parts[2];     // 5
            
            $description = '';
            
            // Process day of week
            if (is_numeric($weekday) && $weekday >= 0 && $weekday <= 6) {
                $daysOfWeek = [
                    FN_Translate("Sunday"),
                    FN_Translate("Monday"),
                    FN_Translate("Tuesday"),
                    FN_Translate("Wednesday"),
                    FN_Translate("Thursday"),
                    FN_Translate("Friday"),
                    FN_Translate("Saturday")
                ];
                $description = $daysOfWeek[(int)$weekday];
            } elseif ($weekday === '*') {
                // Check for specific day in the month or full date
                $dateParts = explode('-', $datePattern);
                if (isset($dateParts[0]) && $dateParts[0] !== '*' && 
                    isset($dateParts[1]) && $dateParts[1] !== '*' && 
                    isset($dateParts[2]) && $dateParts[2] !== '*') {
                    // Full date specified (year-month-day)
                    $months = [
                        '1' => FN_Translate("January"),
                        '2' => FN_Translate("February"),
                        '3' => FN_Translate("March"),
                        '4' => FN_Translate("April"),
                        '5' => FN_Translate("May"),
                        '6' => FN_Translate("June"),
                        '7' => FN_Translate("July"),
                        '8' => FN_Translate("August"),
                        '9' => FN_Translate("September"),
                        '10' => FN_Translate("October"),
                        '11' => FN_Translate("November"),
                        '12' => FN_Translate("December")
                    ];
                    $month = isset($months[$dateParts[1]]) ? $months[$dateParts[1]] : $dateParts[1];
                    $description = $dateParts[2] . ' ' . $month . ' ' . $dateParts[0];
                } elseif (isset($dateParts[1]) && $dateParts[1] !== '*' && isset($dateParts[2]) && $dateParts[2] !== '*') {
                    // Month and day specified
                    $months = [
                        '1' => FN_Translate("January"),
                        '2' => FN_Translate("February"),
                        '3' => FN_Translate("March"),
                        '4' => FN_Translate("April"),
                        '5' => FN_Translate("May"),
                        '6' => FN_Translate("June"),
                        '7' => FN_Translate("July"),
                        '8' => FN_Translate("August"),
                        '9' => FN_Translate("September"),
                        '10' => FN_Translate("October"),
                        '11' => FN_Translate("November"),
                        '12' => FN_Translate("December")
                    ];
                    $month = isset($months[$dateParts[1]]) ? $months[$dateParts[1]] : $dateParts[1];
                    $description = $dateParts[2] . ' ' . $month;
                } elseif (isset($dateParts[2]) && $dateParts[2] !== '*') {
                    $description = FN_Translate("Day") . " " . $dateParts[2];
                } else {
                    $description = FN_Translate("Every day");
                }
            }

            // Process time
            $timeParts = explode(':', $timePattern);
            
            // Special case: if all time parts are wildcards (*:*:*), it means every second
            if ($timePattern === '*:*:*') {
                if (!empty($description)) {
                    $description .= ' ' . FN_Translate("every second");
                } else {
                    $description = FN_Translate("Every second");
                }
            } else {
                // Check if we have any specific time components (not all wildcards)
                $hasSpecificTime = false;
                for ($i = 0; $i < count($timeParts); $i++) {
                    if (isset($timeParts[$i]) && $timeParts[$i] !== '*') {
                        $hasSpecificTime = true;
                        break;
                    }
                }
                
                if ($hasSpecificTime) {
                // Handle interval patterns like "*/1"
                if (isset($timeParts[0]) && strpos($timeParts[0], '/') !== false) {
                    list(, $interval) = explode('/', $timeParts[0]);
                    $timeStr = FN_Translate("every") . " $interval " . FN_Translate("hours");
                    if (isset($timeParts[1]) && $timeParts[1] !== '*' && $timeParts[1] !== '0') {
                        $timeStr .= " " . FN_Translate("at minute") . " " . $timeParts[1];
                    }
                    if (isset($timeParts[2]) && $timeParts[2] !== '*' && $timeParts[2] !== '0') {
                        $timeStr .= " " . FN_Translate("second") . " " . $timeParts[2];
                    }
                } else {
                    // Handle specific times or partial time specifications
                    $timeComponents = [];
                    
                    if (isset($timeParts[0]) && $timeParts[0] !== '*') {
                        // Specific hour
                        $hour = $timeParts[0];
                        $minute = (isset($timeParts[1]) && $timeParts[1] !== '*') ? $timeParts[1] : '00';
                        $second = (isset($timeParts[2]) && $timeParts[2] !== '*') ? $timeParts[2] : '00';
                        
                        // Format time properly
                        $timeStr = sprintf("%02d:%02d", $hour, $minute);
                        if ($second !== '00' && $second !== '0') {
                            $timeStr .= ":" . sprintf("%02d", $second);
                        }
                    } else {
                        // Hour is *, but we have specific minutes/seconds
                        $timeStr = FN_Translate("every hour");
                        if (isset($timeParts[1]) && $timeParts[1] !== '*' && $timeParts[1] !== '0') {
                            $timeStr .= " " . FN_Translate("at minute") . " " . sprintf("%02d", $timeParts[1]);
                        }
                        if (isset($timeParts[2]) && $timeParts[2] !== '*' && $timeParts[2] !== '0') {
                            $timeStr .= " " . FN_Translate("second") . " " . sprintf("%02d", $timeParts[2]);
                        }
                    }
                }
                
                if (!empty($description)) {
                    $description .= ' ' . FN_Translate("at") . ' ' . $timeStr;
                } else {
                    $description = FN_Translate("at") . ' ' . $timeStr;
                }
                }
            }

            // Add the comment if present
            if (!empty($comment)) {
                if (!empty($description)) {
                    $description .= ' (' . $comment . ')';
                } else {
                    $description = $comment;
                }
            }
            
            $descriptions[] = !empty($description) ? $description : $line;
        }

        // Join all descriptions
        return implode(" - ", $descriptions);
    }
}