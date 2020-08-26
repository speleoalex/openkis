<?php
/**
 * @package Flatnux_controlcenter
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
defined('_FNEXEC') or die('Restricted access');
require_once (dirname(__FILE__)."/classes/DeepDir.php");
require_once ("include/classes/FN_Unzipper.php");
global $_FN;

if (!function_exists('file_put_contents'))
{
	Function file_put_contents($file,$data)
	{
		if (false !== ($tmp = fopen($file,"w")))
		{
			fwrite($tmp,$data);
			fclose($tmp);
			return true;
		}
		echo "<b>file_put_contents:</b> Cannot create file $file<br>";
		return false;
	}

}
ini_set("upload_max_filesize","20M");
ini_set("memory_limit","128M");
ini_set("max_execution_time","600");
ini_set("max_input_time","600");

global $UPDATELOG;
echo "<div id=\"ccupdate\">";
$UPDATELOG = "";
$opp = FN_GetParam("opt",$_GET,"flat");
$opmod = FN_GetParam("opmod",$_GET,"flat");
$action = FN_GetParam("action",$_POST,"flat");
$downloadfrominternet = FN_GetParam("downloadfrominternet",$_GET,"flat");
$testupdate = FN_GetParam("testupdate",$_POST,"flat");
$cleanupdate = FN_GetParam("cleanupdate",$_POST,"flat");
$cleanobsolete = FN_GetParam("cleanobsolete",$_POST,"flat");
$file_clean = FN_GetParam("fileupdate",$_POST,"flat");

if ($testupdate != "")
	$testupdate = 1;

//------------------------------------delete temporany files------------------->
if ($cleanupdate != "")
{
	FN_RemoveDir("{$_FN['datadir']}/updates");
}
//------------------------------------delete temporany files-------------------<
//---------------------create folder------------------------------------------->
if (!file_exists("{$_FN['datadir']}/updates"))
	FN_MkDir("{$_FN['datadir']}/updates");
if (!file_exists("{$_FN['datadir']}/updates/tmp"))
	FN_MkDir("{$_FN['datadir']}/updates/tmp");
//---------------------create folder-------------------------------------------<
//---------------------upload file -------------------------------------------->
$file_downloaded = false;
$versionerror = "";
if ($downloadfrominternet == "1")
{
	if (false !== ($latestversion = trim(ltrim(file_get_contents($_FN['url_update'])))))
	{
		$current = trim(ltrim(file_get_contents("VERSION")));
		if ($current >= $latestversion)
		{
			$versionerror = "<div style=\"color:red\">".FN_Translate("cms seems to be updated to the latest version")."</div>";
			$versionerror .= "<div >".FN_Translate("installed version").": <b>$current</b></div>";
			$versionerror .= "<div >".FN_Translate("latest version").": <b>$latestversion</b></div>";
		}
		else
		{
			$file_to_download = dirname($_FN['url_update'])."/$latestversion".".zip";
			$file_clean = basename($file_to_download);
			if (false !== ($string = file_get_contents($file_to_download)))
			{
				FN_Write($string,"{$_FN['datadir']}/updates/$file_clean");
				echo FN_Translate("the last version was successfully downloaded")." ($latestversion)";
				$file_downloaded = true;
				clearstatcache();
			}
			else
			{
				$versionerror = FN_Translate("connection error").":";
				echo "<div>$file_to_download</div>";
			}
		}
	}
	else
	{
		$versionerror = FN_Translate("connection error").":";
		echo "<div>{$_FN['url_update']}</div>";
	}
}
elseif ($action == "upload")
{
	FN_RemoveDir("{$_FN['datadir']}/updates");
	FN_MkDir("{$_FN['datadir']}/updates");
	FN_MkDir("{$_FN['datadir']}/updates/tmp");
	$file_clean = FN_StripPostSlashes($_FILES['flatnukefile']['name']);
	if (!move_uploaded_file($_FILES['flatnukefile']['tmp_name'],"{$_FN['datadir']}/updates/$file_clean"))
	{
		echo FN_i18n("file not created");
	}
	else
	{
		if (strtoupper(FN_GetFileExtension($file_clean)) == "ZIP")
		{
			FNCC_unzip("{$_FN['datadir']}/updates/$file_clean","{$_FN['datadir']}/updates/tmp/");
			echo "<pre>$file_clean: ".FN_i18n("currently loaded file")."</pre>";
			$file_downloaded = true;
		}
	}
}
//---------------------upload file --------------------------------------------<
{
//---------------------extract file ------------------------------------------->
	$existsupdates = false;
	$tmph = opendir("{$_FN['datadir']}/updates/");
	while(false !== $file_clean2 = readdir($tmph))
	{
		if (strtolower(FN_GetFileExtension($file_clean2)) == "zip")
		{
			$existsupdates = true;
			$listzips[] = $file_clean2;
		}
	}
	closedir($tmph);
	if (/* isset($_GET['extract']) && */ $existsupdates)
	{
		foreach ($listzips as $file_clean2)
		{
			if (FN_GetFileExtension($file_clean2) == "zip")
			{
				FNCC_unzip("{$_FN['datadir']}/updates/$file_clean2","{$_FN['datadir']}/updates/tmp/");
			}
		}
	}
}

