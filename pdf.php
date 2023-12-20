<?php
/**
 * @package Flatnux
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
ob_start();
global $_FN;
require_once ("include/html2pdf4/html2pdf.class.php");
include "./include/flatnux.php";
//--------------------------  auto scripts  ----------------------------------->
include ("include/autoexec.php");
//--------------------------  auto scripts  -----------------------------------<
if(file_exists("modules/{$_FN['sectionvalues']['type']}/pdf.php"))
{
    include ("modules/{$_FN['sectionvalues']['type']}/pdf.php");
    die();
}
elseif(file_exists("sections/{$_FN['mod']}/pdf.php"))
{
    include ("sections/{$_FN['mod']}/pdf.php");
    die();
}
//---------------------------strip tags --------------------------------------->
$contents=FN_HtmlSection();
$contents=FNPDF_DropTag("iframe",$contents);
$contents=FNPDF_DropTag("script",$contents);
$contents=FNPDF_DropTag("fieldset",$contents);
$contents=FNPDF_DropTag("strike",$contents);
//---------------------------strip tags ---------------------------------------<
FN_HtmlToPdf($contents,str_replace(" ","_",$_FN['site_title'].".pdf"));

/**
 *
 * @param type $tag
 * @param type $contents
 * @return type 
 */
function FNPDF_DropTag($tag,$contents)
{
    $content_clean=$contents;
    while(1)
    {
        $content_clean=FNPDF_GetHtmlNode($tag,$contents);
        if($content_clean==$contents)
            break;
        $contents=str_replace($content_clean,"",$contents);
    }
    return $contents;
}

/**
 *
 * @param type $search
 * @param type $string
 * @param type $tagname
 * @return type 
 */
function FNPDF_GetHtmlNode($tagname,$string)
{
    $num_OpenTag=0;
    $tot_OpenTag=0;
    $tot_CloseTag=0;
    $pos1=strpos($string,"<$tagname");
    if($pos1===false)
    {
        return $string;
    }
    $tmpstring=$string;
    $poslen=0;
    for($pointer=$pos1; $tmpstring!==""; $pointer++)
    {
        $tmpstring=substr($string,$pointer);
        if(0!==preg_match('/^<\/'.$tagname.'/i',$tmpstring))
        {
            $tot_CloseTag++;
            $num_OpenTag--;
        }
        elseif(0!==preg_match('/^<'.$tagname.'/i',$tmpstring))
        {
            $num_OpenTag++;
            $tot_OpenTag++;
        }
        if($num_OpenTag==0)
            break;
        $poslen++;
        if($poslen>10000)
        {
            //die("error $num_OpenTag");
            return $string;
        }
    }
    return substr($string,$pos1,$poslen)."</"."$tagname>";
}

/**
 *
 * @param type $string
 * @param string $filename 
 */
function FN_HtmlToPdf($string,$filename="",$returnstring=false)
{
    global $_FN;
    if($filename=="")
    {
        $filename=time().'.pdf';
    }
    while(false!==ob_get_clean()
    );
    $html2pdf=new HTML2PDF('P','A4',"en",true,"UTF-8",array(0,0,0,0));
    $html2pdf->setDefaultFont('Arial');
    $string=FN_ConvertEncoding($string,$_FN['charset_page'],"UTF-8");
    $html2pdf->WriteHTML($string,isset($_GET["debug"]));
    if($returnstring)
        $html2pdf->Output($filename,"S");
    else
        $html2pdf->Output($filename,"D");
}

?>