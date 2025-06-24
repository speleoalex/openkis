<?php

// The FN_LoopManager class allows an operation to be executed at regular intervals using a timer string.
// The timer string specifies when the operation should be executed and follows a syntax similar to the Cron format, composed of seven fields separated by spaces:
//
// Year (Y): can be a specific number (e.g., 2023), an asterisk (*) for any year, or a range (*/n) to indicate every n years.
// Month (m): can be a number from 1 to 12, an asterisk (*) for any month, or a range (*/n) to indicate every n months.
// Day (d): can be a number from 1 to 31, an asterisk (*) for any day, or a range (*/n) to indicate every n days.
// Hour (H): can be a number from 0 to 23, an asterisk (*) for any hour, or a range (*/n) to indicate every n hours.
// Minute (i): can be a number from 0 to 59, an asterisk (*) for any minute, or a range (*/n) to indicate every n minutes.
// Second (s): can be a number from 0 to 59, an asterisk (*) for any second, or a range (*/n) to indicate every n seconds.
// Day of the week (w): can be a number from 0 to 6 (0 for Sunday, 1 for Monday, ..., 6 for Saturday), an asterisk (*) for any day of the week, or a range (*/n) to indicate every n weekdays.
//
// Examples of timer strings:
//
// "*-*-* *:*/5:* *": executes the operation every 5 minutes, regardless of the hour, day, month, year, or day of the week.
// "2023-*-* 12:00:00 *": executes the operation year 2023 every day at 12:00:00 in 2023.
// "*-*-12 00:00:00 *": executes the operation on the 12st day of every month at midnight.
// "*-1-15 00:00:00 *": executes the operation on January 15st of every year at midnight.
// "*-*-* *:*/30:05 *": executes the operation every 30 seconds at the 5th second.
// "*-*-* 04:00:00 1": executes the operation every Monday at 4:00.
//
// Usage:
// $loopManager = new FN_LoopManager("PrintTime", "*-*-* *:*/5:* *", true); // every 5 minutes
// $loopManager->run();
// $loopManager2 = new FN_LoopManager("PrintTime2", "*-*-* *:*/2:* *", true); // every 2 minutes
// $loopManager2->run();
// $loopManager3 = new FN_LoopManager("PrintTime3", "*-*-* *:*/30:05 *", true); // every 30 seconds at the 05th second
// $loopManager3->run();


class FN_LoopManager
{

    private $fileId = 0;
    private $startTime = 0;
    private $lockFile = '';
    private $istanceId = '';
    private $maxExecutionTime = 120;
    private $restartThreshold = 10;
    private $lockTimeout = 10;
    private $callback = "";
    private $timerString = "";
    private $time_performed = false;
    private $debug = false;
    private $stateFile = ''; // File to store timer state

    public function __construct($callback = "", $timerString = "*-*-* *:*:00", $debugFile = false) //Y-m-d H:i:s
    {
        $this->callback = $callback;
        if ($this->validateTimerString($timerString)) {
            $this->timerString = $timerString;
        }

        // Generate lock file name based on the current script
        $scriptName = basename($_SERVER['SCRIPT_FILENAME'], '.php');
        $this->istanceId = md5($scriptName . $callback . $timerString);

        if (false && is_writable(sys_get_temp_dir())) {
            $this->lockFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $this->istanceId . '.lock';
            $this->stateFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $this->istanceId . '.state'; // State file

        } else {
            global $_FN;
            if (!file_exists("{$_FN['src_application']}/{$_FN['datadir']}" . DIRECTORY_SEPARATOR . "tmp")) {
                mkdir("{$_FN['src_application']}/{$_FN['datadir']}" . DIRECTORY_SEPARATOR . "tmp");
            }
            $this->lockFile = realpath("{$_FN['src_application']}/{$_FN['datadir']}" . DIRECTORY_SEPARATOR . "tmp") . DIRECTORY_SEPARATOR . $this->istanceId . '.lock';
            $this->stateFile = realpath("{$_FN['src_application']}/{$_FN['datadir']}" . DIRECTORY_SEPARATOR . "tmp") . DIRECTORY_SEPARATOR  . $this->istanceId . '.state'; // State file
        }

        $this->debug = $debugFile;
        $this->setMaxExecutionTime();
        // Load the previous state if it exists
        $this->loadState();
    }

