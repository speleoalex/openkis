<?php

/*
 * Created on 21-giu-2006
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
global $_FN;
//--config-->
/*
$config=FN_LoadConfig();
$search_fields=$config['search_fields']!= "" ? explode(",",$config['search_fields']) : array();
$search_partfields=$config['search_fields']!= "" ? explode(",",$config['search_partfields']) : array();
$search_orders=$config['search_orders']!= "" ? explode(",",$config['search_orders']) : array();
$navigate_groups=$config['navigate_groups']!= "" ? explode(",",$config['navigate_groups']) : array();
$search_options=$config['search_options']!= "" ? explode(",",$config['search_options']) : array();
$search_min=$config['search_min']!= "" ? explode(",",$config['search_min']) : array();
$databasename="fndatabase";
$pathdatabase=$_FN['datadir'];
$tables=explode(",",$config['tables']);
$tablename=$tables[0];
$id=FN_GetParam("id",$_GET,"html");
//--config--<
//-----BIBLIOGRAFIA-------------------------->
$table=new XMLTable("fndatabase","ctl_bibliography",$_FN['datadir']);

$query="SELECT * FROM ctl_bibliography WHERE fauna LIKE \"%".$row['scientific_name']."%\" OR 	fauna LIKE \"%".strtolower($row['scientific_name'])."%\" OR fauna LIKE \"%".ucfirst(strtolower($row['scientific_name']))."%\" OR fauna LIKE \"%".strtoupper($row['scientific_name'])."%\" ";

//die ($query);
$bibliografia=FN_XMLQuery($query);
if (count($bibliografia) > 0)
{
    echo "<div style=\"border:1px solid #fcac2b;padding:5px;margin-top:10px;background-color:#ffffda\" class=\"bibliografia\">";
    echo "<h3>Bibliografia</h3><ul>";
    foreach($bibliografia as $bib)
    {
        echo "<li>.<a href=\"".FN_RewriteLink("?mod=bibliografia&amp;op=view&amp;id={$bib['ID']}")."\">".FN_FixEncoding($bib['Rivista']).":</a>&nbsp; ";
        echo FN_FixEncoding("{$bib['Titolo']}",$_FN['charset_page']);
        if ($bib['Anno']!= "")
            echo " , anno:{$bib['Anno']}";
        if ($bib['Autori']!= "")
            echo "<br /><em>{$bib['Autori']}</em>";
        echo "</li>";
    }
    echo "</ul></div>";
}
//-----BIBLIOGRAFIA--------------------------<

//-----GROTTE ASSOCIATE----------------------->

if (FN_UserCanViewSection("fauna_rilevamenti"))
{
    $fauna_grotte=get_all_caves(true,false,"AND FAUNA LIKE \"%{$row['scientific_name']}%\"","NUM");
    $query="SELECT NUMCAVE FROM ctl_FAUNACAVE WHERE name LIKE \"%{$row['scientific_name']}%\"";
    $rilevamenti_grotte=FN_XMLQuery($query);
    $numeri=array();
    if (is_array($fauna_grotte))
        foreach($fauna_grotte as $c)
        {
            $numeri[$c['NUM']]=$c['NUM'];
        }
    if (is_array($rilevamenti_grotte))
        foreach($rilevamenti_grotte as $c)
        {
            $numeri[$c['NUMCAVE']]=$c['NUMCAVE'];
        }
//-----GROTTE ASSOCIATE-----------------------<
    $numeri_get=implode(",",$numeri);

    $lat=0;
    $lon=0;
    $i=0;
    $html="";

    foreach($numeri as $numero)
    {
        $item=get_cave_by_num($numero,true);
        $r=coordinate($item);
        if (isset($r['WGS84']['latitude']))
        {
            $_lat=$r['WGS84']['latitude'];
            $_lon=$r['WGS84']['longitude'];
            $latlon=" <span style=\"color:grey\">wgs84: ".$_lat."N ".$_lon."E</span>";
            $lat+=$r['WGS84']['latitude'];
            $lon+=$r['WGS84']['longitude'];
            $i++;
        }
        else
        {
            $latlon="";
        }
        $quota=max(array($item['SC_01'],$item['QA_01'],$item['QC_01']));
        $html.="<br /><b>{$item['NUM']}</b> <a target = \"_blank\" href=\"".FN_Rewritelink("index.php?mod=Navigator&op=view&id={$item['NUM']}")."\">{$item['NOME']}</a>  <em>SV.R.{$item['SVILRE']} SV.PL.{$item['SVILPLAN']} ES.{$item['ESTEN']} P.{$item['DTOT']}</em> $latlon Q.$quota";
    }

    if (count($numeri) > 0)
    {
        echo "<div><h3>Grotte in cui è stato rilevato:</h3>";
        if ($i > 0)
        {
            $lon=$lon / $i;
            $lat=$lat / $i;
            $zoom=8;
            echo "<iframe id=\"mapframe\" name=\"mapframe\" 
    frameborder=\"0\" src=\"bs_map.php?hidecaves=1&num=$numeri_get&lat=$lat&lon=$lon&zoom=$zoom\" width=\"650\" height=\"500\" ></iframe>$html";
        }

        echo "</div>";
    }
}
*/
?>
