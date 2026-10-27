<?php
// the "groups" field of the surveys (multicave) links here as op=view&id=CODE
if (FN_GetParam("op", $_GET, "flat") == "view" && FN_GetParam("id", $_GET, "flat") != "")
{
    $_GET["op___xdb_ctl_associations"] = "view";
    $_GET["pk___xdb_ctl_associations"] = FN_GetParam("id", $_GET, "flat");
}
// everyone can read the list, only the section editors (group_edit) can change it
$canedit = FN_UserCanEditSection();
FN_XMETATableEditor("ctl_associations", array(
    "enableview" => true,
    "enableedit" => $canedit,
    "enablenew" => $canedit,
    "enabledelete" => $canedit,
    "fields" => "name|logo|provincia|active|journal",
    "imagesize" => 48,
    "recordsperpage" => 0,
    "defaultorder" => "provincia,name"
));
