<?php

global $_FN;
include ("include/flatnux.php");
$lat=FN_GetParam("lat",$_GET);
$lon=FN_GetParam("lon",$_GET);
$address=FN_GetParam("address",$_GET);
{
    if ($lat== "")
        $lat=44.510310;
    if ($lon== "")
        $lon=8.869067;

    $ret['lat']=$lat;
    $ret['lon']=$lon;
    $ret['regione']="";
    $ret['provincia']="";
    $ret['via']="";
    $ret['comune']="";
    $ret['localita']="";
    $ret['title']="informazioni non disponibili";
    $ret['cap']="";
    
    
/*
[place_id] => 120382900
    [licence] => Data © OpenStreetMap contributors, ODbL 1.0. https://osm.org/copyright
    [osm_type] => way
    [osm_id] => 184465893
    [lat] => 44.5105376041155
    [lon] => 8.86899034614592
    [display_name] => Via Bartolomeo Parodi, Piandosse, Piane, Ceranesi, Genoa, Liguria, 16014, Italy
    [address] => Array
        (
            [road] => Via Bartolomeo Parodi
            [neighbourhood] => Piandosse
            [suburb] => Piane
            [village] => Ceranesi
            [county] => Genoa
            [state] => Liguria
            [postcode] => 16014
            [country] => Italy
            [country_code] => it
        )

    [boundingbox] => Array
        (
            [0] => 44.5090832
            [1] => 44.5134382
            [2] => 8.8665925
            [3] => 8.8833171
        )
 *  */

    $r=file_get_contents_browser("https://nominatim.openstreetmap.org/reverse?format=json&lat=$lat&lon=$lon&addressdetails=1");
    $r=json_decode($r,true);
    //dprint_r($r);
    $ret['grotte_in_regione']=0;
    $ret['grotte_in_provincia']=0;
    $ret['grotte_in_comune']=0;
    $ret['grotte_in_localita']=0;
    if (!empty($r['address']['state']))
    {
        $ret['regione']=fix_nomi($r['address']['state']);
    }
    if (!empty($r['address']['city']))
    {
        $ret['provincia']=fix_nomi($r['address']['city']);
    }
    if (!empty($r['address']['county']))
    {
        $ret['provincia']=fix_nomi($r['address']['county']);
    }
    if (!empty($r['address']['neighbourhood']))
    {
        $ret['localita']=$r['address']['neighbourhood'];
    }
    if (!empty($r['display_name']))
    {
        $ret['title']=$r['display_name'];
    }
    if (!empty($r['road']))
    {
        $ret['via']=$r['road'];
    }
    
      if (!empty($r['address']['postcode']))
    {
        $ret['cap']=$r['address']['postcode'];
    }  
    
    
    if (!empty($r['address']['village']))
    {
        $ret['comune']=$r['address']['village'];
    }
    

    if (!empty($ret['regione']))
    {
        $num_grotte=FN_XMDBQuery("SELECT count(*) AS c FROM ctl_DBCAVE");
        $ret['grotte_in_regione']=isset($num_grotte[0]['c']) ? $num_grotte[0]['c'] : "";
    }
    if (!empty($ret['provincia']))
    {
        $num_grotte=FN_XMDBQuery("SELECT count(*) AS c FROM ctl_DBCAVE WHERE PROV LIKE '{$ret['provincia']}'");
        $ret['grotte_in_provincia']=isset($num_grotte[0]['c']) ? $num_grotte[0]['c'] : "";
    }
    if (!empty($ret['comune']))
    {
        $num_grotte=FN_XMDBQuery("SELECT count(*) AS c FROM ctl_DBCAVE WHERE COMUNE LIKE '".strtoupper($ret['comune'])."' ");
        $ret['grotte_in_comune']=isset($num_grotte[0]['c']) ? $num_grotte[0]['c'] : "";
    }
    if (!empty($ret['localita']))
    {
        $num_grotte=FN_XMDBQuery("SELECT count(*) AS c FROM ctl_DBCAVE WHERE LOCAL LIKE '".strtoupper($ret['localita'])."' ");
        $ret['grotte_in_localita']=isset($num_grotte[0]['c']) ? $num_grotte[0]['c'] : "";
    }


//if (isset())
}
function fix_nomi($prov)
{
    $arrayprov=array();
    $arrayprov['Genoa']="GE";
    $arrayprov['LIG']="LI";
    if (!empty($arrayprov[$prov]))
    {
        return $arrayprov[$prov];
    }
    return $prov;
}
function file_get_contents_browser($url)
{
    $ch=curl_init();
    //'Accept-Language: it-it,it;q=0.5',
    //'Accept-Language: en-us,en;q=0.5',
    $header=array(
        'User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.12) Gecko/20101026 Firefox/3.6.12',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language: en-us,en;q=0.5',
        'Accept-Encoding: gzip,deflate',
        'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7',
        'Keep-Alive: 115',
        'Connection: keep-alive',
    );

    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch,CURLOPT_COOKIEFILE,'cookies.txt');
    curl_setopt($ch,CURLOPT_COOKIEJAR,'cookies.txt');
    curl_setopt($ch,CURLOPT_HTTPHEADER,$header);
    $result=curl_exec($ch);

    curl_close($ch);
   // dprint_r($result);
    return $result;
}

echo json_encode($ret);

//dprint_r($components);
//dprint_r($ret);
?>