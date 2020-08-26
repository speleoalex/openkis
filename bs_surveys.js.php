<?php
ob_start();
require_once "include/flatnux.php";
require_once "modules/dbview/FNDBVIEW.php";
/*
  $Tablerilievi=FN_XmlForm("ctl_surveys");
  $rilievi=$Tablerilievi->xmltable->GetRecords();
 */
$params=array();
$Tablerilievi=FN_XmlForm("ctl_surveys");
$config=FN_LoadConfig("modules/dbview/config.php","survey");
$dbview=new FNDBVIEW($config);
$params['fields']="id,filekml,codecave";
$rilievi=$dbview->GetResults($config,$params);

while(false!== @ob_end_clean()
);
ob_start();
header('Access-Control-Allow-Origin: *');
header('Content-type: application/javascript');
$kml=array();
$visible="false";
if (isset($_GET['mod']) && $_GET['mod']== "caves")
    $visible="true";
if (is_array($rilievi))
{
    foreach($rilievi as $rilievo)
    {
        if ($rilievo['filekml']!= "")
        {
            $km=$Tablerilievi->xmltable->get_file($rilievo,'filekml');
            echo "\nOPS_Map.addKmlLayer(\"ril_{$rilievo['codecave']}\", \"$km\", false, $visible,false,false,'".FN_Translate("surveys")."');";
        }
    }
}
?>
OPS_Map.addLayerSwitcher();
<?php
$str=ob_get_clean();
if ($_FN['enable_compress_gzip'])
{
    header("Content-Encoding: gzip");
    die(gzencode($str));
}
else
    die($str);
?>