    private function file_log_contents($filename, $contents, $max_lines = 0)
    {
        // Append the new contents to the file
        file_put_contents($filename, $contents, FILE_APPEND);
        // Check if max_lines is greater than 0
        if ($max_lines > 0) {
            // Read all the lines from the file
            $file_lines = file($filename, FILE_IGNORE_NEW_LINES);

            // Check if the number of lines exceeds max_lines
            if (count($file_lines) > $max_lines) {
                // Calculate the number of lines to remove
                $lines_to_remove = count($file_lines) - $max_lines;

                // Remove the excess lines from the beginning
                $file_lines = array_slice($file_lines, $lines_to_remove);

                // Write the remaining lines back to the file
                file_put_contents($filename, implode(PHP_EOL, $file_lines) . PHP_EOL);
            }
        }
    }

    private function setMaxExecutionTime()
    {
        // Get the current max execution time
        $currentMaxExecutionTime = ini_get('max_execution_time');

        // If the current max execution time is lower than our desired time, or if it's set to 0 (unlimited),
        // we set it to our desired time plus a buffer
        if ($currentMaxExecutionTime < $this->maxExecutionTime || $currentMaxExecutionTime == 0) {
            // Add a buffer of 30 seconds to our max execution time
            $newMaxExecutionTime = $this->maxExecutionTime + 30;

            // Try to set the new max execution time
            if (@ini_set('max_execution_time', $newMaxExecutionTime) === false) {
                $this->log("Warning: Unable to set max_execution_time to $newMaxExecutionTime seconds");
            } else {
                $this->log("Set max_execution_time to $newMaxExecutionTime seconds");
            }
        }

        // Disable the time limit for the script execution
        set_time_limit(0);
    }

    private function loadState()
    {
        // Check if state file exists and load the previous state
        if (file_exists($this->stateFile)) {
            $stateData = file_get_contents($this->stateFile);
            $state = unserialize($stateData);
            if (isset($state['time_performed'])) {
                $this->time_performed = $state['time_performed'];
            }
        }
    }

    private function saveState()
    {
        // Save the current state to the state file
        $state = ['time_performed' => $this->time_performed];
        file_put_contents($this->stateFile, serialize($state));
    }

    public static function validateTimerString($timerString)
    {
        // Add the seventh field for the day of the week and allow comments
        $pattern = '/^(\*|\d{4}|\*\/\d+)-(\*|\d+|\*\/\d+)-(\*|\d+|\*\/\d+) (\*|\d+|\*\/\d+):(\*|\d+|\*\/\d+):(\*|\d+|\*\/\d+) (\*|\d|\*\/\d+)(\s*#.*)?$/';

        if (!preg_match($pattern, $timerString, $matches)) {
            return false;
        }

        // Validate valid ranges, including the day of the week
        for ($i = 1; $i <= 7; $i++) {
            if ($matches[$i] !== '*') {
                if (strpos($matches[$i], '/') !== false) {
                    list($_, $interval) = explode('/', $matches[$i]);
                    $value = intval($interval);
                } else {
                    $value = intval($matches[$i]);
                }

                switch ($i) {
                    case 1: // Year
                        if ($value < 1970 || $value > 2100)
                            return false;
                        break;
                    case 2: // Month
                        if ($value < 1 || $value > 12)
                            return false;
                        break;
                    case 3: // Day
                        if ($value < 1 || $value > 31)
                            return false;
                        break;
                    case 4: // Hour
                        if ($value < 0 || $value > 23)
                            return false;
                        break;
                    case 5: // Minute
                    case 6: // Second
                        if ($value < 0 || $value > 59)
                            return false;
                        break;
                    case 7: // Day of the week
                        if ($value < 0 || $value > 6)
                            return false;
                        break;
                }
            }
        }

        return true;
    }

