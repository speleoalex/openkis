<?php
ob_start();
require_once "loadfinis.php";
require_once "{$_FN['src_finis']}/modules/dbview/FNDBVIEW.php";
/*
  $Tablerilievi=FN_XMDBForm("ctl_surveys");
  $rilievi=$Tablerilievi->xmltable->GetRecords();
 */
$params = array();
$tablename = "ctl_surveys";
$mod_survey = "survey";
$title = FN_Translate("surveys");
$table_link = "codecave";
if (!empty($_GET['t']) && $_GET['t'] == "artificials")
{
    $tablename = "ctl_surveys_artificials";
    $mod_survey = "survey_artificials";
    $title = $title . " art";
    $table_link = "codeartificial";
}
if (!empty($_GET['t']) && $_GET['t'] == "areas")
{
    $tablename = "ctl_areas";
    $mod_survey = "areas";
    $title = $title . " aree";
    $table_link = "code";
}

// The map page includes this script for every visitor. Someone who may not
// view the surveys section gets an empty script, not a 403: no survey data is
// sent either way, and the map shows no error in the browser console.
if (!FN_UserCanViewSection($mod_survey))
{
    while (false !== @ob_end_clean());
    header("Cache-Control: no-store");
    header('Content-type: application/javascript');
    die("/* surveys: no access */\n");
}
$Tablerilievi = FN_XMDBForm($tablename);
$config = FN_LoadConfig("modules/dbview/config.php", "$mod_survey");
$dbview = new FNDBVIEW($config);
$params['fields'] = "id,filekml,$table_link";
$area_cave_codes = array();
// When an area's sheet opens the map, show only cave surveys belonging to
// caves in that area (not every survey in the register).
if ($tablename == "ctl_surveys" && !empty($_GET['cave_area']))
{
    $caves_config = FN_LoadConfig("modules/dbview/config.php", "caves");
    $caves_view = new FNDBVIEW($caves_config);
    $area = addslashes(str_replace(array("\\", "%", "_"), array("\\\\", "\\%", "\\_"), $_GET['cave_area']));
    $caves = $caves_view->GetResults($caves_config, array(
        "fields" => "code",
        "appendquery" => "(areas LIKE '$area' OR areas LIKE '$area,%' OR areas LIKE '%,$area,%' OR areas LIKE '%,$area')"));
    $codes = array();
    if (is_array($caves))
    {
        foreach ($caves as $area_cave)
        {
            if (!empty($area_cave['code']))
                $area_cave_codes[trim($area_cave['code'])] = true;
        }
    }
    if (!count($area_cave_codes))
        $params['appendquery'] = "id = 0";
}
$rilievi = $dbview->GetResults($config, $params);
if ($tablename == "ctl_surveys" && !empty($_GET['cave_area']) && is_array($rilievi))
{
    $rilievi = array_values(array_filter($rilievi, function ($rilievo) use ($area_cave_codes, $table_link)
    {
        return isset($area_cave_codes[trim(strval($rilievo[$table_link]))]);
    }));
}
while (false !== @ob_end_clean()
);
ob_start();
header('Access-Control-Allow-Origin: *');
header('Content-type: application/javascript');
$kml = array();
$visible = "false";

if (isset($_GET['mod']) && $_GET['mod'] == "caves" && $tablename == "ctl_surveys")
    $visible = "true";
if (isset($_GET['mod']) && $_GET['mod'] == "artificials" && $tablename == "ctl_surveys_artificials")
    $visible = "true";
if (isset($_GET['mod']) && $_GET['mod'] == "areas" && $tablename == "ctl_areas")
    $visible = "true";
if ($tablename == "ctl_surveys" && !empty($_GET['cave_area']))
    $visible = "true";

if (is_array($rilievi))
{
    $i = 1;
    foreach ($rilievi as $rilievo)
    {
        if (!empty($rilievo['filekml']))
        {
            $km = $Tablerilievi->xmltable->get_file($rilievo, 'filekml');
            //(title, path, isBaselayer, visible, showPointNames, searchable, layergroup)
            echo "\nOPS_Map.addKmlLayer(\"ril_{$i}_{$rilievo[$table_link]}\", \"$km\", false, $visible,false,false,'" . $title . "');";
        }
        $i++;
    }
}
?>
OPS_Map.addLayerSwitcher();
<?php
$str = ob_get_clean();
if ($_FN['enable_compress_gzip'])
{
    header("Content-Encoding: gzip");
    die(gzencode($str));
}
else
    die($str);
?>
