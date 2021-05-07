<?php

global $_FN;

$tablename = "ctl_caves";
$tableSurveys = "ctl_photos";
$tablePhotos = "ctl_photos";
$section = str_replace("ctl_", "", $tablename);
$suffix = "";
$config = FN_LoadConfig("modules/dbview/config.php", $section);




if ($section !== "caves")
{
    $suffix = "_$section";
}
FN_LoadMessagesFolder("modules/dbview");
$table = new XMLTable("fndatabase", "$tablename", $_FN['datadir']);
$tableSurveys = new XMLTable("fndatabase", "ctl_surveys$suffix", $_FN['datadir']);
$tablePhotos = new XMLTable("fndatabase", "ctl_photos$suffix", $_FN['datadir']);
$all_items = $table->GetRecords();
$all_surveys = $tableSurveys->GetRecords();
$all_foto = $tablePhotos->GetRecords();
$all_items = xmldb_array_natsort_by_key($all_items, "code", $desc = false);

foreach ($all_surveys as $survey)
{
    if (!isset($report_items['surveys'][$survey['codecave']]))
    {
        $report_items['surveys'][$survey['codecave']] = 0;
    }
    $report_items['surveys'][$survey['codecave']]++;
}
foreach ($all_foto as $survey)
{
    if (!isset($report_items['photo'][$survey['codecave']]))
    {
        $report_items['photo'][$survey['codecave']] = 0;
    }
    $report_items['photo'][$survey['codecave']]++;
}
$today = FN_GetDateTime(time());
$vars = array("table" => array());
$vars['title'] = FN_Translate("report") . " " . $today;

$tablevars = array();
$tablevars['headers'] = array();
$tablevars['headers'][] = array("text" => FN_Translate("code"));
$tablevars['headers'][] = array("text" => FN_Translate("name"));
$tablevars['headers'][] = array("text" => FN_Translate("surveys"));
$tablevars['headers'][] = array("text" => FN_Translate("photo entrance"));
$tablevars['headers'][] = array("text" => FN_Translate("photos"));
$tablevars['headers'][] = array("text" => FN_Translate("description"));
$tablevars['headers'][] = array("text" => FN_Translate("itinerary"));
$tablevars['headers'][] = array("text" => FN_Translate("position"));
$tablevars['headers'][] = array("text" => FN_Translate("updated by"));
$tablevars['headers'][] = array("text" => FN_Translate("date updated"));
$tablevars['headers'][] = array("text" => FN_Translate("visible to"));
$tablevars['headers'][] = array("text" => FN_Translate("Documents"));


$tablevars['rows'] = array();
$count_rows = 0;
foreach ($all_items as $item)
{

    $surveys = "";
    if (!isset($report_items['surveys'][$item['code']]))
    {
        $report_items['surveys'][$item['code']] = "<span style=\"color:red\">0</span>";
    }
    else
    {
        $report_items['surveys'][$item['code']] = "<span style=\"color:green\">" . $report_items['surveys'][$item['code']] . "</span>";
    }
    if (empty($item['photo1']))
    {
        $report_items['entrance'][$item['code']] = "<span style=\"color:red\">" . FN_Translate("no") . "</span>";
    }
    else
    {
        $report_items['entrance'][$item['code']] = "<span style=\"color:green\">" . FN_Translate("yes") . "</span>";
    }

    if (!isset($report_items['photo'][$item['code']]))
    {
        $report_items['photo'][$item['code']] = "<span style=\"color:red\">0</span>";
    }
    else
    {
        $report_items['photo'][$item['code']] = "<span style=\"color:green\">{$report_items['photo'][$item['code']]}</span>";
    }

    if ($item['description'] != "")
    {
        $report_items['description'] = "<span style=\"color:green\">" . FN_Translate("yes") . "</span>";
    }
    else
    {
        $report_items['description'] = "<span style=\"color:red\">" . FN_Translate("no") . "</span>";
    }
    if ($item['itinerary'] != "")
    {
        $report_items['itinerary'] = "<span style=\"color:green\">" . FN_Translate("yes") . "</span>";
    }
    else
    {
        $report_items['itinerary'] = "<span style=\"color:red\">" . FN_Translate("no") . "</span>";
    }

    if ($item['latitude'] != "" && $item['longitude'] != "")
    {
        $report_items['position'] = "<span style=\"color:green\">" . FN_Translate("yes") . "</span>";
    }
    else
    {
        $report_items['position'] = "<span style=\"color:red\">" . FN_Translate("no") . "</span>";
    }
    
    
    $id=$item['code'];
    $id_number=intval(preg_replace("/[^0-9]/", "", $item['code']));    
    $idfolder = sprintf("%04d", intval($id_number));
    $list = glob("{$config['documents_folder']}/{$id}");
    if ($list || count($list) < 1)
        $list = glob("{$config['documents_folder']}/{$idfolder}");
    if ($list || count($list) < 1)
        $list = glob("{$config['documents_folder']}/{$idfolder}_*");        
    if (count($list) > 0)
    {
        $list = glob("{$list[0]}/*.*");    
    }
    if (count($list) > 0)
    {
        
        $report_items['documents'] = "<span style=\"color:green\">" . count($list) . "</span>";
    }
    else
    {
        $report_items['documents'] = "<span style=\"color:red\">0</span>";
    }

    $uservalues = FN_GetUser($item['userupdate']);
    if (isset($uservalues['name']) && $uservalues['name'] != "")
    {
        $report_items['name'] = $uservalues['name'] . " " . $uservalues['surname'];
    }
    else
    {
        $report_items['name'] = $item['userupdate'];
        if ($report_items['name'] == "admin")
            $report_items['name'] = "";
    }
    $report_items['visible'] = "";
    if ($item['groupview'] == "")
    {
        $report_items['visible'] = FN_Translate("all");
    }
    else
    {
        $report_items['visible'] = $item['groupview'];
    }

    $tmp_row = array();
    $tmp_row['cols'] = array();
    $tmp_col = array();
    $tmp_col[] = array("text" => "<a target=\"_blank\" href=\"" . FN_RewriteLink("index.php?mod={$section}&amp;op=view&amp;id={$item['id']}") . "\">{$item['code']}</a>");
    $tmp_col[] = array("text" => "{$item['name']}");
    $tmp_col[] = array("text" => $report_items['surveys'][$item['code']]);
    $tmp_col[] = array("text" => $report_items['entrance'][$item['code']]);
    $tmp_col[] = array("text" => $report_items['photo'][$item['code']]);
    $tmp_col[] = array("text" => $report_items['description']);
    $tmp_col[] = array("text" => $report_items['itinerary']);
    $tmp_col[] = array("text" => "{$report_items['position']}");
    $tmp_col[] = array("text" => "{$report_items['name']}");
    $tmp_col[] = array("text" => "{$item['recordupdate']}");
    $tmp_col[] = array("text" => "{$report_items['visible']}");
    $tmp_col[] = array("text" => "{$report_items['documents']}");


    $tmp_row['cols'] = $tmp_col;
    $tablevars['rows'][] = $tmp_row;
    $count_rows++;
    if ($count_rows > 10)
    {
      //  break;
    }
}
$vars['table'] = $tablevars;
echo FN_TPL_ApplyTplFile("sections/reports/table.tp.html", $vars);
?>