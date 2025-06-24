<?php

//---------uppercase--------------------------------------->
class xmetadbfrm_field_provincia
{

    function show($params)
    {
        $comuni=FN_ReadCsvDatabase(__DIR__."/comuni.csv",",");
        foreach($comuni as $comune)
        {
            $code=strtoupper($comune['regione']);
            

            $province[$comune['provincia']]=array("regione"=>strtoupper($comune['regione']),"name"=>mb_strtoupper($comune['provincia'],"UTF-8"));
        }
        //dprint_r();
        $province=FN_ArraySortByKey($province,"name");

        static $htmljs=true;
        if ($htmljs)
        {
            $htmljs="<script type=\"text/javascript\">
                var province=".json_encode($province).";		
		</script>";
        }
        //-----------------------------------------
        $html="$htmljs";
        $htmljs="";
        $html.="<select onchange=\"updateComuni();\" id=\"select_provincia\" name=\"{$params['name']}\">";
        foreach($province as $provincia)
        {
            $s=($provincia['name']== $params['value']) ? "selected=\"selected\"" : "";
            if (!empty($params['oldvalues']['regione']) && $provincia['regione'] == $params['oldvalues']['regione'] || $s!="")
                $html.="<option $s value=\"{$provincia['name']}\">{$provincia['name']} </option>";
        }
        $html.="</select>";

        //-----------------------------------------
        return $html;
    }

}

