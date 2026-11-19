<?php

// Refresh the computed development before showing the record, so edits made
// outside the save hooks (imports, table_editor) are picked up too. The time
// stamp inside openkis_UpdateCaveSystems() makes this a no-op on most views.
if (function_exists("openkis_UpdateCaveSystems") && isset($row['id']))
{
    openkis_UpdateCaveSystems();
    $systems_refresh = FN_XMDBTable("ctl_cavesystems");
    $row_refresh = is_object($systems_refresh) ? $systems_refresh->GetRecordByPk($row['id']) : false;
    if (is_array($row_refresh))
    {
        $row = $row_refresh;
    }
}

$config=FN_LoadConfig("modules/dbview/config.php","caves");
$params['fields']="code,latitude,longitude,elevation,name,synonyms,depth_total,depth_negative,depth_positive,lenght_total";
$dbview=new FNDBVIEW($config);
$items_tmp=$dbview->GetResults($config,$params);
if (!is_array($items_tmp))
{
    $items_tmp=array();
}
$codecaves=array_map("trim",explode(",",isset($row['caves']) ? $row['caves'] : ""));
$items=array();
foreach($items_tmp as $item)
{
    if (in_array($item['code'],$codecaves))
    {
        $items[]=$item;
    }
}
if (count($items) > 0)
{
    echo openkis_CavesGroupList($items, "caves", "filter_code={$row['caves']}");
}


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
$deltalatkm=0;
$deltalonkm=0;

$cave_max_quota=null;
$cave_min_quota=null;



foreach($items as $item)
{
    if (!isset($item['code']))
    {
        continue;
    }
    $quota="";
    $sviluppo+=intval($item['lenght_total']);
    if (!empty($item['latitude']) && !empty($item['longitude']))
    {
        $_lat=floatval($item['latitude']);
        $_lon=floatval($item['longitude']);
        $geo=new openkis_geoconverter($_lat,$_lon,"GEOWGS84");
        $latlonUTM=$geo->GetCoordinates("UTMWGS8432");
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

    if (isset($item['elevation']) && $item['elevation']!== "")
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

    // elevation of this entrance, empty when unknown (reset for every item)
    $quota=(isset($item['elevation']) && $item['elevation']!== "") ? $item['elevation'] : "";
    if ($quota!== "")
    {
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
}


//----------quote e dislivelli ----->

$htmlquote="";
$dislivelloTot=0;
$dislivelloTotDaIngressoPiuAlto=0;
if ($cave_max_quota!== null && $cave_min_quota!== null && $min_quota_gr!== false)
{
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
}

//----------quote e dislivelli -----<

// Show the stored record values. The per-entrance sum double counts caves that
// carry the whole-cave development on every entrance, so this view must not
// overwrite the record; the scope-aware recalculation is done by
// openkis_UpdateCaveSystems() (extra/openkis.inc.php).
$sviluppo=round($sviluppo);
$dislivelloTot=round($dislivelloTot);

echo openkis_TplApply("cavesystem_stats", array(
    "lenght_total" => $row['lenght_total'],
    "depth_total" => $row['depth_total'],
    // the computed values only mean something when at least one linked cave was found
    "sviluppo_note" => count($items) > 0 && $sviluppo != intval($row['lenght_total']),
    "sviluppo" => $sviluppo,
    "dislivello_note" => count($items) > 0 && $dislivelloTot != intval($row['depth_total']),
    "dislivello" => $dislivelloTot,
    "has_items" => count($items) > 0,
    "profondita" => $dislivelloTotDaIngressoPiuAlto,
    "htmlquote" => $htmlquote,
));

/**
 * 
 * @param type $nome
 * @return string
 */
function get_articolo($nome)
{
    //dprint_r($nome);
    $art="la ";
    $nome=(string)$nome;
    if ($nome=== "")
        return "";
    if (isset($nome[1]) && $nome[1]== " ")
        return "";
    if ($nome)
    {
        $nome=explode(" ",splx_str_to_lower($nome));
        $nome=$nome[0];
        if ($nome=== "")
            return $art;
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
    $nome=(string)$nome;
    if ($nome=== "")
        return "";
    if (isset($nome[1]) && $nome[1]== " ")
        return "";
    if ($nome)
    {
        $nome=explode(" ",splx_str_to_lower($nome));
        $nome=$nome[0];
        if ($nome=== "")
            return $art;
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
    $nome=(string)$nome;
    if ($nome=== "")
        return "";
    if (isset($nome[1]) && $nome[1]== " ")
        return "";
    if ($nome)
    {
        $nome=explode(" ",splx_str_to_lower($nome));
        $nome=$nome[0];
        if ($nome=== "")
            return $art;
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
    $str=strtolower((string)$str);
    $str=str_replace("À","à",$str);
    $str=str_replace("È","è",$str);
    $str=str_replace("Ì","ì",$str);
    $str=str_replace("Ò","ò",$str);
    $str=str_replace("Ù","ù",$str);
    return $str;
}
?>