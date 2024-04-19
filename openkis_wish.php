<?php

ob_start();
global $_FN;
require_once "include/flatnux.php";
require_once "modules/dbview/FNDBVIEW.php";

//Liguria: https://www.catastogrotte.net/liguria/openkis_wish.php?mod=caves




FN_LoadMessagesFolder("extra/openkis");
$exclude = FN_GetParam("exclude", $_GET, "flat");
$minimal = FN_GetParam("minimal", $_GET, "flat");
$codes = FN_GetParam("filter_code", $_GET, "flat");
$big_icons = !empty($_GET['big_icons']);
$mod = $_FN['mod'];
if ($mod == "")
{
    $mod = "caves";
}
foreach ($_REQUEST as $k => $v)
{
    $params[$k] = $v;
}
if (!file_exists("sections/$mod"))
{
    die();
}
$config = FN_LoadConfig("modules/dbview/config.php", $mod);


$dbview = new FNDBVIEW($config);
$tablename = $config['tables'];
$table = FN_XmlTable($tablename);
//https://www.catastogrotte.net/liguria/openkis_wish.php?mod=artificials&debug=1

$fields = array();
//$params['fields'] = "id,country,code,latitude,longitude,location,localita,marine,lake,elevation,name,synonyms,depth_total,depth_negative,depth_positive,lenght_total,closed,destroyed,userinsert,userupdate";
$params['fields'] = "id,country,code,latitude,longitude,location,localita,marine,lake,elevation,name,synonyms,depth_total,depth_negative,depth_positive,lenght_total,closed,destroyed";
$idresult = false;
openkis_UpdateCoords("caves");
openkis_UpdateCoords("artificials");
openkis_UpdateCoords("springs");

$results = $dbview->GetResults($config, $params);
foreach ($results as $id => $result)
{
    $results[$id]['link'] = FN_RewriteLink("index.php?mod=$mod&op=view&id={$result['id']}", "&", true);
}

if (!empty($_GET['debug']))
{
    die(GetCSV($results));
}
FN_SaveFile((GetCSV($results)), "wish.csv", "application/vnd.ms-excel");


function GetCSV($data)
{
    $sep = ",";
    $str = "";
    foreach ($data as $k => $row)
    {
        $arraycols = array();
        foreach ($row as $cell => $value)
        {
            $arraycols[] = "\"" . str_replace("\"", "\"\"", $cell) . "\"";
        }
        $str .= implode($sep, $arraycols) . "\n";
        break;
    }
    foreach ($data as $row)
    {
        $arraycols = array();
        foreach ($row as $cell)
        {
            $arraycols[] = "\"" . str_replace("\"", "\"\"", $cell) . "\"";
        }
        $str .= implode($sep, $arraycols) . "\n";
    }
    return $str;
}

?>
