<?php
/**
 * @package Flatnux_module_filemanager
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
ob_start ();
while ( !file_exists("include/flatnux.php") )
{
	chdir("..");
}
global $_FN;
include_once "./include/flatnux.php";

$file = FN_GetParam("file", $_GET);
if ( !file_exists($file) )
	die($file);
$file = realpath($file);

if ( FN_CanModifyFile($_FN ['user'], $file) )
{
	dl_file($file);
}
else
{
	die ();
}
/**
 *
 * @param string $file
 */
function dl_file($file)
{
	//First, see if the file exists
	if ( !is_file($file) )
	{
		die("<b>$file 404 File not found!</b>");
	}
	//Gather relevent info about file
	$len = filesize($file);
	$filename = basename($file);
	$file_extension = strtolower(substr(strrchr($filename, "."), 1));

	//This will set the Content-Type to the appropriate setting for the file
	switch ( $file_extension )
	{
		case "pdf" :
			$ctype = "application/pdf";
			break;
		case "exe" :
			$ctype = "application/octet-stream";
			break;
		case "zip" :
			$ctype = "application/zip";
			break;
		case "doc" :
			$ctype = "application/msword";
			break;
		case "xls" :
			$ctype = "application/vnd.ms-excel";
			break;
		case "ppt" :
			$ctype = "application/vnd.ms-powerpoint";
			break;
		case "gif" :
			$ctype = "image/gif";
			break;
		case "png" :
			$ctype = "image/png";
			break;
		case "jpeg" :
		case "jpg" :
			$ctype = "image/jpg";
			break;
		case "mp3" :
			$ctype = "audio/mpeg";
			break;
		case "wav" :
			$ctype = "audio/x-wav";
			break;
		case "mpeg" :
		case "mpg" :
		case "mpe" :
			$ctype = "video/mpeg";
			break;
		case "mov" :
			$ctype = "video/quicktime";
			break;
		case "avi" :
			$ctype = "video/x-msvideo";
			break;
		default :
			$ctype = "application/force-download";
	}

	//Begin writing headers
	header("Pragma: public");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Cache-Control: public");
	header("Content-Description: File Transfer");
	//Use the switch-generated Content-Type
	header("Content-Type: $ctype");
	//Force the download
	$header = "Content-Disposition: attachment; filename=" . $filename . ";";
	header($header);
	header("Content-Transfer-Encoding: binary");
	header("Content-Length: " . $len);
	@ readfile($file);
	exit ();
}
?>