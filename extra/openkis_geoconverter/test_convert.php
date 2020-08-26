<?php
require_once ("../catasto.inc.php");
//require_once ("splx_GeoConverter.php");
//require_once ("splx_GeoConverter2.php");
$row['latitude']="44°7'19'' N";
$row['longitude']= "4°19'33'' Ovest di Monte Mario";
$row['TC_01'] = "Igm 1:25000 Geografiche M.Mario (OVEST)";
//Proj4php::$defs["GEOWGS84"]
//Proj4php::$defs["GEOROME1940MONTEMARIOW"]
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
  <script src="../../proj4js/lib/proj4js.js"></script>
</head>
	<body>

<?php
//die (Proj4php::$defs["GEOROME1940MONTEMARIOW"]);
$c = coordinate($row);
dprint_r($c);
function FN_ergi($find, $str, $regs = null)
{
    return (preg_match("/" . str_replace('/', '\\/', $find) . "/si", $str, $regs));
}
function dprint_r($var,$str = "")
{
	global $_FN;
	if (empty($_FN['consolemode']))
		echo "<pre style=\"font-size:10px;line-height:12px;border:1px solid green\">";
	echo "$str\n";
	print_r($var);
	if (empty($_FN['consolemode']))
		echo "</pre>";
}
?>
		<div id="res"></div>
		<script>
		pointSource = new Proj4js.Point();
		Proj4js.defs["GEOROME1940MONTEMARIOW"] = "<?php echo Proj4php::$defs["GEOROME1940MONTEMARIOW"];?>";
		Proj4js.defs["GEOWGS84"] = "<?php echo Proj4php::$defs["GEOWGS84"];?>";
		pointSource.x = -4.32583333;
		pointSource.y = 44.12194444;
		
		ct1 = new  Proj4js.Proj("GEOROME1940MONTEMARIOW");
		ct2 = new  Proj4js.Proj("GEOWGS84");
		dest = Proj4js.transform(ct1,ct2,pointSource);
		html = "x="+dest.x;
		html += "<br />y="+dest.y;
		html += "<br />z="+dest.z;
		document.getElementById("res").innerHTML=html;
		console.log (dest);
		</script>
	</body>