<?php
global $_FN;

$data = array();
$data['siteurl'] = $_FN['siteurl'];
$data['user'] = $_FN['user'];
$_FN['getvars']=$_GET;
$_FN['postvars']=$_POST;
$jsonData = json_encode($_FN);

// Command to execute the Python script, passing the JSON as an argument
$command = "python3 {$_FN['src_application']}/sections/{$_FN['mod']}/section.py '" . $jsonData . "' 2>&1";

// Set up the descriptors for proc_open
$descriptorspec = array(
   0 => array("pipe", "r"),  // stdin
   1 => array("pipe", "w"),  // stdout
   2 => array("pipe", "w")   // stderr
);

// Open the process
$process = proc_open($command, $descriptorspec, $pipes);

if (is_resource($process)) {
    // Set stream to non-blocking
    stream_set_blocking($pipes[1], 0);
    stream_set_blocking($pipes[2], 0);

    // Flush output buffer
    ob_implicit_flush(true);
    ob_end_flush();

    // Read output in real-time
    while (!feof($pipes[1])) {
        $output = fgets($pipes[1]);
        if ($output !== false) {
            echo $output;
            // Flush the output to the browser
            flush();
        }
        
        // Also check for any error output
        $error = fgets($pipes[2]);
        if ($error !== false) {
            echo "Error: " . $error;
            flush();
        }
        
        // Small delay to prevent CPU overuse
        usleep(10000);  // 10ms delay
    }

    // Close the pipes
    fclose($pipes[0]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    // Close the process
    proc_close($process);
}
