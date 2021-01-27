<?php

$zoom=8;
$baselayer="";
$lat=45;
$lon=9;

$config=FN_LoadConfig("modules/dbview/config.php","caves");
$params['fields']="code,latitude,longitude,elevation,name,synonyms,depth_total,depth_negative,depth_positive,lenght_total";
$dbview=new FNDBVIEW($config);
$items_tmp=$dbview->GetResults($config,$params);
$codecaves=explode(",",$row['caves']);

foreach($items_tmp as $item)
{
    if (in_array($item['code'],$codecaves))
    {
        $items[]=$item;
    }
}

$num=count($items);
$html="";
if ($num > 0)
{
    $lat=0;
    $lon=0;
    $lat_min=100;
    $lon_min=100;
    $lat_max=0;
    $lon_max=0;
    $i=0;
    $num=array();
    foreach($items as $item)
    {
        $num[]=$item['code'];
        if (!empty($item['latitude']))
        {
            $_lat=floatval($item['latitude']);
            $_lon=floatval($item['longitude']);
            $lat_max=max($lat_max,$_lat);
            $lat_min=min($lat_min,$_lat);
            $lon_max=max($lon_max,$_lon);
            $lon_min=min($lon_min,$_lon);
            $latlon=" <span style=\"color:grey\">wgs84: ".$_lat."N ".$_lon."E</span>";
            $i++;
        }
        else
        {
            $latlon="";
        }
        // dprint_r($item);

        $html.="<br /><b>{$item['code']}</b> <a target = \"_blank\" href=\"".FN_Rewritelink("index.php?mod=caves&op=view&id={$item['id']}")."\">{$item['name']}</a> $latlon Q.{$item['elevation']}";
    }
    if ($i > 0)
    {
        $lon=(($lon_max - $lon_min) / 2) + $lon_min;
        $lat=(($lat_max - $lat_min) / 2) + $lat_min;

        $lat=str_replace(",",".",$lat);
        $lon=str_replace(",",".",$lon);
        $zoom=13;
        echo "<iframe id=\"mapframe\" name=\"mapframe\" 
    frameborder=\"0\" src=\"{$_FN['siteurl']}bs_map.htm?zoom=$zoom&mod=caves&lat=$lat&lon=$lon&filter_code={$row['caves']}\" width=\"100%\" height=\"500\" ></iframe>";
    }
}
echo $html;


$lat=0;
$lon=0;
$i=0;
$html="";
$sviluppo=0;
$dislivello=0;

$max_quota=false;
$min_quota=false;
$max_quota_gr=false;
$min_quota_gr=false;

$latmin=false;
$latmax=false;
$lonmin=false;
$lonmax=false;



foreach($items as $item)
{
    if (!isset($item['code']))
    {
        continue;
    }
    $sviluppo+=intval($item['lenght_total']);
    if (isset($item['latitude']))
    {
        $_lat=$item['latitude'];
        $_lon=$item['longitude'];
        $geo=new openkis_geoconverter($_lat,$_lon,"GEOWGS84");
        $latlonUTM=$geo->GetCoordinates("UTMWGS8432");
        $latlon=" <span style=\"color:grey\">wgs84: ".$_lat."N ".$_lon."E</span>";
        $lat+=$_lat;
        $lon+=$_lon;
        $i++;
        if (!$latmin || $latlonUTM['y'] < $latmin)
            $latmin=$latlonUTM['y'];
        if (!$latmax || $latlonUTM['y'] > $latmax)
            $latmax=$latlonUTM['y'];
        if (!$lonmin || $latlonUTM['x'] < $lonmin)
            $lonmin=$latlonUTM['x'];
        if (!$lonmax || $latlonUTM['x'] > $lonmax)
            $lonmax=$latlonUTM['x'];

        $deltalat=$latmax - $latmin;
        $deltalon=$lonmax - $lonmin;

        $deltalatkm=round($deltalat / 1000,2);
        $deltalonkm=round($deltalon / 1000,2);
    }
    else
    {
        $latlon="";
    }

    $qu=$item['elevation'];
    $item['elevation']=$qu;
    if ($item['elevation']!== "")
    {
        if (!$max_quota || $item['elevation'] > $max_quota)
        {
            $max_quota=round($item['elevation']);
            $cave_max_quota=$item;
        }
        if ($item['elevation']!== "")
        {
            if ($min_quota=== false || $item['elevation'] < $min_quota)
            {
                $min_quota=round($item['elevation']);
                $cave_min_quota=$item;
            }
        }
    }

    if (isset($item['elevation']) && $item['elevation']!== "")
    {
        $quota=$item['elevation'];
        $punto_piu_basso=$quota - abs(intval($item['depth_negative']));
        $punto_piu_alto=$quota + abs(intval($item['depth_positive']));
        if ($max_quota_gr=== false || $punto_piu_alto > $max_quota_gr)
        {
            $max_quota_gr=$punto_piu_alto;
        }
        if ($min_quota_gr=== false || $punto_piu_basso < $min_quota_gr)
        {
            $min_quota_gr=$punto_piu_basso;
        }
    }
    if (isset($item['code']))
    {
        $html.="<br /><b>{$item['code']}</b> <a target = \"_blank\" href=\"".FN_Rewritelink("index.php?mod=Navigator&op=view&id={$item['code']}")."\">{$item['name']}</a>  <em>SV.R.{$item['lenght_total']}  P.{$item['depth_total']}</em> $latlon Q.$quota";
    }
}
if ($row['lenght_total'])
{
    $lenght_total = $row['lenght_total'];
}
//----------quote e dislivelli ----->

