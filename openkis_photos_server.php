<?php

global $_FN;
$t=isset($_GET["t"]) ? $_GET["t"] : "";
$id=isset($_GET["id"]) ? $_GET["id"] : "";
$op=isset($_GET["op"]) ? $_GET["op"] : "";
$session=isset($_GET["session"]) ? $_GET["session"] : "";

if ($t== "ctl_artificials")
{
    $t="ctl_artificials";
    $mod="artificials";
    $t_photos="ctl_photos_artificials";
}
else
{
    $t="ctl_caves";
    $mod="caves";
    $t_photos="ctl_photos";
}
$_GET['mod']=$mod;
require_once "loadfinis.php";
$table_photos=FN_XMDBTable("$t_photos");

header('cache-control: no-cache,no-store,must-revalidate');
header('pragma: no-cache');
header('expires: 0');
$name="Nessuna cavità selezionata";
$session=uniqid("_");
if ($_FN['user']== "")
{
    echo "<h2>Per inserire nuove cavità devi prima eseguire l'accesso</h2>";
    echo FN_HtmlLoginForm();
    die("");
}

$ids=array();
$files_arr=array();

if ($id && $op== "")
{
    if ($t== "ctl_artificials")
    {
        $photolist=$table_photos->GetRecords(array("codeartificial"=>$id));
    }
    else
    {
        $photolist=$table_photos->GetRecords(array("codecave"=>$id));
    }

    //dprint_r($photolist);
    if (is_array($photolist))
        foreach($photolist as $recordvalues)
        {
            $url=$table_photos->getThumbPath($recordvalues,"photo");
            if ($url)
            {
                $files_arr[]=$url;
                $ids[]=$recordvalues['id'];
            }
        }
}
else
{
    switch($op)
    {
        default:
            $files_arr=array();
// Count total files
            if (isset($_FILES['files']['name']))
            {
                $countfiles=count($_FILES['files']['name']);

// Upload directory
                @mkdir("misc/uploads/");
                @mkdir("misc/uploads/$session");
                $upload_location="misc/uploads/$session/";

// To store uploaded files path
// Loop all files
                for($index=0; $index < $countfiles; $index++)
                {

                    // File name
                    $filename=$_FILES['files']['name'][$index];

                    // Get extension
                    $ext=pathinfo($filename,PATHINFO_EXTENSION);

                    // Valid image extension
                    $valid_ext=array("png","jpeg","jpg");

                    // Check extension
                    if (in_array($ext,$valid_ext))
                    {

                        // File path
                        $path=$upload_location.$filename;

                        // Upload file
                        if (move_uploaded_file($_FILES['files']['tmp_name'][$index],$path))
                        {
                            $files_arr[]=$path;
                        }
                    }
                }
            }

            break;
        case "cancel";
            $session=FN_GetParam("session",$_GET,"flat");
            $authorphoto1=FN_GetParam("authorphoto1",$_GET,"flat");
            $files=glob("misc/uploads/$session/*");
            $res=false;
            if ($session[0]== "_")
            {
                FN_RemoveDir("misc/uploads/$session");
            }
            die(json_encode(array("result"=>true)));
            break;
        case "confirm";
            $session=FN_GetParam("session",$_GET,"flat");
            $authorphoto1=FN_GetParam("authorphoto1",$_GET,"flat");
            $files=glob("misc/uploads/$session/*");
            $res=false;
            foreach($files as $file)
            {
                if (IsImage($file))
                {
                    $newvalues=array();
                    if ($t== "ctl_artificials")
                    {
                        $newvalues['codeartificial']=$id;
                    }
                    else
                    {
                        $newvalues['codecave']=$id;
                    }
                    $newvalues['name']=basename($file);
                    $newvalues['date']=FN_Now();
                    $newvalues['license']=5;
                    $newvalues['description']="foto inviata da ".$_FN['user'];

                    $newvalues['author']=$authorphoto1;
                    $newvalues['photo']=basename($file);
                    $_FILES['photo']['tmp_name']=realpath($file);
                    $_FILES['photo']['name']=$newvalues['photo'];
                    //dprint_r($newvalues);
                    $res=$table_photos->InsertRecord($newvalues);
                    if (!$res)
                    {
                        die(json_encode(array("result"=>false)));
                    }
                }
            }
            /*
              $olds = glob("misc/uploads/*");
              foreach($olds as $old)
              {
              if (is_dir($old))
              {
              FN_RemoveDir("$old");
              }
              }
             */
            if ($res && $session[0]== "_")
            {
                FN_RemoveDir("misc/uploads/$session");
            }
            echo json_encode(array("result"=>true));
            die;
            break;
    }
}

function IsImage($mediapath)
{
    if (@is_array(getimagesize($mediapath)))
    {
        $image=true;
    }
    else
    {
        $image=false;
    }
    return $image;
}

echo json_encode(array("session"=>$session,"files"=>$files_arr,"photos"=>$ids));
die;
?>