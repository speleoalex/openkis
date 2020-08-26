<?php

/**
 * @package Flatnux_module_contacts
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 *
 */
defined('_FNEXEC') or die('Restricted access');
global $_FN;
FN_LoadMessagesFolder("sections/{$_FN['mod']}/");
$config=FN_LoadConfig("modules/contacts/config.php");
$tablename=empty($config['tablename']) ? "contact_message" : $config['tablename'];
//session_start();
if (!file_exists("{$_FN['datadir']}/{$_FN['database']}/$tablename.php"))
    INS_InitTable("$tablename");
echo FN_HtmlContent("sections/{$_FN['mod']}");
echo "<a name=\"contactsform\"></a>";
INS_GestInsert("$tablename");

//dprint_r($_POST);
/**
 *
 * @global array $_FN
 * @param string $tablename
 * @param array $params
 */
function INS_GestInsert($tablename,$params=array())
{
    global $_FN,$_FNMESSAGE;
    FN_LoadMessagesFolder("sections/{$_FN['mod']}/");

    $captcha=FN_GetParam("captcha",$_POST,"flat");
    $config=FN_LoadConfig("modules/contacts/config.php");
    $savemessage=$config['savemessage'];
    $usermail=$config['usermail'];
    if ($usermail== "")
    {
        $usermail=$_FN['log_email_address'];
    }
    $params['requiredtext']="";
    $Table=FN_XmlForm($tablename,$params);
    $Table->setlayout("flat");
    $newvalues=false;
    $errors=array();
    $wrong_antispam=false;
    $captcha=FN_GetSessionValue("captcha");
    $captcha=FN_GetParam("security_code",$captcha);
    $security_code=FN_GetParam("security_code",$_POST);
    if (count($_POST) > 0)
    {
        $newvalues=$Table->GetByPost();
        $newvalues['username']=$_FN['user'];
        $newvalues['subject']=isset($newvalues['subject']) ? $newvalues['subject'] : $_FN['mod']." ".FN_FormatDate(time());
        $errors=$Table->Verify($newvalues);
        // checking the value of anti-spam code inserted
        if ($config['enable_captcha']!= 0)
        {
            if (empty($security_code) || $captcha!= $security_code)
            {
                FN_SetSessionValue("captcha",array("security_code"=>""));
                $wrong_antispam=true;
            }
        }
        if (count($errors)== 0 && !$wrong_antispam)
        {
            //nuovo record---->
            if ($newvalues)
            {
                if ($savemessage== 1)
                {
                    $newvalues['ip']=FN_GetParam("REMOTE_ADDR",$_SERVER,"html");
                    $newvalues['date']=FN_Now();
                    $Table->InsertRecord($newvalues);
                }
                $mailbody="<pre>\n";
                $Table->SetLayoutView("table");
                $mailbody.=$Table->HtmlShowView($newvalues,1);
                $mailbody.="<br />".FN_Translate("view all messages").":";
                $mailbody.="<br /><br /><a href=\"{$_FN['siteurl']}/controlcenter.php?mod=contacts&opt=fnc_ccnf_section_contacts\">{$_FN['siteurl']}/controlcenter.php?mod=contacts&opt=fnc_ccnf_section_contacts</a>";
                $mailbody.="</pre>";
                FN_SendMail($usermail,$newvalues['subject'],$mailbody,true);
                $template_path=file_exists("themes/{$_FN['theme']}/modules/contacts/contacts_form_success.tp.html") ? "themes/{$_FN['theme']}/modules/contacts/contacts_form_success.tp.html" : "modules/contacts/contacts_form_success.tp.html";
                $basepath=dirname($template_path)."/";
                $templateForm=file_get_contents($template_path);
                $templateForm=FN_TPL_ApplyTplString($templateForm,array(),$basepath);
                echo $templateForm;
                echo "<div />";
                FN_SetSessionValue("captcha",array("security_code"=>""));
            }
            else
            {
                echo FN_Translate("error");
                //error
            }
            //nuovo record----<
            return;
        }
    }
    $template_path=file_exists("themes/{$_FN['theme']}/modules/contacts/contacts_form.tp.html") ? "themes/{$_FN['theme']}/modules/contacts/contacts_form.tp.html" : "modules/contacts/contacts_form.tp.html";
    $templateForm=file_get_contents($template_path);
    $basepath=dirname($template_path)."/";
    $templateForm=FN_TPL_ApplyTplString($templateForm,array(),$basepath);
    $templateFields=FN_TPL_GetHtmlPart("contacts_formfields",$templateForm);
    $Table->SetlayoutTemplate($templateFields);
    $htmlFields=$Table->HtmlShowInsertForm(false,$newvalues,$errors);
    $tplvars=array();
    $tplvars['contactsform_action']=FN_RewriteLink("index.php?mod={$_FN['mod']}");
    $tplvars['url_captcha']="{$_FN['siteurl']}/captcha.php?t=security_code";
    $templateForm=FN_TPL_ReplaceHtmlPart("contacts_formfields",FN_TPL_encode($htmlFields),$templateForm);


//antispam--------------------------------------------------------------------->
    if ($config['enable_captcha']!= 0)
    {
        FN_SetSessionValue("captcha",array("security_code"=>rand(1000,9999)));
        if (!$wrong_antispam)
        {
            $templateForm=FN_TPL_ReplaceHtmlPart("wrong_antispam","",$templateForm);
        }
    }
    else
    {
        $templateForm=FN_TPL_ReplaceHtmlPart("captcha","",$templateForm);
    }
//antispam---------------------------------------------------------------------<
    //   dprint_xml($templateForm);
    $templateForm=FN_TPL_ApplyTplString($templateForm,$tplvars,$basepath);


    echo $templateForm;
}

