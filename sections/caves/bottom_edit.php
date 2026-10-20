<?php
$id = FN_GetParam("id", $_GET, "html");
if ($id === "")
{
    // After a save (op=updaterecord) the record key travels in the POST
    $id = FN_GetParam("id", $_POST, "html");
}

// Record validator: shows an informative banner above the edit form with the
// missing or non-uniform data. It never blocks saving. Default rules are in
// extra/openkis_validator.inc.php, regions can override them with
// extra/openkis/custom/<region>/validator_<mod>.php
if (file_exists("extra/openkis_validator.inc.php"))
{
    require_once "extra/openkis_validator.inc.php";
    // Table from the section config, falling back to the ctl_<mod> convention
    $validator_tables = isset($config['tables']) ? explode(",", $config['tables']) : array();
    $validator_tablename = trim(isset($validator_tables[0]) ? $validator_tables[0] : "");
    if ($validator_tablename === "" && !empty($_FN['mod']))
    {
        $validator_tablename = "ctl_{$_FN['mod']}";
    }
    if ($validator_tablename !== "" && $id !== "")
    {
        $validator_table = FN_XMDBTable($validator_tablename);
        $validator_row = $validator_table->GetRecordByPk($id);
        if (is_array($validator_row))
        {
            $html = openkis_ValidatorHtml(openkis_ValidateRecord($_FN['mod'], $validator_row)) . $html;
        }
    }
}

$html .= "<hr /><a class=\"btn btn-primary\" href=\"{$_FN['siteurl']}openkis_photos.php?id={$id}&t=ctl_{$_FN['mod']}\">Inserimento rapido foto</a><hr />";

// Region hook: a region can append its own additions to the edit form with
// extra/openkis/custom/<region>/bottom_edit_<mod>.php
if (!empty($_FN['openkis_custom']))
{
    $region_bottom_edit = "extra/openkis/custom/{$_FN['openkis_custom']}/bottom_edit_{$_FN['mod']}.php";
    if (file_exists($region_bottom_edit))
    {
        include $region_bottom_edit;
    }
}
?>
