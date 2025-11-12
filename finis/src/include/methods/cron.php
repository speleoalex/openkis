<?php
global $_FN;
require_once("{$_FN['src_finis']}/include/classes/FN_LoopManager.php");
FN_Install("misc/fndatabase/fn_cron.php");
$finis = new FINIS();
$lines = array();
if (!file_exists("{$_FN['src_application']}/{$_FN['datadir']}/{$_FN['database']}/fn_cron.php")) {
    FN_Copy("{$_FN['src_finis']}/include/install/misc/fndatabase/fn_cron.php", "{$_FN['src_application']}/{$_FN['datadir']}/{$_FN['database']}/fn_cron.php");
}


function FN_CronTest()
{
    global $_FN;
    // Simulate some processing work
    echo "\nPrintTime ";
    $curtime = date("Y-m-d H:i:s");
    $logfile = "{$_FN['src_application']}/{$_FN['datadir']}/curdate.log.txt";
    file_put_contents($logfile, "\n$curtime", FILE_APPEND);

    // Limita il file alle ultime 1000 righe
    $lines = @file($logfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines !== false && count($lines) > 1000) {
        $lines = array_slice($lines, -1000);
        file_put_contents($logfile, implode("\n", $lines) . "\n");
    }
    echo "\n";
    @ob_end_flush(); // Flush (send) the output buffer
    @flush(); // Force it to send
}

echo "<pre>";
$debugFile = "{$_FN['src_application']}/{$_FN['datadir']}/log/cron.log";
$tablefn_cron = FN_XMDBTable("fn_cron");
$items = $tablefn_cron->GetRecords();
// *-*-* *:*:*/20 *
$i = 0;
$loopManager = array();
foreach ($items as $nrecord => $item) {
    $nrecord++;
    $lines = explode("\n", $item['cron_lines']);
    foreach ($lines as $nLine => $line) {
        $nLine++;
        $line = str_replace("\r", "", trim(ltrim($line)));
        if ($line != "") {
            if ($item['operation'] && FN_LoopManager::validateTimerString($line)) {
                echo ("\nExecution schedule manager ($nrecord-$nLine):$line");
                $loopManager[$i] = new FN_LoopManager($item['operation'], $line, $debugFile);
                $loopManager[$i]->run(function() use ($tablefn_cron, $item) {
                    $tablefn_cron->UpdateRecord(array("last_execution" => FN_Now()), $item['id']);
                });
                $i++;
            } else {
                echo ("\nsyntax error item $nrecord on line $nLine:" . $line);
            }
        }
    }
}


echo "/<pre>";