//---------------------extract file -------------------------------------------<
//---------------------updateflatnux ------------------------------------------>
$tmperror = "";
if ($action == "update" && $cleanupdate == "" && $cleanobsolete == "")
{
	$fileupdate = FN_GetParam("fileupdate",$_POST,"flat");
	if ($file_clean != "" && $testupdate != 1)//make update
	{
		//check file permissions
		$tmperror = UpdateFlatnux("{$_FN['datadir']}/updates/tmp/$file_clean",true);
		if ($tmperror == "")
		{
			$UPDATELOG = "";
			// se tutti i files sono scrivibili:
			UpdateFlatnux("{$_FN['datadir']}/updates/tmp/$file_clean",false);
			FN_RemoveDir("{$_FN['datadir']}/updates");
			if (file_exists("{$_FN['datadir']}/firstinstall"))
				FN_JsRedirect("index.php");
			echo "<div>".FN_Translate("the installation was completed")."<div>";
		}
	}
	if ($file_clean != "" && $testupdate == 1)//test update
	{
		$tmperror = UpdateFlatnux("{$_FN['datadir']}/updates/tmp/$file_clean",$testupdate);
	}
}
//---------------------updateflatnux ------------------------------------------<
//------------------------------FORM ------------------------------------------>

if ($file_downloaded == false)
{

	echo "<div>".FN_Translate("if you can not upload the file here, you can load it manually via ftp in the folder").": {$_FN['datadir']}/updates/</div><br />";
	echo "\n<form  enctype=\"multipart/form-data\" action=\"?mod={$_FN['mod']}&opt=$opp&opmod=$opmod\" method=\"post\" >";
	echo FN_i18n("upload zip file");

	echo "\n<input size=\"20\" type=\"file\" name=\"flatnukefile\"  />";
	echo "\n<input type=\"hidden\" name=\"action\" value=\"upload\" />";
	echo "\n<input type=\"submit\" class=\"submit\" name=\"up\" value=\""."upload"."\" />";
	echo "\n</form>";
	echo "\n<div><br /><a onclick=\"return fn_to_ajax(this,'ccupdate');\" href=\"?opt=$opt&amp;downloadfrominternet=1\">".FN_Translate("download the latest version from the internet")."</a><br /></div>";
	echo ($versionerror );
}
$handle = opendir("{$_FN['datadir']}/updates/tmp");


$html_update = "";
if ($handle)
{
	while(false !== ($file = readdir($handle)))
	{
		if ($file == ".." | $file == ".")
			continue;
		$V = "";
		$R = "";
		if (is_dir("{$_FN['datadir']}/updates/tmp/$file") && file_exists("{$_FN['datadir']}/updates/tmp/$file/VERSION"))
		{
			$V = file_get_contents("{$_FN['datadir']}/updates/tmp/$file/VERSION");
		}
		if (file_exists("{$_FN['datadir']}/updates/tmp/$file/RELEASENOTES"))
		{

			$R .= htmlspecialchars(file_get_contents("{$_FN['datadir']}/updates/tmp/$file/RELEASENOTES"));
		}
		$html_update.= "<br /><input  ";
		$html_update.= " name=\"fileupdate\" type=\"hidden\" value=\"$file\" />$file  ";
		if ($V != "")
			$html_update.= "($V)";
		if (is_dir("{$_FN['datadir']}/updates/tmp/$file") && file_exists("{$_FN['datadir']}/updates/tmp/$file/Changelog"))
		{
			$html_update.= "&nbsp;-&nbsp;<a onclick=\"window.open(this.href);return false;\" href=\"{$_FN['datadir']}/updates/tmp/$file/Changelog\">Changelog</a>";
		}

		$html_update.= "<input type=\"hidden\" name=\"action\" value=\"update\" />";
	}
	closedir($handle);
}
if ($html_update)
{
	echo "<fieldset>";
	echo "<legend>".FN_i18n("updates avaiables")."</legend>";

	echo "\n\n<form  action=\"?mod={$_FN['mod']}&opt=$opp&opmod=$opmod\" method=\"post\" >";
	echo $html_update;
	echo "<br /><br /><input type=\"submit\" class=\"submit\" name=\"update\" value=\"".FN_i18n("execute update",false,"Aa")."\" />";
	echo "&nbsp;<input type=\"submit\" class=\"submit\" name=\"testupdate\" value=\"".FN_i18n("test update",false,"Aa")."\" />";
	echo "&nbsp;<input type=\"submit\" class=\"submit\" name=\"cleanupdate\" value=\"".FN_i18n("clean files",false,"Aa")."\" />";
	echo "</form></fieldset>";
}

