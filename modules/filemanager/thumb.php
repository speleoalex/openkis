<?php
/**
 * @package Flatnux_module_filemanager
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
global $_FN;
chdir("../../");
include "./include/flatnux.php";
//header("Cache-Control: no-cache");
header("Cache-Control:private");
if (  !function_exists("imagecreatetruecolor") )
{
	header("Content-type: image/jpeg");
	die(file_get_contents("images/mime/image.png"));
}
//header("Pragma: no-cache"); 
$filename = FN_GetParam("f",$_GET);
$maxw = FN_GetParam("w",$_GET);
$maxh = FN_GetParam("h",$_GET);
$table = FN_GetParam("t",$_GET);
$id = FN_GetParam("i",$_GET);
$d = FN_GetParam("d",$_GET);
$field = FN_GetParam("c",$_GET);
if ( $d == "" )
	$d = "fndatabase";
if ( $table != "" && $id != "" && $field != "" )
{
	$tb = new XMLTable("$d",$table,$_FN['datadir']);
	$filename = $tb->get_file(array("unirecid"=>$id),"$field");
	$filename = str_replace($_FN['siteurl'],"",$filename);
	//$filename=$_FN['datadir']."/fndatabase/$table/$id/$field/";
}
if ( $maxh == "" && $maxw == "" )
{
	$maxh = 60;
	$maxw = 60;
}
if ( $maxh == "" && $maxw != "" )
	$maxh = $maxw;
if ( $maxw == "" && $maxh != "" )
	$maxw = $maxh;
$filename = str_replace("http//","http://",$filename);
if (  !fn_erg("http://",$filename) &&  !file_exists($filename) )
	die($filename . " not exists $field");
list($width,$height,$type,$attr) = getimagesize($filename);
$path = dirname($filename) . "/thumbs";
$new_height = $height;
if ( $maxw != "" && $width >= $maxw )
{
	$new_width = $maxw;
	$new_height = $height * ($new_width / $width);
}
//se troppo alta
if ( $maxh != "" && $new_height >= $maxh )
{
	$new_height = $maxh;
	$new_width = $width * ($new_height / $height);
}
// se l' immagine e gia piccola
if ( $maxw != "" && $maxh != "" && $width <= $maxw && $height <= $maxh )
{
	$new_width = $width;
	$new_height = $height;
	header("Content-type: image/jpeg");
	die(file_get_contents("$filename"));
}
// Load
$thumb = imagecreatetruecolor($new_width,$new_height);
$white = imagecolorallocate($thumb,255,255,255);
$size = getimagesize($filename);
//die ($size);
switch ($size[2])
{
	case 1 :
		$source = ImageCreateFromGif($filename);
	break;
	case 2 :
		$source = ImageCreateFromJpeg($filename);
	break;
	case 3 :
		$source = ImageCreateFromPng($filename);
	break;
	default :
		$tmb = null;
		$size[0] = $size[1] = 150;
		$source = ImageCreateTrueColor(150,150);
		$rosso = ImageColorAllocate($tmb,255,0,0);
		ImageString($tmb,5,10,10,"Not a valid",$rosso);
		ImageString($tmb,5,10,30,"GIF, JPEG or PNG",$rosso);
		ImageString($tmb,5,10,50,"image.",$rosso);
}
// Resize
imagefilledrectangle($thumb,0,0,$width,$width,$white);
imagecopyresampled($thumb,$source,0,0,0,0,$new_width,$new_height,$width,$height);
// Output
header("Content-type: image/jpeg");
imagejpeg($thumb);
imagedestroy($thumb);
?>