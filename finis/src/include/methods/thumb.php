<?php
global $_FN;
ob_start();
$usecache=$_FN['use_cache'];
$usecache=false;
$filename=FN_GetParam("f",$_GET,"flat");
$maxh=FN_GetParam("h",$_GET,"flat");
$maxw=FN_GetParam("w",$_GET,"flat");
$table=FN_GetParam("t",$_GET,"flat");
$id=FN_GetParam("i",$_GET,"flat");
$d=FN_GetParam("d",$_GET,"flat");
$field=FN_GetParam("c",$_GET,"flat");
$forceratio=true;
$exists=true;
$format=FN_GetParam("format",$_GET,"flat");
if ($format!= "jpg")
{
    $format="png";
}
//-----------------------------files in xmetadb---------------------------------->
if ($d== "")
    $d="fndatabase";
if ($table!= "" && $id!= "" && $field!= "")
{
    $tb=new XMETATable("$d",$table,$_FN['datadir']);
    $filename=$tb->get_file(array("{$tb->primarykey}"=>$id),"$field");
    $filename=str_replace($_FN['siteurl'],"",$filename);
}
//-----------------------------files in xmetadb----------------------------------<
//---------------fix max and min----------------------------------------------->
if ($maxh== "" && $maxw== "")
{
    $maxh=32;
    $maxw=32;
}
if ($maxh== "" && $maxw!= "")
{
    $maxh=$maxw;
}
if ($maxw== "" && $maxh!= "")
{
    $maxw=$maxh;
}
//---------------fix max and min-----------------------------------------------<
//---------------fix filename   ----------------------------------------------->
if ($filename== "" || !file_exists($filename))
{
    $filename="images/mime/image.png";
    $exists=false;
}
//---------------fix filename   -----------------------------------------------<
//-----------------------------cache------------------------------------------->
$thumbcachefile="{$_FN['datadir']}/_THUMBS/{$maxw}x{$maxh}_".md5($filename).".".filemtime($filename).".$format";
if ($forceratio)
    $thumbcachefile="{$_FN['datadir']}/_THUMBS/{$maxw}x{$maxh}_ratio_".md5($filename).".".filemtime($filename).".$format";

if ($usecache && $exists && file_exists("$thumbcachefile"))
{
    header("Location:".$thumbcachefile);
    die();
}
//-----------------------------cache-------------------------------------------<
//----------------------------read file---------------------------------------->
list($width,$height,$type,$attr)=getimagesize($filename);
if (!$width)
{
    $filename="images/mime/image.png";
    $exists=false;
    list($width,$height,$type,$attr)=getimagesize($filename);
}
if (function_exists("exif_read_data"))
{
    $exif=exif_read_data($filename);
    if (!empty($exif['Orientation']) && $exif['Orientation']== 6 || $exif['Orientation']== 8)
    {
        $tmp=$height;
        $height=$width;
        $width=$tmp;
    }
}
//----------------------------read file----------------------------------------<
//----------------------------new size ---------------------------------------->
$new_height=$height;
if ($maxw!= "" && $width>= $maxw)
{
    $new_width=$maxw;
    $new_height=$height * ($new_width / $width);
}
//se troppo alta
if ($maxh!= "" && $new_height>= $maxh)
{
    $new_height=$maxh;
    $new_width=$width * ($new_height / $height);
}
// se l' immagine e gia piccola
if ($maxw!= "" && $maxh!= "" && $width<= $maxw && $height<= $maxh)
{
    $new_width=$width;
    $new_height=$height;
}
//----------------------------new size ----------------------------------------<
//----------------------------load image resource------------------------------>
switch($type)
{
    case 1 :
        $source=ImageCreateFromGif($filename);
        break;
    case 2 :
        $source=ImageCreateFromJpeg($filename);
        break;
    case 3 :
        $source=ImageCreateFromPng($filename);
        break;
    default :
        $tmb=null;
        $size[0]=$size[1]=150;
        $source=ImageCreateTrueColor(150,150);
        $rosso=ImageColorAllocate($tmb,255,0,0);
        ImageString($tmb,5,10,10,"Not a valid",$rosso);
        ImageString($tmb,5,10,30,"GIF, JPEG or PNG",$rosso);
        ImageString($tmb,5,10,50,"image.",$rosso);
}
image_fix_orientation($source,$filename);
//----------------------------load image resource------------------------------<
//----------------------------create thumb resource---------------------------->
$thumb=imagecreatetruecolor($maxw,$maxh);
$thumb_h=$maxh;
$thumb_w=$maxw;
$white=imagecolorallocate($thumb,255,255,255);
$black=imagecolorallocate($thumb,0,0,0);
imagecolortransparent($thumb,$white);
imagefilledrectangle($thumb,0,0,$thumb_w,$thumb_h,$white);
//----------------------------create thumb resource----------------------------<
//-------------------------------   center image         ---------------------->
$sx=0;
$sy=0;
if ($thumb_h > $new_height)
{
    $sy=round(($thumb_h - $new_height) / 2);
}
if ($thumb_w > $new_width)
{
    $sx=round(($thumb_w - $new_width) / 2);
}
//-------------------------------   center image         ----------------------<
//---------------------------------resize-------------------------------------->
imagecopyresampled($thumb,$source,$sx,$sy,0,0,$new_width,$new_height,$width,$height);
//---------------------------------resize--------------------------------------<
// Output
$fname="imagejpeg";
if ($format== "png")
{
    $fname="imagepng";
}
if ($usecache)
{
    if (!file_exists($thumbcachefile))
    {
        if (!file_exists("{$_FN['datadir']}/_THUMBS/"))
            mkdir("{$_FN['datadir']}/_THUMBS/");
    }
    $fname($thumb,$thumbcachefile);
    imagedestroy($thumb);
    ob_get_clean();
    header("Location:".$thumbcachefile);
}
else
{
    while(false!== ob_get_clean()
    );
    if ($format== "png")
    {
        header("Content-type: image/png");
    }
    else
    {
        header("Content-type: image/jpeg");
    }
    $fname($thumb);
    imagedestroy($thumb);
}
/**
 * 
 * @param type $image
 * @param type $filename
 */
function image_fix_orientation(&$image,$filename)
{
    if (function_exists("exif_read_data"))
    {
        $exif=exif_read_data($filename);
        if (!empty($exif['Orientation']))
        {
            switch($exif['Orientation'])
            {
                default:
                    break;
                case 3:
                    $image=imagerotate($image,180,0);
                    break;

                case 6:
                    $image=imagerotate($image,-90,0);
                    break;

                case 8:
                    $image=imagerotate($image,90,0);
                    break;
            }
        }
    }
    else
    {
        
    }
}
