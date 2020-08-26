<?php

/**
 * Utility to extract the strings to be translated
 *
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 1011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 *
 * USAGE:
 *
 * utility.php?lang=en (get fn_i18n in system files)
 * utility.php?lang=en&mod=login (get fn_i18n in login section)
 *
 */
global $_FN;
include 'include/flatnux.php';
header("Content-Type: text/html; charset={$_FN['charset_page']}");
$lang=FN_GetParam("lang",$_GET);
if ($lang=="")
    $lang="en";
$s=FN_GetParam("mod",$_GET);
$m=FN_GetParam("m",$_GET);

if ($m!=""&&file_exists("modules/$s/"))
{
    $s=$m;
    $dirs=FN_ListDir("modules/$s/",true,true);
    $_cms_strings=Local_LoadMessages("languages/it/lang.csv");
    //dprint_r($_cms_strings);
    $strings=Local_LoadMessages("modules/$s/languages/$lang/lang.csv",$_cms_strings);
    $dirs[]="modules/$s/";
    foreach($dirs as $dir)
    {
        if (FN_erg("^modules/",$dir))
        {
            $files=glob("$dir/*.php");

            foreach($files as $file)
            {
                $contents=file_get_contents($file);
                $out=false;
                preg_match_all('/FN_i18n\(\"(.*?)\"/is',$contents,$out);
                if (isset($out[1][0]))
                {
                    foreach($out[1] as $string_not_set)
                    {
                        if (!isset($strings[$string_not_set])&&!isset($_cms_strings[$string_not_set]))
                        {
                            $strings[$string_not_set]="";
                        }
                    }
                }
                preg_match_all('/FN_Translate\(\"(.*?)\"/is',$contents,$out);

                if (isset($out[1][0]))
                {
                    foreach($out[1] as $string_not_set)
                    {
                        if (!isset($strings[$string_not_set])&&!isset($_cms_strings[$string_not_set]))
                        {
                            $strings[$string_not_set]="";
                        }
                    }
                }
            }
        }
    }
}
elseif ($s!=""&&file_exists("sections/$s/"))
{

    $dirs=FN_ListDir("sections/$s/",true,true);

    $_cms_strings=Local_LoadMessages("languages/it/lang.csv");
    //dprint_r($_cms_strings);
    $strings=Local_LoadMessages("sections/$s/languages/$lang/lang.csv",$_cms_strings);
    //dprint_r("file: sections/$s/languages/$lang/lang.csv");
    $dirs[]="sections/$s/";
    foreach($dirs as $dir)
    {
        if (FN_erg("^sections/",$dir))
        {
            $files=glob("$dir/*.php");

            foreach($files as $file)
            {
                $contents=file_get_contents($file);
                $out=false;
                preg_match_all('/FN_i18n\(\"(.*?)\"/is',$contents,$out);
                if (isset($out[1][0]))
                {
                    foreach($out[1] as $string_not_set)
                    {
                        if (!isset($strings[$string_not_set])&&!isset($_cms_strings[$string_not_set]))
                        {
                            $strings[$string_not_set]="";
                        }
                    }
                }
                preg_match_all('/FN_Translate\(\"(.*?)\"/is',$contents,$out);
                //dprint_r("file:$file");
                if (isset($out[1][0]))
                {
                    //dprint_r($out[1]);
                    foreach($out[1] as $string_not_set)
                    {
                        if (!isset($strings[$string_not_set])&&!isset($_cms_strings[$string_not_set]))
                        {
                            $strings[$string_not_set]="";
                        }
                    }
                }
            }
        }
    }
}
else
{
    $dirs=FN_ListDir(".",true,true);
    $strings=Local_LoadMessages("languages/$lang/lang.csv");
    foreach($dirs as $dir)
    {
        if (!FN_erg("^modules/",$dir)&&!FN_erg("^sections/",$dir)&&!FN_erg("^blocks/",$dir)&&!FN_erg("^misc/",$dir))
        {
            $files=glob("$dir/*.php");
            foreach($files as $file)
            {
                $contents=file_get_contents($file);
                $out=false;
                preg_match_all('/FN_i18n\(\"(.*?)\"/is',$contents,$out);
                if (isset($out[1][0]))
                {
                    foreach($out[1] as $string_not_set)
                    {
                        if (!isset($strings[$string_not_set]))
                        {
                            $strings[$string_not_set]="";
                        }
                    }
                }
            }
        }
    }
}
$csv='"id","text"';
foreach($strings as $key=> $value)
{
    $key=str_replace('"','""',$key);
    $value=str_replace('"','""',$value);
    $csv.="\n\"{$key}\",\"{$value}\"";
}
dprint_r($csv);

/**
 *
 * @param string $filename
 * @return string
 */
function Local_LoadMessages($filename,$exclude=array())
{
    $sd=FN_GetParam("d",$_GET);


    $var="";
            if ($sd)
            {
                $var[$filename]="<h1>$filename</h1>";
            }
    if (!file_exists($filename))
        return $var;
    $first=true;
    $handle=fopen("$filename","r");

    while(($data=fgetcsv($handle,5000,","))!==false)
    {

        if ($first==true)
        {
            $first=false;
            continue;
        }

        if (isset($data[1])&&!isset($exclude[$data[0]]))
            $var[$data[0]]=$data[1];
    }
    fclose($handle);
    return $var;
}

?>