    private function log($str)
    {
        if ($this->debug) {
            $str = "[Istance " . $this->istanceId . "] $str";
            $this->file_log_contents($this->debug, "\n" . FN_Now() . "$str ", 500);

            echo "\n$str";
            // Flush the output buffer and close the connection to the client
            @ob_end_flush(); // Flush (send) the output buffer
            @flush(); // Force it to send
        }
    }

    public function run()
    {
        $this->log("lockFile:{$this->lockFile}");
        if (empty($_GET[$this->istanceId])) {
            $this->log("Background script launch " . FN_Now());
            $this->restartScript();
            return;
        }
        $this->log("Istance {$this->istanceId} " . date("Y-m-d H:i:s"));
        $lastExecution = 0;
        while (true) {
            $this->keepLoop();
            $callback = $this->callback;
            // Check if it's time to execute based on timerString
            if ($this->isTimeToExecute() === true) {
                $now = time();
                if ($now != $lastExecution) { // Esegui solo una volta per secondo
                    $lastExecution = $now;
                    if (function_exists($callback)) {
                        $this->log("Execute $callback {$this->timerString}");
                        $callback();
                        $this->log("End execute $callback {$this->timerString}");
                    } elseif ($this->isValidURL($callback)) {
                        $this->log("Execute URL $callback {$this->timerString}");
                        $this->callUrl($callback);
                        $this->log("End execute $callback {$this->timerString}");
                    }
                    else{
                        $this->log("Error Execute $callback {$this->timerString}");

                    }
                }
            }
            usleep(500);
        }
    }

    private function isValidURL($string)
    {
        // Use filter_var to validate the URL
        return filter_var($string, FILTER_VALIDATE_URL) !== false;
    }

    private function keepLoop()
    {
        if (!$this->fileId) {
            $this->initializeLoop();
        }

        // Update the lock file timestamp to indicate the script is still running
        touch($this->lockFile);
        // Check remaining execution time
        $elapsedTime = time() - $this->startTime;
        $remainingTime = $this->maxExecutionTime - $elapsedTime;

        // Restart the script if the remaining time is less than the threshold
        if ($remainingTime < $this->restartThreshold) {
            $this->log("remainingTime < this->restartThreshold");
            $this->restartScript();
        }
    }

    private function initializeLoop()
    {
        // Ignore user abort to allow the script to continue running even if the client disconnects
        ignore_user_abort(true);

        // Start time of execution
        $this->startTime = time();
        $this->fileId = md5(file_get_contents(__FILE__));

        // Register shutdown function to remove lock file on exit
        register_shutdown_function(function () {
            if (file_exists($this->lockFile)) {
                //unlink($this->lockFile);
            }
        });

        $this->checkAndCreateLockFile();

        // Inform the client that the script has started
        $this->log("Script avviato " . FN_Now());
        // If using FastCGI, finish the request to close the connection properly
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }

    private function checkAndCreateLockFile()
    {
        if (file_exists($this->lockFile)) {
            // Check the age of the lock file
            $lockFileAge = time() - filemtime($this->lockFile);
            if ($lockFileAge < $this->lockTimeout) {
                $this->log("Script is already running");
                exit("Script is already running.\n");
            } else {
                // Remove the stale lock file
                unlink($this->lockFile);
            }
        }
        // Create a lock file to indicate that the script is running
        file_put_contents($this->lockFile, $this->fileId);
    }

    private function restartScript()
    {
        // Save the state before restarting
        $this->saveState();

        // Remove the lock file before restarting
        @unlink($this->lockFile);

        // Get the current script URL
        $scriptUrl = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        // Determine if the URL already has a query string
        $separator = (strpos($scriptUrl, '?') !== false) ? '&' : '?';

        if (empty($_GET[$this->istanceId])) {
            $scriptUrl .= "{$separator}{$this->istanceId}=1";
        }

        $this->log("Restarting script: $scriptUrl");
        $this->callUrl($scriptUrl);

        if (empty($_GET[$this->istanceId])) {
            return;
        }

        exit("Restarting script: $scriptUrl \n");
    }