$htmlquote="";
$minquotatext="da quota $min_quota_gr m s.l.m. ";

$dislivelloTot=$max_quota_gr - $min_quota_gr;

if ($min_quota_gr== 0)
{
    $minquotatext="dal livello del mare";
}
if ($min_quota_gr < 0)
{
    $minquotatext="da ".abs($min_quota_gr)."m sotto il livello del mare";
}

$htmlquote.="\n<p>Gli ingressi sono disposti in un area di $deltalatkm km x $deltalonkm km.</p>\n<p>Le grotte si sviluppano $minquotatext fino alla quota di $max_quota_gr m s.l.m.  ";

$minquotatext="a quota {$cave_min_quota['elevation']} m s.l.m. ";
if ($min_quota_gr== 0)
{
    $minquotatext="al livello del mare";
}
if ($min_quota_gr < 0)
{
    $minquotatext=abs($cave_min_quota['elevation'])."m sotto il livello del mare";
}


if ($cave_max_quota['elevation']== $max_quota_gr)
{
    $htmlquote.=" in corrispondenza dell'ingresso ".get_articolo_de($cave_max_quota['name']).ucwords(splx_str_to_lower($cave_max_quota['name'])).".";
    $htmlquote.="\n<br />\nL'ingresso più basso corrispondente ".get_articolo_a($cave_min_quota['name']).ucwords(splx_str_to_lower($cave_min_quota['name']));

    $htmlquote.=" si trova $minquotatext.";
}
else
{
    $htmlquote.="\nL'ingresso più alto ({$cave_max_quota['name']}) si apre a quota {$cave_max_quota['elevation']} m s.l.m.";
    $htmlquote.="il più basso ({$cave_min_quota['name']}) $minquotatext.";
}

$dislivelloTotDaIngressoPiuAlto=$cave_max_quota['elevation'] - $min_quota_gr;

//----------quote e dislivelli -----<
//----------aggiorno automaticamente lo sviluppo reale del complesso con i dati presenti a catasto --->

$sviluppo=round($sviluppo);
$dislivelloTot=round($dislivelloTot);

if ($row['lenght_total'] == 0 )
{
    $id=FN_GetParam("id",$_GET,"html");
    $TableSystem=FN_XmlTable($tablename);
    $snewvalues=$TableSystem->GetRecordByPrimaryKey($id);
    $snewvalues['lenght_total']=$sviluppo;
    $snewvalues['depth_total']=$dislivelloTot;
    $TableSystem->UpdateRecord($snewvalues);
}
//----------aggiorno automaticamente lo sviluppo reale del complesso con i dati presenti a catasto ---<
echo "<div>Sviluppo reale complesso: <b>$sviluppo m.</b> (calcolato sommando lo sviluppo reale delle cavità)</div>";


echo "<div>Dislivello totale complesso: <b>$dislivelloTot m.</b></div>";
echo "<div>Profondità calcolata dall'ingresso più alto al punto più profondo: <b>$dislivelloTotDaIngressoPiuAlto m.</b></div>";

echo $htmlquote;

/**
 * 
 * @param type $nome
 * @return string
 */
function get_articolo($nome)
{
    //dprint_r($nome);
    $art="la ";
    if ($nome[1]== " ")
        return "";
    if ($nome)
    {
        $nome=explode(" ",splx_str_to_lower($nome));
        $nome=$nome[0];
        if ($nome[strlen($nome) - 1]== "o")
        {
            $art="il ";
        }
    }
    if ($nome[0]== "a" || $nome[0]== "e" || $nome[0]== "i" || $nome[0]== "o" || $nome[0]== "u")
        return "l'";
    return $art;
}

/**
 * 
 * @param type $nome
 * @return string
 */
function get_articolo_de($nome)
{
    //dprint_r($nome);
    $art="della ";
    if ($nome[1]== " ")
        return "";
    if ($nome)
    {
        $nome=explode(" ",splx_str_to_lower($nome));
        $nome=$nome[0];
        if ($nome[strlen($nome) - 1]== "o")
        {
            $art="del ";
        }
    }
    if ($nome[0]== "a" || $nome[0]== "e" || $nome[0]== "i" || $nome[0]== "o" || $nome[0]== "u")
        return "dell'";
    return $art;
}

function get_articolo_a($nome)
{
    //dprint_r($nome);
    $art="alla ";
    if ($nome[1]== " ")
        return "";
    if ($nome)
    {
        $nome=explode(" ",splx_str_to_lower($nome));
        $nome=$nome[0];
        if ($nome[strlen($nome) - 1]== "o")
        {
            $art="al ";
        }
    }
    if ($nome[0]== "a" || $nome[0]== "e" || $nome[0]== "i" || $nome[0]== "o" || $nome[0]== "u")
        return "all'";
    return $art;
}

/**
 * 
 * @param type $str
 * @return type
 */
function splx_str_to_lower($str)
{
    $str=strtolower($str);
    $str=str_replace("À","à",$str);
    $str=str_replace("È","è",$str);
    $str=str_replace("Ì","ì",$str);
    $str=str_replace("Ò","ò",$str);
    $str=str_replace("Ù","ù",$str);
    return $str;
}
?>