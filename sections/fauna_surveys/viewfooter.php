<?php



$dbview_fauna=new FNDBVIEW(FN_LoadConfig("modules/dbview/config.php","fauna_surveys"));

$config=FN_LoadConfig("modules/dbview/config.php","caves");
$params=array();
$params['fields']="code,latitude,longitude,elevation,name,synonyms,depth_total,depth_negative,depth_positive,lenght_total";
$params['appendquery']="code LIKE '{$row['codecave']}'";
$dbview=new FNDBVIEW($config);
$item=$dbview->GetResults(false,$params);
//dprint_r($item);

if (isset($item[0]))
{
    $zoom=15;
    $lat=$item[0]['latitude'];
    $lon=$item[0]['longitude'];
    $code=$item[0]['code'];
    echo "<iframe id=\"mapframe\" name=\"mapframe\" 
    frameborder=\"0\" src=\"{$_FN['siteurl']}bs_map.htm?zoom=$zoom&mod=caves&lat=$lat&lon=$lon&filter_code={$code}\" width=\"100%\" height=\"500\" ></iframe>";
}
?>








