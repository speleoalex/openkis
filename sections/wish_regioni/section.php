<?php

global $_FN;
$params['fields'] = "[Logo]photo1|regione|names|email";
$params['enablenew'] = false;
$params['enableedit'] = false;
if (FN_UserInGroup($_FN['user'], "w_regioni") || FN_IsAdmin())
    $params['enableedit'] = true;

$params['enableview'] = true;
$params['enabledelete'] = false;
$params['defaultorder'] = "regione";

if (FN_UserInGroup($_FN['user'], "w_regioni") || FN_IsAdmin())
{
    //  $params['enableedit'] = true;
}
FN_XMETATableEditor("ctl_wishregioni", $params);