    private function callUrl($scriptUrl)
    {
        $this->log("callUrl:$scriptUrl");
        $context = stream_context_create([
            'http' => [
                'timeout' => 1 // timeout in secondi
            ]
        ]);
        $response = @file_get_contents($scriptUrl, false, $context);
        return $response;
    }

    /**
     * Determines if it's time to execute the callback based on the timer string
     * 
     * This function analyzes the timer string in "Y-m-d H:i:s w" format and checks
     * if the current time matches the specified criteria. It also implements
     * an anti-duplication mechanism to prevent multiple executions within the same
     * time interval.
     * 
     * @return bool true if it's time to execute, false otherwise
     */
    private function isTimeToExecute()
    {
        // Get the current Unix timestamp
        $currentTime = time();
        
        // Split the timer string into its main components
        // Format: "Y-m-d H:i:s w" where w is the day of the week
        $parts = explode(' ', $this->timerString);
        $dateParts = explode('-', $parts[0]); // [Year, Month, Day]
        $timeParts = explode(':', $parts[1]);  // [Hour, Minute, Second]
        $weekDayPart = $parts[2];              // Day of week (0-6)

        // Initially assume all criteria match
        $match = true;
        
        // Keep track of the position of the last part with interval (*/n)
        // This will be used to calculate the correct time divisor
        $partPos = "";
        
        // Iterate through all 7 time components
        // 0=Year, 1=Month, 2=Day, 3=Hour, 4=Minute, 5=Second, 6=DayOfWeek
        for ($i = 0; $i < 7; $i++) {
            // Select the correct part of the timer string based on the index
            $part = $i < 3 ? $dateParts[$i] : ($i < 6 ? $timeParts[$i - 3] : $weekDayPart);
            
            // Get the corresponding current value from the actual time
            // For the first 6 values use date/time components, for the 7th use day of week
            $currentValue = $i < 6 ? date(['Y', 'm', 'd', 'H', 'i', 's'][$i], $currentTime) : date('w', $currentTime);

            // If the part is not a wildcard (*), check for match
            if ($part != '*') {
                // Handle intervals in "*/n" format (e.g. "*/5" = every 5 units)
                if (strpos($part, '/') !== false) {
                    $partPos = $i; // Store position to calculate divisor
                    list(, $interval) = explode('/', $part);
                    
                    // Check if current value is divisible by the interval
                    // If not, the criteria is not satisfied
                    if (intval($currentValue) % intval($interval) !== 0) {
                        $match = false;
                        break;
                    }
                } 
                // Handle specific values (e.g. "12" = only when it's 12)
                elseif ($part != $currentValue) {
                    $match = false;
                    break;
                }
            }
        }
        
        // Map positions to their equivalent in seconds for anti-duplication calculation
        // $i 0 = year (31536000 seconds)
        // $i 1 = month (2592000 seconds, approximate)
        // $i 2 = day (86400 seconds)
        // $i 3 = hour (3600 seconds)
        // $i 4 = minutes (60 seconds)
        // $i 5 = seconds (1 second)
        $div = 1;
        if ($partPos == 5) {
            $div = 1;        // Check per second
        } elseif ($partPos == 4) {
            $div = 60;       // Check per minute
        } elseif ($partPos == 3) {
            $div = 3600;     // Check per hour
        } elseif ($partPos == 2) {
            $div = 86400;    // Check per day
        } elseif ($partPos == 1) {
            $div = 2592000;  // Check per month (30 days)
        } elseif ($partPos == 0) {
            $div = 31536000; // Check per year (365 days)
        }

        // If all time criteria match, apply anti-duplication logic
        if ($match) {
            // First execution: mark the time and allow execution
            if ($this->time_performed === false) {
                $this->time_performed = $currentTime;
                $match = true;
            } 
            // Check if we're still in the same time interval as the last execution
            // Divide both times by the divisor and compare them as integers
            elseif (intval($currentTime / $div) == intval($this->time_performed / $div)) {
                // Same interval: block execution to prevent duplications
                $match = false;
            } 
            // New interval: update time and allow execution
            else {
                $this->time_performed = $currentTime;
                $match = true;
            }
        }
        
        // Save current state to file for persistence across restarts
        $this->saveState();
        
        return $match;
    }
}
