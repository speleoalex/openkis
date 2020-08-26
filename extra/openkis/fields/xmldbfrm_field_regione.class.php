<?php

//---------uppercase--------------------------------------->
class xmldbfrm_field_regione
{

    function show($params)
    {
        $comuni=FN_ReadCsvDatabase(__DIR__."/comuni.csv",",");
        foreach($comuni as $comune)
        {
            $code=strtoupper($comune['regione']);
            if ($code== "VALLE D'AOSTA")
            {
                $code="AO";
            }
            else
            {
                $code=$code[0].$code[1];
            }

            $regioni[$comune['regione']]=array("name"=>strtoupper($comune['regione']),"code"=>$code);
        }
        $regioni=FN_ArraySortByKey($regioni,"name");
        // dprint_r($regioni);
        $toltips=($params['frm_help']!= "") ? "title=\"".$params['frm_help']."\"" : "";
        $size=isset($params['frm_size']) ? $params['frm_size'] : 30;
        static $htmljs=true;
        if ($htmljs)
        {
            $htmljs="<script>".file_get_contents(__DIR__."/update_comuniprovince.js")."</script>";
            $htmljs.="\n<script type=\"text/javascript\">
                var regioni=".json_encode($regioni)."	
		</script>";
        }
        //-----------------------------------------
        $html="$htmljs";
        $htmljs="";
        $html.="<select id=\"select_regione\" onchange=\"updateProvince();\" name=\"{$params['name']}\">";
        foreach($regioni as $code=> $regione)
        {
            $s=($regione['name']== $params['value']) ? "selected=\"selected\"" : "";
            $html.="<option $s value=\"{$regione['name']}\">{$regione['name']}</option>";
        }
        $html.="</select>";

        //-----------------------------------------
        return $html;
    }

}

?>
