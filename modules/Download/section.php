<?php
/**
 * @package Flatnux_section_download
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2003-2005
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 *
 */
defined('_FNEXEC') or die('Restricted access');
global $_FN;
$config = FN_LoadConfig("modules/Download/config.php");
$config['download_folder'] = empty($config['download_folder']) ? "Download" : $config['download_folder'];
if (!file_exists($_FN['datadir']."/{$config['download_folder']}"))
	mkdir($_FN['datadir']."/{$config['download_folder']}");

$dirname = FN_GetPAram("dirname",$_POST,"flat");
$downloadfile = FN_GetPAram("downloadfile",$_GET,"flat");
$deldir = FN_GetPAram("deldir",$_GET,"flat");
$delfile = FN_GetPAram("delfile",$_GET,"flat");
$filename = FN_GetPAram("filename",$_POST,"flat");
$title = FN_GetPAram("title",$_POST,"flat");
$location = FN_GetPAram("location",$_POST,"flat");
if (strstr($dirname,".."))
	$dirname = "";
if (strstr($downloadfile,".."))
	$downloadfile = "";
if (empty($_GET['f']))
	echo FN_HtmlContent("sections/{$_FN['mod']}");
FNFILES_gest_download();
//------------------------------------------------------------------------------
if (FN_IsAdmin())
{
	if ($delfile != "")
	{

		$filetodel = $_FN['datadir']."/{$config['download_folder']}/$delfile";
		if (file_exists($filetodel) && unlink($filetodel))
			FN_Alert("file deleted");
	}
	if ($deldir != "")
	{
		$filename = (FN_GetPAram("filename",$_GET,"flat"));
		$handle = opendir($filename);
		while(false !== ($file = readdir($handle)))
		{
			if (preg_match("/^description./si",$file))
			{
				unlink($filename."/$file"); //elimino le descrizioni
			}
		}
		rmdir($filename);
	}
	if (isset($_POST['newdir']))
	{
		mkdir($_FN['datadir']."/{$config['download_folder']}/".$dirname);
		$filetoopen = ($_FN['datadir']."/{$config['download_folder']}/".$dirname)."/description.{$_FN['lang']}.html";
		$hfile = fopen($filetoopen,"w");
		fwrite($hfile,$title);
	}
	if (isset($_POST['changetitle']))
	{
		$filetoopen = $filename;
		$hfile = fopen($filetoopen,"w");
		fwrite($hfile,$title);
	}
	if ($_FN['fneditmode'] != 0)
	{
		?>
		<span class='flatnukeadmin'><b><?php echo FN_i18n("create folder",false,"Aa")?></b>
			<form method="post" action="<?php echo FN_RewriteLink("index.php?mod={$_FN['mod']}")?>" >
				<?php echo FN_i18n("name",false,"Aa")?>:<input size="8" type="text" name="dirname" />&nbsp;
				<?php echo FN_i18n("title")?>:<input type="text" name="title" /> <input
					type="submit" class="submit" name="newdir" value="<?php echo FN_i18n("create")?>" />
			</form>
		</span>
		<?php
	}
	if (isset($_POST['send']))
	{
		$fileToOpen = $_FN['datadir']."/{$config['download_folder']}/".$location;
		$file_clean = FN_StripPostSlashes($_FILES['filename']['name']);
		if (!move_uploaded_file($_FILES['filename']['tmp_name'],$fileToOpen."/".$file_clean))
		{
			echo "<br />Error: $fileToOpen -> $file_clean<br />";
		}
	}
}
FNFILES_view_download();
function FNFILES_view_download()
{
	global $_FN;
	$config = FN_LoadConfig("modules/Download/config.php");
	$config['download_folder'] = empty($config['download_folder']) ? "Download" : $config['download_folder'];
	$sectionslist = array();
	// crea elenco di tutte le sezioni presenti
	$handle = opendir($_FN['datadir']."/{$config['download_folder']}");
	while(false !== ($file = readdir($handle)))
	{
		if (!($file == "." or $file == "..") and (!preg_match("/^\\./s",$file) and ($file != "CVS") and is_dir($_FN['datadir']."/{$config['download_folder']}/$file")))
		{
			$sectionslist[] = $file;
		}
	}
	closedir($handle);
	sort($sectionslist);
	//tabella statistiche
	$stat = new XMLTable("fndatabase","download",$_FN['datadir']);
	echo "<br />";
	foreach ($sectionslist as $downloadsection)
	{
		echo FN_HtmlOpenTableTitle("<img style=\"vertical-align: middle;border:0px\" alt=\"\"  src=\"".FN_FromTheme("images/mime/dir.png")."\" />&nbsp;"."<b>".htmlentities($downloadsection)."</b>");
		$filedescr = $_FN['datadir']."/{$config['download_folder']}/".$downloadsection."/description.{$_FN['lang']}.html";
		if (file_exists($filedescr))
		{
			echo "<em>".htmlentities(file_get_contents($filedescr))."</em><br />";
		}
		$modlist_down = array();
		$handle_down = opendir($_FN['datadir']."/{$config['download_folder']}/".$downloadsection);
		while(false !== ($file = readdir($handle_down)))
		{
			if (!($file == "." || $file == "..") && (!preg_match("/^\\./s",$file) && ($file != "CVS") && (!preg_match("/^description./si",$file))))
			{
				$modlist_down[] = $file;
			}
		}
		closedir($handle_down);
		if (FN_IsAdmin() && $_FN['fneditmode'] != 0)
		{
			if (count($modlist_down) == 0)
				echo " [<a href=\"javascript:check('{$_FN['siteurl']}index.php?mod={$_FN['mod']}&amp;filename=".$_FN['datadir']."/Download/".htmlentities(addslashes($downloadsection),ENT_QUOTES)."&amp;deldir=1')\">".FN_i18n("delete")."</a>]";

			echo "

			<span class=\"flatnukeadmin\">
				<form method=\"post\" action=\"?mod={$_FN['mod']}\">
		".FN_i18n("title").":<textarea rows=\"1\" cols=\"30\" name=\"title\">".htmlentities(@file_get_contents($filedescr),ENT_QUOTES).
			"</textarea>
		<input type=\"hidden\" name=\"filename\"
			   value=\"".htmlentities($filedescr,ENT_QUOTES)."\"> <input
			   type=\"submit\" class=\"submit\" value=\"".FN_i18n("change")."\"
			   name=\"changetitle\"></form></span>";
		}
		sort($modlist_down);
		foreach ($modlist_down as $downloadfile)
		{
			$downloadlink = FN_RewriteLink("index.php?mod={$_FN['mod']}&amp;f=".htmlentities($downloadsection,ENT_QUOTES)."/".htmlentities($downloadfile,ENT_QUOTES));
			echo "<br />&nbsp;&nbsp;&nbsp;&nbsp;";
			$dimg = fn_GetIconByFilename($downloadfile);
			echo "<a href=\"$downloadlink\">";
			echo "<img style=\"vertical-align: middle;border:0px;\" alt=\"download ".htmlentities($downloadfile)."\"  src=\"$dimg\" "."/></a>&nbsp;"."<a title=\"download $downloadfile\" href=\"$downloadlink\" ".">"."$downloadfile"."</a> ";
			$fsize = filesize($_FN['datadir']."/{$config['download_folder']}/".$downloadsection."/".$downloadfile);
			$suff = "bytes";
			if ($fsize > 1024)
			{
				$fsize = round($fsize / 1024,2);
				$suff = "Kb";
			}
			if ($fsize > 1024)
			{
				$fsize = round($fsize / 1024,2);
				$suff = "Mb";
			}
			$filetodown = $downloadsection."/".$downloadfile;
			$val = $stat->GetRecordByPrimaryKey($filetodown);
			//dprint_r($stat);
			$count = isset($val['numdownload']) ? $val['numdownload'] : 0;
			if (FN_IsAdmin() || $config['show_stats'] == 1)
			{
				$st = " | $count Download";
			}
			echo "&nbsp;($fsize $suff$st) ";
			if (FN_IsAdmin())
				echo "[<a href=\"javascript:check('{$_FN['siteurl']}index.php?mod={$_FN['mod']}&amp;delfile=".htmlentities(($downloadsection),ENT_QUOTES)."/".htmlentities(($downloadfile),ENT_QUOTES)."')\" >".FN_i18n("delete")."</a>]";
			echo "<br />";
		}
		echo "<br />";
		if (FN_IsAdmin() && $_FN['fneditmode'] != 0)
		{
			?>
			<span class="flatnukeadmin">
				<form enctype="multipart/form-data" method="post"
					  action="?mod=<?php echo $_FN['mod']?>" >
					<?php echo FN_i18n("send file")?> : <input type="file" name="filename"
							 value="upload" /> <input type="hidden" name="MAX_FILE_SIZE"
							 value="1000000" /> <input type="submit" value="<?php echo FN_i18n("send")?>"
							 name="send" /> <input type="hidden"
							 value="<?php echo htmlentities($downloadsection,ENT_QUOTES)?>"
							 name="location" /></form>
			</span>
			<?php
		}
		echo FN_HtmlCloseTableTitle();
		echo "<br />";
	}
}