/**
 *
 * @global array $_FN
 * @param type $tablename
 */
function INS_InitTable($tablename)
{
    global $_FN;
    $xml='<?xml version="1.0" encoding="UTF-8"?>
<?php exit(0);?>
<tables>
	<field>
		<name>id</name>
		<primarykey>1</primarykey>
		<frm_show>0</frm_show>
		<extra>autoincrement</extra>
	</field>
	<field>
		<name>archived</name>
		<frm_show>0</frm_show>
                <frm_type>bool</frm_type>
	</field>
        <field>
		<name>name</name>
		<frm_i18n>name</frm_i18n>
		<frm_help_i18n>insert your name here</frm_help_i18n>
	</field>
	<field>
		<name>surname</name>
		<frm_i18n>surname</frm_i18n>
		<frm_help_i18n>insert your surname here</frm_help_i18n>
	</field>
	<field>
		<name>contact</name>
		<frm_i18n>email</frm_i18n>
		<frm_validator>FN_CheckMail</frm_validator>
		<frm_required>1</frm_required>
	</field>
	<field>
		<name>telephone</name>
		<frm_i18n>telephone</frm_i18n>
		<frm_required>0</frm_required>
	</field>
	<field>
		<name>subject</name>
		<frm_i18n>subject</frm_i18n>
		<frm_required>1</frm_required>
	</field>
	<field>
		<name>message</name>
		<frm_i18n>message</frm_i18n>
		<type>text</type>
		<frm_cols>60</frm_cols>
		<frm_rows>auto</frm_rows>
		<frm_required>1</frm_required>
	</field>
	<field>
		<name>date</name>
		<frm_i18n>date</frm_i18n>
		<frm_show>0</frm_show>
		<view_show>1</view_show>
	</field>

	<field>
		<name>ip</name>
		<frm_i18n>ip</frm_i18n>
		<frm_show>0</frm_show>
		<view_show>1</view_show>
	</field>
</tables>
';
    FN_Write($xml,"{$_FN['datadir']}/{$_FN['database']}/$tablename.php");


    $t=FN_XmlTable("{$tablename}");
    if (empty($t->fields['status']))
    {
        $field['name']="status";
        $field['frm_i18n']="status";
        $field['frm_show']=0;
        addxmltablefield($t->databasename,$t->tablename,$field,$_FN['datadir']);
        $t=FN_XmlTable("{$tablename}");
    }
    if (empty($t->fields['date']))
    {
        $field['name']="date";
        $field['frm_i18n']="date";
        $field['frm_show']="1";
        addxmltablefield($t->databasename,$t->tablename,$field,$_FN['datadir']);
        $t=FN_XmlTable("{$tablename}");
    }    
}

/**
 *
 * @global array $_FN
 * @param type $values
 * @param type $sep
 * @return type 
 */
function INS_MakeLink($values,$sep="&amp;")
{
    global $_FN;
    if ($values)
        foreach($values as $k=> $v)
        {
            $link[$k]=urlencode($v);
        }
    $linkclean="";
    foreach($link as $k=> $v)
    {
        if ($v!= "")
            $linkclean.="$sep$k=".$v;
    }
    $linkclean=FN_RewriteLink("index.php?mod={$_FN['mod']}$linkclean");
    ;
    return $linkclean;
}

?>