<?php

/**
 * @package Finis
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
ob_start();
$CONFIG=array();
$t=FN_GetParam("t",$_GET,"html");
if ($t== "")
{
    $t="security_code";
}
$session=FN_GetSessionValue("captcha");
if (!isset($session[$t]))
{
    $text="error";
}
else
{
    $text=$session[$t];
}
$CONFIG['font_id']=7;
$CONFIG['width']=120;
$CONFIG['height']=60;

/**
 *
 * @param type $rgb
 * @return type 
 */
function rgb_grayscale($rgb)
{
    $color=array();
    $color['r']=0.299 * $rgb['r'] + 0.587 * $rgb['g'] + 0.114 * $rgb['b'];
    $color['g']=0.299 * $rgb['r'] + 0.587 * $rgb['g'] + 0.114 * $rgb['b'];
    $color['b']=0.299 * $rgb['r'] + 0.587 * $rgb['g'] + 0.114 * $rgb['b'];
    return $color;
}

/**
 *
 * @param array $rgb
 * @return type 
 */
function rgb_complementary($rgb)
{
    $color=array();
    $color['r']=255 - $rgb['r'];
    $color['g']=255 - $rgb['g'];
    $color['b']=255 - $rgb['b'];
    return $color;
}

/**
 *
 * @param type $min
 * @param type $max
 * @return type 
 */
function rgb_rand($min=0,$max=255)
{
    $color=array();
    $color['r']=rand($min,$max);
    $color['g']=rand($min,$max);
    $color['b']=rand($min,$max);
    return $color;
}

function rgbcolor($r,$g,$b)
{
    $color=array();
    $color['r']=$r;
    $color['g']=$g;
    $color['b']=$b;
    return $color;
}

/**
 *
 * @param type $r
 * @param type $g
 * @param type $b
 * @return type 
 */
function rgb_create($r=0,$g=0,$b=0)
{
    $color=array();
    $color['r']=$r;
    $color['g']=$g;
    $color['b']=$b;
    return $color;
}

/**
 *
 * @param type $lhs
 * @param type $rhs
 * @return type 
 */
function rgb_merge($lhs,$rhs)
{
    $color=array();
    $color['r']=( $lhs['r'] + $rhs['r'] ) >> 1;
    $color['g']=( $lhs['g'] + $rhs['g'] ) >> 1;
    $color['b']=( $lhs['b'] + $rhs['b'] ) >> 1;
    return $color;
}

srand((double)microtime() * 1000000);
// Creates a simple image
$image=imagecreate($CONFIG['width'],$CONFIG['height']);
// Create random colors
$rgb=array();
$rgb['background']=rgbcolor(233,236,239);
$rgb['foreground']=rgbcolor(126,126,200);
if ($rgb['foreground']['r'] > 127)
{
    $inicio=-127;
    $rgb['foreground']=rgb_merge($rgb['foreground'],rgb_create(255,255,255));
    $rgb['shadow']=rgb_merge(rgb_complementary($rgb['foreground']),rgb_create(0,0,0));
}
else
{
    $inicio=0;
    $rgb['foreground']=rgb_merge($rgb['foreground'],rgb_create(0,0,0));
    $rgb['shadow']=rgb_merge(rgb_complementary($rgb['foreground']),rgb_create(255,255,255));
}
// Allocate color resources
$color=array();
foreach($rgb as $name=> $value)
{
    $color[$name]=imagecolorallocate($image,$value['r'],$value['g'],$value['b']);
} // foreach
// Draw background
imagefilledrectangle($image,0,0,120,30,$color['background']);
// Write some random text on background
for($i=0; $i < rand(5,9); $i++)
{
    $x=rand(0,$CONFIG['width']);
    $y=rand(0,$CONFIG['height']);
    $f=rand(0,5);
    $c=rgb_grayscale(rgb_rand(127 - $inicio,254 - $inicio));
    $color[$i]=imagecolorallocate($image,$c['r'],$c['g'],$c['b']);
    imagestring($image,$f,$x,$y,"* * *",$color[$i]);
}
// Center the real captcha text
$x=( $CONFIG['width'] - 15 - ( ImageFontWidth($CONFIG['font_id']) * strlen($text) ) ) >> 1;
$y=( $CONFIG['height'] - ImageFontHeight($CONFIG['font_id']) ) >> 1;

putenv('GDFONTPATH='.realpath('./images'));
$font='captcha.ttf';

// Add some shadow to the text
imagettftext($image,22,10,0,$y + 22 + 1,$color['foreground'],$font,$text);
imagettftext($image,22,10,1,$y + 22,$color['foreground'],$font,$text);
while(false!== ob_get_clean()
);
// Returns the image
header('Content-type: image/png');
header("Cache-Control: no-cache");
header("Pragma: no-cache");
imagepng($image);
// Free resources
foreach($color as $name=> $value)
{
    imagecolordeallocate($image,$value);
}
imagedestroy($image);