if ($tmperror)
	echo $tmperror;
echo "<pre style=\"height:200px;overflow:auto\">";
echo "$UPDATELOG";
echo "</pre>";
echo "</div>";
//------------------------------FORM ------------------------------------------<
/**
 *
 * @global array $_FN
 * @global type $UPDATELOG
 * @param type $sourcefolder
 * @param type $testupdate 
 */
function UpdateFlatnux($sourcefolder,$testupdate)
{
	global $_FN,$UPDATELOG;

	$error = "";
	$makebackup = false;
	$handle = opendir($sourcefolder);
	while(false !== ($file = readdir($handle)))
	{
		if (basename($file) == "config.php")
		{
			$makebackup = true;
			$error .= UpdateConfigfile("$sourcefolder/$file","$file",$testupdate,$makebackup);
		}
		else
		{
			$makebackup = false;
			if (!is_dir($sourcefolder."/".$file))
			{
				$error .= Updatefile("$sourcefolder/$file","$file",$testupdate,$makebackup);
			}
		}
	}
	closedir($handle);
	$UPDATELOG .= "\n\nFILES:";
	$dirs = FN_ListDir($sourcefolder,true,true,true);
	foreach ($dirs as $dir)
	{
		$handle = opendir("$sourcefolder/$dir");
		while(false !== ($file = readdir($handle)))
		{
			if (!is_dir("$sourcefolder/$dir/$file"))
			{
				if (basename($file) == "config.php")
					$makebackup = true;
				else
					$makebackup = false;

				$error .= Updatefile("$sourcefolder/$dir/$file","$dir/$file",$testupdate,$makebackup);
			}
		}
		closedir($handle);
	}
	return $error;
}

/**
 *
 * @global string $UPDATELOG
 * @global array $_FN
 * @param string $source
 * @param string $dest
 * @param bool $testupdate
 * @param bool $makebackup
 * @return string 
 */
function Updatefile($source,$dest,$testupdate = 1,$makebackup = false)
{
	global $UPDATELOG,$_FN;
	$error = "";
	$sep = "/";
	$sourceabb = str_replace("{$_FN['datadir']}/updates/tmp/","",$source);
	if (strtoupper(substr(PHP_OS,0,3) == 'WIN'))
	{
		$sep = '\\';
	}
	$complete_path = str_replace('/',$sep,dirname($dest));
	$complete_name = str_replace('/',$sep,$dest);
	if (!file_exists($complete_path))
	{
		$tmp = '';
		foreach (explode($sep,$complete_path) as $k)
		{
			$tmp .= $k.$sep;
			if (!file_exists($tmp))
			{

				$UPDATELOG .= "\n<font color=\"orange\">+|$tmp (folder)</font>";
				if ($testupdate != 1)
				{
					FN_MkDir($tmp);
				}
			}
		}
	}

	$topudate = false;
	if (file_exists($dest))
	{
		$md5dest = md5(file_get_contents($dest));
		$md5source = md5(file_get_contents($source));

		if ($md5dest != $md5source)
		{
			if (!is_writable($dest))
			{
				$UPDATELOG .= "\n<font color=\"red\">>|$sourceabb -> $dest (".FN_i18n("is read-only").")</font>";
				$error .= "<font color=\"red\">$dest :".FN_i18n("is read-only")."</font>\n";
			}
			else
			{
				if ($makebackup)
					$UPDATELOG .= "\n<font color=\"magenta\">#|backup  $dest</font>";
				$UPDATELOG .= "\n<font color=\"blue\">>|$sourceabb -> $dest</font>";
				$topudate = true;
			}
		}
	}
	else
	{
		$UPDATELOG .= "\n<font color=\"green\">+|$sourceabb -> $dest</font>";
		$topudate = true;
	}
	if ($testupdate != 1 && $topudate)
	{
		if ($makebackup)
			if (file_exists($dest))
			{
				FN_BackupFile($dest);
			}
		FN_Copy($source,$dest);
	}
	return $error;
}

/**
 *
 * @global string $UPDATELOG
 * @global string $_FN
 * @param string $source
 * @param string $dest
 * @param bool $testupdate
 * @param bool $makebackup
 * @return string 
 */
