<?php
/**
 * @package Flatnux_installer
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 1011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
global $_FN;
require_once ('include/flatnux.php');
header("Content-Type: text/html; charset={$_FN['charset_page']}");
if (file_exists("misc/firstinstall"))
{
	@unlink("{$_FN['datadir']}/{$_FN['database']}/fn_sections.php");
	@unlink("{$_FN['datadir']}/{$_FN['database']}/fn_sectionstypes.php");
	@unlink("{$_FN['datadir']}/{$_FN['database']}/fn_blocks.php");
	FN_InitTables();
//---elimino cookie di installazioni residuo
	if (!isset($_GET ['step']))
	{
		foreach ($_COOKIE as $key=> $value)
		{
			if ($key != "fnuser" && $key != "secid")
				setcookie($key,"",time() - 3600,$_FN ['urlcookie']);
		}
	}
	$lang = FN_SaveGetPostParam("lang");
	if ($lang == "" || !file_exists("languages/$lang"))
		$lang = "en";
	$_FN['lang'] = $lang;
	$_FN['datadir'] = "misc";
	$errimg = "<img src=\"images/error.png\" alt=\"\"/>&nbsp;";
	echo "<?xml version=\"1.0\"?>
<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">
<html xmlns=\"http://www.w3.org/1999/xhtml\" lang=\"{$_FN['lang']}\" xml:lang=\"{$_FN['lang']}\">";
	echo "
<head>
<title>Flatnux Installer</title>";
	echo "\n\t<title>{$_FN['site_title']}</title>";
	echo FN_IncludeJS();
	echo "<link rel='StyleSheet' type='text/css' href=\"controlcenter/css/installer.css\" />";
	echo "</head>
	<html><body><div>";
	$step = isset($_GET ['step']) ? $_GET ['step'] : 0;
	FNINSTALLER_OpenInstallerTable("installer");
	if (FN_CountUsers() > 0 && !FN_IsAdmin())
	{
		FNINSTALLER_OpenInstallerBody();
		echo FN_LoginForm();
		FNINSTALLER_CloseInstallerTable();
		FNINSTALLER_CloseInstallerBody();
		echo "</div></body></html>";
		die();
	}
	switch($step)
	{
		case 0 :
			echo "\n<form name=\"f\" action=\"?step=".($step + 1)."\" method=\"post\">";
			FNINSTALLER_OpenInstallerBody();
			echo "\n<br /><br /><b>".FN_Translate("language").":</b>";

			echo "\n<select name=\"lang\" onchange=\"window.location='?lang='+ document.f.lang.options[document.f.lang.selectedIndex].value + '&amp;step=$step'\" >";
			$listlanguages = explode(",",$languages);
			$files = glob("languages/*");
			foreach ($files as $cf)
			{
				$sv = preg_replace('/.php$/si','',basename($cf));
				$ticf = FN_GetFolderTitle($cf);
				$s = "";
				if ($lang == $sv)
					$s = "selected=\"selected\"";
				echo "\n<option $s value=\"$sv\">$ticf [$sv]</option>";
			}
			echo "</select>";
			echo "<br />";
			echo "<br />";
			FNINSTALLER_CloseInstallerBody();
			echo "<button disabled=\"disabled\"  type=\"button\" onclick=\"return false;\" >&#060;&#060; ".FN_Translate("back")."</button>";
			echo "<button type=\"submit\" > ".FN_Translate("next")."&#062;&#062;</button>";
			echo "\n</form>";
			break;
		case 1:
			FNINSTALLER_OpenInstallerBody();
			echo "<br />\n";
			echo "\n";
			echo FN_Translate("the data folder is this").":";
			echo "\n<br />".realpath($_FN ['datadir'])."<br /><br />";
			$err = false;
			if (is_dir($_FN ['datadir']) && is_writable($_FN ['datadir']) && @FN_Write(" ","{$_FN ['datadir']}{$_FN ['slash']}firstinstall"))
			{
				echo "<img src=\"images/ok.png\" alt=\"ok\"/>&nbsp;";
				echo FN_Translate("the folder permissions are correct");
			}
			else
			{
				echo "<br />$errimg".FN_Translate("permissions error").":";
				echo "<br /><pre>".realpath($_FN ['datadir'])."</pre>";
				$err = true;
			}
			if (!file_exists($_FN ['datadir']) || !is_dir($_FN ['datadir'])) //cartella $_FN['datadir'] non esistente
			{
				echo $errimg.FN_Translate("folder dont exists");
				$err = true;
			}
			else
			{
				if (!file_exists("{$_FN['datadir']}/firstinstall"))
				{
					$err = true;
					echo "\n<br /><br />$errimg".FN_i18n("firstinstall not exists");
				}
				if (!is_writable($_FN ['datadir'])) //cartella $_FN['datadir'] non scrivibile
				{
					echo "<br />$errimg".FN_Translate("permissions error")."<br />";
					echo "<pre>".FN_Translate("run this command on the server").":";
					echo "\nchmod -R a+w ".dirname($_SERVER ['SCRIPT_FILENAME'])."/{$_FN['datadir']}/";
					echo "\n";
					echo "\n";
					echo "</pre>";
					$err = true;
				}
			}
			FNINSTALLER_CloseInstallerBody();
			echo "\n<form action=\"?step=".($step + 1)."\" method=\"post\">";
			echo "<button type=\"button\" onclick=\"window.location='?step=".($step - 1 )."';\" >&#060;&#060; ".FN_Translate("back")."</button>";
			if ($err == false)
			{
				echo "<button type=\"submit\" > ".FN_Translate("next")."&#062;&#062;</button>";
			}
			else
			{
				echo "&nbsp;<button onclick=\"window.location='install.php?step=1'\" type=\"button\" > ".FN_Translate("retry")."</button>";
			}
			echo "\n</form>";

			break;
		case 2:
			if (count($_POST) == 0)
			{
				$t = FN_XmlTable("fn_settings");
				$res = $t->GetRecord(array("varname"=>"languages"));
				if (empty($res["varname"]) || $res["varvalue"] == $res["defaultvalue"])
				{
					$_POST['conf_languages'] = $_FN['lang'];
				}
			}
			FNINSTALLER_OpenInstallerBody();
			echo "<fieldset><legend>".FN_Translate("settings")."</legend>";
			FN_EditConfFile("config.php","?step=$step",false,array("languages","theme","sitename","site_title","site_subtitle","site_email_address","home_section"));
			FNINSTALLER_CloseInstallerBody();
			echo "</fieldset>";
			echo "\n<form action=\"?step=".($step + 1)."\" method=\"post\">";
			if (isset($_POST['conf_sitename']))
			{
				echo "<button type=\"button\" onclick=\"window.location='?step=".($step)."';\" >&#060;&#060; ".FN_Translate("back")."</button>";
				echo "<button type=\"submit\" > ".FN_Translate("next")."&#062;&#062;</button>";
			}
			else
			{
				echo "<button type=\"button\" onclick=\"window.location='?step=".($step - 1)."';\" >&#060;&#060; ".FN_Translate("back")."</button>";
			}
			echo "\n</form>";
			break;

		case 3:
			$err = array();
			if (FN_CountUsers() == 0)
			{
				$sections = FN_XmlTable("fn_sections");
				$sections->UpdateRecord(array("position"=>"0","id"=>$_FN['home_section']));//($values,$pkey,$pvalue)
				foreach ($_FN['listlanguages']as $l)
				{
					if (file_exists("include/install/section.$l.html") && !file_exists("home/section.$l.html"))
					{
						FN_Copy("include/install/section.$l.html","sections/home/section.$l.html");
					}
				}
				$tabuser = FN_GetUserForm();
				foreach ($tabuser->formvals as $k=> $v)
				{
					if (!isset($v ['frm_required']) || $v ['frm_required'] != 1)
						$tabuser->formvals [$k] ['frm_show'] = 0;
				}
				$tabuser->setlayoutTags("<b>","</b><br />","","<br />");
				//$err = "-";
				$newvalues = array();
				if (isset($_POST ['reg']))
				{
					$newvalues = $tabuser->getbypost();
					$err = $tabuser->Verify($newvalues);
					foreach ($err as $field=> $error)
					{
						$tabuser->setlayoutTag($field,"<b>","</b><span style=\"color:red;background-color:#ffffff\">{$error['error']}</span><br />","","<br />");
					}
					$newvalues ['active'] = "1";
					$newvalues ['level'] = "10";
					$newvalues ['group'] = "users";
					$newvalues ['registrationdate'] = FN_Now();

					if (count($err) == 0)
					{
						$newvalues['rnd'] = md5(rand(1000000000,9999999999)).md5(rand(1000000000,9999999999));
						FN_AddUser($newvalues);
						$textmail = "";
						$textmail .= "";
						$textmail .= "";
						$textmail .= "";
						$textmail .= "";
						$textmail .= "";
						$textmail .= "<h2>".FN_Translate("your installation was completed")."</h2>";
						$textmail .= "<br /><br /><b>".FN_Translate("site url")."</b>: <a href=\"{$_FN['siteurl']}\">".$_FN['siteurl']."index.php?theme=__default__</a><br />";
						$textmail .= "<b>".FN_Translate("control center")."</b>: <a href=\"{$_FN['siteurl']}controlcenter.php\">".$_FN['siteurl']."controlcenter.php</a><br />";
						$textmail .= "<b>".FN_Translate("administrator user")."</b>: ".$newvalues['username'];
						$textmail .= "<hr /><br />";
						$textmail .= "<br />Powered by <a href=\"http://www.flatnux.org\">FlatnuX CMS</a>";
						$textmail .= "";
						$textmail .= "";
						$textmail .= "";
						FN_SendMail($newvalues['email'],$_FN['sitename'],$textmail,true);
						FN_Login($newvalues ['username']);
						unlink("{$_FN['datadir']}/firstinstall");
						FN_JsRedirect("index.php","&");
						FN_OnSitemapChange();
						die("</body></html>");
					}
				}
			}
			if (count($err) != 0 || FN_CountUsers() == 0)
			{
				echo "\n<form action=\"?step=".($step)."\" method=\"post\" >";
				FNINSTALLER_OpenInstallerBody();
				echo "<h2>".FN_Translate("please register the administrator")."</h2>";
				$newvalues ['passwd'] = "";
				$tabuser->ShowInsertForm(false,$newvalues);
				echo "<input type=\"hidden\" name=\"reg\" value=\"reg\" />";
				FNINSTALLER_CloseInstallerBody();
				echo "<button type=\"button\" onclick=\"window.location='?step=".($step - 1)."';\" >&#060;&#060; ".FN_Translate("back")."</button>";
				echo "<button type=\"submit\" > ".FN_Translate("next")."&#062;&#062;</button>";
				echo "\n</form>";
			}
			else
			{
				unlink("{$_FN['datadir']}/firstinstall");
				FN_JsRedirect("index.php","&");
			}
			break;
	}
	FNINSTALLER_CloseInstallerTable();
	echo "</body></html>";
}
else
{
	FN_JsRedirect("index.php");
}
/**
 * 
 */
function FNINSTALLER_OpenInstallerBody()
{
	echo "<div id=\"installerbody\">";
}

/**
 *
 */
function FNINSTALLER_CloseInstallerBody()
{
	echo "</div>";
}

/**
 *
 */
function FNINSTALLER_OpenInstallerTable()
{
	echo "<div id=\"fninstaller\">";
	echo "<div id=\"fninstallertitle\">Flatnux installer</div>";
}

/**
 *
 */
function FNINSTALLER_CloseInstallerTable()
{
	echo "</div>";
}

?>
