<?php

global $_FN;
require_once "include/flatnux.php";
//$section="",$recursive=false,$onlyreadable=true,$hidden=false,$onlyenabled=true,$nocache=false
$sections=FN_GetSections("",true,false,true,false,true);
foreach($sections as $section)
{
    dprint_r($section);
    FN_UpdateDefaultXML($section,false);
}
?>