function FNFILES_gest_download()
{

	global $_FN;
	include_once "./include/flatnux.php";
	$config = FN_LoadConfig("modules/Download/config.php");
	$config['download_folder'] = empty($config['download_folder']) ? "Download" : $config['download_folder'];
	$file = FN_GetParam("f",$_GET,"flat");
	if ($file == "")
		return;
	if (stristr($file,".."))
		die();
	$fields = array();
	if (!file_exists($_FN ['datadir']."/fndatabase/download.php"))
	{
		$fields [1] ['name'] = "filename";
		$fields [1] ['primarykey'] = "1";
		$fields [2] ['name'] = "numdownload";
		$fields [2] ['defaultvalue'] = "0";
		createxmltable("fndatabase","download",$fields,$_FN ['datadir'],"download");
	}
	$stat = new XMLTable("fndatabase","download",$_FN ['datadir']);
	$oldval = $stat->GetRecordByPrimaryKey($file);
	$r ['filename'] = $file;
	if ($oldval == null)
	{
		$r ['numdownload'] = 1;
		$t = $stat->InsertRecord($r);
	}
	else
	{
		$r ['numdownload'] = $oldval ['numdownload'] + 1;
		$t = $stat->UpdateRecord($r);
	}

	if (FN_GetFileExtension($file) == "link")
	{
		header("Location: ".file_get_contents($_FN ['datadir']."/{$config['download_folder']}/$file"));
	}
	else
		FN_SaveFile(file_get_contents($_FN ['datadir']."/{$config['download_folder']}/$file"),basename($_FN ['datadir']."/{$config['download_folder']}/$file"));
}
?>