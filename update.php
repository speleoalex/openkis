<?php
global $_FN;
require_once "include/flatnux.php";
ini_set("max_execution_time","30000");
if (1) // 0 is unsafe
{
	if (!FN_IsAdmin())
	{
		echo FN_LoginForm();
		die("---");
	}
}

$op=FN_GetParam("op",$_GET,"html");

if ($op == "" && empty($_GET['f']))
{
	echo "<h3>USAGE:</h3>";
	echo "<div> {$_FN['siteurl']}update.php?f=[file name]<br />  {$_FN['siteurl']}update.php?op=charset/importusers/</div>";
}

//--------------fix table charsets--------------------------------------------->	
if ($op == "charset")
{
	$tablename=FN_GetParam("table",$_GET,"html");
	if ($tablename != "")
	{
		ConvertCharsetInTable($tablename);
		echo "<div>.....convert to UTF-8</div>";
	}
	//else
	{
		$files=glob("misc/fndatabase/*.php");
		echo "table? list tables:";
		foreach ($files as $file)
		{
			$t=str_replace(".php","",basename($file));
			echo "<br /><a href=\"?op=charset&amp;table=$t\">$t</a>";
		}
	}
	die("---end---");
}

//--------------fix table charsets---------------------------------------------<
//--------------import old users----------------------------------------------->
if ($op == "importusers")
{
	if (file_exists("misc/fndatabase/users"))
	{
		$t=FN_XmlTable("users");
		$t2=FN_XmlTable("fn_users");
		$old_users=$t->GetRecords();
		foreach ($old_users as $user)
		{
			if (!empty($_GET["active"]) && $user['active'] != "1")
			{
				
			}
			else
			{
				if (!FN_GetUser($user['username']))
				{
					$t2->InsertRecord($user);
				}
			}
		}
	}
}
//--------------import old users-----------------------------------------------<
//--------------porting old module--------------------------------------------->

$filename=isset($_GET['f']) ? $_GET['f'] : "";
if (file_exists($filename))
{

	$str=file_get_contents($filename);
	//costanti
	$str=str_replace("PAR_GET","\$_GET",$str);
	$str=str_replace("PAR_POST","\$_POST",$str);
	$str=str_replace("PAR_SERVER","\$_SERVER",$str);
	$str=str_replace("PAR_COOKIE",'$_COOKIE',$str);
	$str=str_replace("SAN_FLAT","\"flat\"",$str);
	$str=str_replace("SAN_HTML",'"html"',$str);
	//funzioni
	$str=str_replace("getparam","FN_GetParam",$str);
	$str=str_replace("fn_i18n",'FN_i18n',$str);
	$str=str_replace('FieldForm','FieldFrm',$str);
	$str=str_replace("fromtheme",'FN_FromTheme',$str);
	$str=str_replace("isadmin",'FN_IsAdmin',$str);
	$str=str_replace("is_admin",'FN_IsAdmin',$str);
	$str=str_replace("is_spam",'FN_IsSpam',$str);
	$str=str_replace("FieldForm",'FieldFrm',$str);
	$str=str_replace("get_file_extension",'FN_GetFileExtension',$str);
	$str=str_replace("tag2html",'FN_Tag2Html',$str);
	$str=str_replace('jsalert',' FN_Alert',$str);
	$str=str_replace('FN_cc_edit_xmltable',' FN_XmltableEditor',$str);



	//stringhe
	$str=str_replace('"_INDIETRO"','"back"',$str);
	$str=str_replace('"_LOGIN"','"login"',$str);
	$str=str_replace('"_NOMEUTENTE"','"username"',$str);
	$str=str_replace(' _NOMEUTENTE ',' FN_i18n("username") ',$str);
	$str=str_replace(' _LOGIN ',' FN_i18n("login") ',$str);
	$str=str_replace(' _LOGOUT ',' FN_i18n("login") ',$str);
	$str=str_replace(' _POS ',' FN_i18n("position") ',$str);
	$str=str_replace(' _CREA ',' FN_i18n("create") ',$str);
	$str=str_replace(' _SAVE ',' FN_i18n("save") ',$str);
	$str=str_replace(' _SENDNEWIMAGE ',' FN_i18n("send new image") ',$str);
	$str=str_replace(' _NEWFOLDER ',' FN_i18n("new folder") ',$str);
	$str=str_replace(' _ELIMINA ',' FN_i18n("delete") ',$str);
	$str=str_replace(' _MODIFICA ',' FN_i18n("modify") ',$str);
	$str=str_replace(' _CHARSET ',' FN_i18n("_CHARSET") ',$str);
	$str=str_replace(' _INDIETRO',' FN_i18n("back")',$str);
	$str=str_replace(' _FUTENTI',' FN_i18n("users")',$str);
	$str=str_replace(' _FGUIDA',' FN_i18n("help")',$str);
	$str=str_replace(' _FNOME',' FN_i18n("name")',$str);
	$str=str_replace(' _EXEC',' FN_i18n("execute")',$str);

	$str=str_replace('_NONPUOI','FN_i18n("operation is not permitted")',$str);
	$str=str_replace('_THISISSPAM',' FN_i18n("operation is not allowed because it was identified as spam")',$str);
	//variabili
	$str=str_replace('$_FN[\'idmod\']','$_FN[\'mod\']',$str);
	$str=str_replace('$_FN[\'vmod\']','$_FN[\'mod\']',$str);
	$str=str_replace('$_FN[\'id\']','$_FN[\'mod\']',$str);
	$str=str_replace('$_FN [\'idmod\']','$_FN[\'mod\']',$str);
	$str=str_replace('$_FN [\'vmod\']','$_FN[\'mod\']',$str);
	$str=str_replace('$_FN [\'id\']','$_FN[\'mod\']',$str);
	echo "<pre>";
	echo htmlspecialchars($str,ENT_QUOTES,$_FN['charset_page']);
	echo "</pre>";

//--------------porting old module---------------------------------------------<
}
/**
 *
 * @param string $tablename 
 */
function ConvertCharsetInTable($tablename)
{
	$table=FN_XmlTable($tablename);
	$all=$table->GetRecords();
	foreach ($all as $item)
	{
		$newvalues=$item;
		$toupdate="";
		foreach ($item as $k=>$v)
		{
			if (XMLDB_IsIso8859($v) && !XMLDB_IsUtf8($v))
			{
				$newvalues[$k]=XMLDB_ConvertEncoding($v,"ISO-8859-1","UTF-8");
				$toupdate .= "$k ";
			}
		}
		if ($toupdate != "")
		{
			$table->UpdateRecord($newvalues);
			dprint_r($newvalues[$table->primarykey]);
			dprint_r($toupdate);
		}
	}
}
?>