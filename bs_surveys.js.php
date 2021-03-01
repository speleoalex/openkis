<?php
ob_start();
require_once "include/flatnux.php";
require_once "modules/dbview/FNDBVIEW.php";
/*
  $Tablerilievi=FN_XmlForm("ctl_surveys");
  $rilievi=$Tablerilievi->xmltable->GetRecords();
 */
$params=array();
$tablename="ctl_surveys";
$mod_survey="survey";
$title=FN_Translate("surveys");
$table_link="codecave";
if (!empty($_GET['t']) && $_GET['t'] == "artificials")
{
    $tablename="ctl_surveys_artificials";
    $mod_survey="survey_artificials";
    $title=$title." art";
    $table_link="codeartificial";
}
if (!empty($_GET['t']) && $_GET['t'] == "areas")
{
    $tablename="ctl_areas";
    $mod_survey="areas";
    $title=$title." aree";
    $table_link="code";
}

$Tablerilievi=FN_XmlForm($tablename);
$config=FN_LoadConfig("modules/dbview/config.php","$mod_survey");
$dbview=new FNDBVIEW($config);
$params['fields']="id,filekml,$table_link";
$rilievi=$dbview->GetResults($config,$params);
while(false!== @ob_end_clean()
);
ob_start();
header('Access-Control-Allow-Origin: *');
header('Content-type: application/javascript');
$kml=array();
$visible="false";

if (isset($_GET['mod']) && $_GET['mod'] == "caves" && $tablename == "ctl_surveys")
    $visible="true";
if (isset($_GET['mod']) && $_GET['mod'] == "artificials" && $tablename == "ctl_surveys_artificials")
    $visible="true";
if (isset($_GET['mod']) && $_GET['mod'] == "areas" && $tablename == "ctl_areas")
    $visible="true";


$visible="false";


if (is_array($rilievi))
{
    $i=1;
    foreach($rilievi as $rilievo)
    {
        if ($rilievo['filekml']!= "")
        {
            $km=$Tablerilievi->xmltable->get_file($rilievo,'filekml');
            //(title, path, isBaselayer, visible, showPointNames, searchable, layergroup)
            echo "\nOPS_Map.addKmlLayer(\"ril_{$i}_{$rilievo[$table_link]}\", \"$km\", false, $visible,false,false,'".$title."');";
        }
        $i++;
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