function UpdateConfigfile($source,$dest,$testupdate = 1,$makebackup = false)
{
	global $UPDATELOG,$_FN;
	$error = "";
	$sep = "/";
	$sourceabb = str_replace("{$_FN['datadir']}/updates/tmp/","",$source);
	if (strtoupper(substr(PHP_OS,0,3) == 'WIN'))
	{
		$sep = '\\';
	}
	$complete_path = str_replace('/',$sep,dirname($dest));
	$complete_name = str_replace('/',$sep,$dest);
	if (!file_exists($complete_path))
	{
		$tmp = '';
		foreach (explode($sep,$complete_path) as $k)
		{
			$tmp .= $k.$sep;

			if (!file_exists($tmp))
			{

				$UPDATELOG .= "\n<font color=\"orange\">+|$tmp (folder)</font>";
				if ($testupdate != 1)
				{
					FN_MkDir($tmp);
				}
			}
		}
	}
	$topudate = false;
	if (file_exists($dest))
	{
		$md5dest = md5(file_get_contents($dest));
		$md5source = md5(file_get_contents($source));

		if ($md5dest != $md5source)
		{
			if (!is_writable($dest))
			{
				$UPDATELOG .= "\n<font color=\"red\">>|$sourceabb -> $dest (".FN_i18n("is read-only").")</font>";
				$error .= "<font color=\"red\">$dest :".FN_i18n("is read-only")."</font>\n";
			}
			else
			{
				if ($makebackup)
					$UPDATELOG .= "\n<font color=\"magenta\">#|backup  $dest</font>";
				$UPDATELOG .= "\n<font color=\"blue\">>|$sourceabb -> $dest</font>";
				$topudate = true;
			}
		}
	}
	else
	{
		$UPDATELOG .= "\n<font color=\"green\">+|$sourceabb -> $dest</font>";
		$topudate = true;
	}
	if ($testupdate != 1 && $topudate)
	{
		$newfilestring = file_get_contents($source);
		if (file_exists($dest))
		{
			$newfilestring = "";
			if ($makebackup)
			{
				//backup_file($dest);
			}
			//---file originale------------------------------->
			$oldfile = file($dest);
			for ($i = 0; $i < count($oldfile); $i++)
			{
				if (preg_match('/^\$./s',ltrim($oldfile[$i]))) // prende solo le righe che iniziano col carattere "$"
				{
					$line_tmp = explode(";",$oldfile[$i]); // cancella eventuali commenti a dx della variabile
					$varvalue = explode("=",ltrim($line_tmp[0]));
					if (isset($varvalue[1]))
					{
						$oldvalue[$varvalue[0]] = $oldfile[$i];
					}
				}
			}
			//---file originale-------------------------------<
			$newfile = file($source);
			for ($i = 0; $i < count($newfile); $i++)
			{
				if (preg_match('/^\$./s',ltrim($newfile[$i]))) // prende solo le righe che iniziano col carattere "$"
				{
					$line_tmp = explode(";",$newfile[$i]); // cancella eventuali commenti a dx della variabile
					$varvalue = explode("=",ltrim($line_tmp[0]));
					if (isset($varvalue[1]))
					{
						if (isset($oldvalue[$varvalue[0]]))
						{
							$newfile[$i] = $oldvalue[$varvalue[0]];
						}
					}
				}
				$newfilestring .= $newfile[$i];
			}
		}

		$newfile = fopen($dest,"w");
		fwrite($newfile,$newfilestring);
		fclose($newfile);
	}
	return $error;
}

/**
 *
 * @param type $file
 * @param type $path 
 */
function FNCC_unzip($file,$path)
{
	$zip = new FN_Unzipper($file);
	$zip->unzipAll($path);
}

/**
 *
 * @global type $_FNMESSAGE
 * @param type $dirpath
 * @param type $maxsize
 * @return type 
 */
function FNCC_check_size($dirpath,$maxsize)
{
	if (get_size($dirpath) < $maxsize)
	{
		if (get_size($dirpath) < disk_free_space("."))
		{
			return true;
		}
		else
		{
			echo FN_i18n('there is not enough free space on the server to make a backup');
			return false;
		}
	}
	else
	{
		echo FN_i18n('Too big of a file, I cannot execute this backup');
		return false;
	}
}

/**
 * Get folder size
 * @param type $dirpath
 * @return type 
 */
function FNCC_get_size($dirpath)
{
	$totalsize = 0;
	$dir = new FNCC_DeepDir();
	$dir->setDir($dirpath);
	$dir->load();
	foreach ($dir->files as $n=> $pathToFile)
	{
		$totalsize += filesize($pathToFile);
	}
	return $totalsize;
}

?>