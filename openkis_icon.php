<?php
global $_FN;
require_once "loadfinis.php";
require_once "{$_FN['src_finis']}/modules/dbview/FNDBVIEW.php";
FN_LoadMessagesFolder("extra/openkis");

$mod="caves";

$config=FN_LoadConfig("modules/dbview/config.php",$mod);
$dbview=new FNDBVIEW($config);
$tablename=$config['tables'];
$params['fields']="code,latitude,longitude,elevation,name,synonyms,depth_total,depth_negative,depth_positive,lenght_total,meteorology,fauna";
$results=$dbview->GetResults($config,$params);
//dprint_r($results);
foreach($results as $item)
{
    $icon=openkis_GetIcon($item,$mod);
}
?>
