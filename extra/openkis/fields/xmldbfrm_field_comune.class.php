<?php

//---------uppercase--------------------------------------->
class xmldbfrm_field_comune
{

    function show($params)
    {
        $comuni=FN_ReadCsvDatabase(__DIR__."/comuni.csv",",");
        //dprint_r();
        foreach($comuni as $k=> $comune)
        {
            $comuni[$k]['comune']=mb_strtoupper($comune['comune'],"UTF-8");
        }
        $comuni=FN_ArraySortByKey($comuni,"comune");
        static $htmljs=true;
        if ($htmljs)
            $htmljs="<script type=\"text/javascript\">
                    var comuni = ".json_encode($comuni).";
		</script>";
        //-----------------------------------------
        $html="$htmljs";
        $htmljs="";
        $html.="<select id=\"select_comune\" name=\"{$params['name']}\">";
        //$html.="<option selected=\"selected\" value=\"{$params['value']}\">{$params['value']} </option>";
        foreach($comuni as $comune)
        {
            $s=($comune['comune']== $params['value']) ? "selected=\"selected\"" : "";
            if (!empty($params['oldvalues']['provincia']) && $comune['provincia']== $params['oldvalues']['provincia'] || $s!= "")
                $html.="<option $s value=\"{$comune['comune']}\">{$comune['comune']} </option>";
        }
        $html.="</select>";

        //-----------------------------------------
        return $html;
    }

}

?>
