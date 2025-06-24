<?php
ob_start();
global $_FN;
require_once "loadfinis.php";
require_once "{$_FN['src_finis']}/modules/dbview/FNDBVIEW.php";
require_once "extra/Shapefile/ShapefileAutoloader.php";
// Register autoloader
Shapefile\ShapefileAutoloader::register();

// Import classes
use Shapefile\Shapefile;
use Shapefile\ShapefileException;
use Shapefile\ShapefileWriter;
use Shapefile\Geometry\Point;

//load cms strings
FN_LoadMessagesFolder("extra/openkis");

$exclude = FN_GetParam("exclude", $_GET, "flat");
$codes = FN_GetParam("filter_code", $_GET, "flat");
$big_icons = !empty($_GET['big_icons']);
$mod = $_FN['mod'];
if ($mod == "")
{
    $mod = "caves";
}
foreach ($_REQUEST as $k => $v)
{
    $params[$k] = $v;
}
if (!file_exists("sections/$mod"))
{
    die();
}
$config = FN_LoadConfig("modules/dbview/config.php", $mod);
$dbview = new FNDBVIEW($config);
$tablename = $config['tables'];
$table = FN_XMDBTable($tablename);
$fields_to_read = explode(",", "code,latitude,longitude,elevation,name,synonyms,depth_total,depth_negative,depth_positive,lenght_total,meteorology,fauna,hydrology,closed,photo1");
foreach ($fields_to_read as $field)
{
    if (isset($table->fields[$field]))
    {
        $fields[] = $field;
    }
}

$params['fields'] = implode(",", $fields);
$params['filter_code'] = $codes;
$idresult = false;
openkis_UpdateCoords("$mod");

$data = $_FN['sitename'] . "-" . FN_now();
$data = str_replace(" ", "_", str_replace(":", "-", $data));
$data = str_replace("\\", "", str_replace("/", "", $data));
$filename = "$data-$mod";

$results = $dbview->GetResults(false, $params, $idresult);



$tplvars['sourceurl'] = $_FN['siteurl'];
$tplvars['name'] = $tablename;
$tplvars['items'] = array();

$cx = 0;
foreach ($results as $item)
{
    $ItemData = array();
    if (!empty($item['latitude']) && !empty($item['longitude']))
    {
        $ItemData = $item;
        $lat_ = openkis_floatfrmt($item['latitude']);
        $lon_ = openkis_floatfrmt($item['longitude']);
        $ItemData['lat'] = $lat_;
        $ItemData['lon'] = $lon_;
        $elevation = isset($item['elevation']) ? $item['elevation'] : "";
        $lenght_total = isset($item['lenght_total']) ? $item['lenght_total'] : "";
        $depth_total = isset($item['depth_total']) ? $item['depth_total'] : "";
        $elevation_txt = isset($item['elevation']) ? "Q." . $item['elevation'] : "";
        $lenght_total_txt = isset($item['lenght_total']) ? "SV." . $item['lenght_total'] : "";
        $depth_total_txt = isset($item['depth_total']) ? "P." . $item['depth_total'] : "";
        //-----dimensione icona------------------------------------------------>

        $size = 0.3;
        $maxdis = $depth_total;
        if ($maxdis <= 2 && $lenght_total <= 8)
            $size = 0.2;
        if ($maxdis >= 10 || $lenght_total >= 20)
            $size = 0.25;
        if ($maxdis >= 20 || $lenght_total >= 30)
            $size = 0.26;
        if ($maxdis >= 30 || $lenght_total >= 40)
            $size = 0.27;
        if ($maxdis >= 40 || $lenght_total >= 60)
            $size = 0.28;
        if ($maxdis >= 50 || $lenght_total >= 80)
            $size = 0.29;
        if ($maxdis >= 100 || $lenght_total >= 500)
            $size = 0.3;
        if ($maxdis >= 200 || $lenght_total >= 1000)
            $size = 0.35;
        if ($maxdis >= 200 || $lenght_total >= 1500)
            $size = 0.4;
        if ($maxdis == 0 && $lenght_total == 0)
        {
            $size = 0.2;
        }
        //-----dimensione icona------------------------------------------------<
        if ($big_icons)
        {
            $size = $size * 3;
        }
        $size = str_replace(",", ".", $size);
        $url = FN_RewriteLink("index.php?mod=$mod&op=view&id={$item['id']}", "&", true);
        //$description="{$item['hydrology']}";
        $description = "";
        $description_txt = "";
        if ($item['photo1'])
        {
            $img = $table->getThumbPath($item, "photo1");
            if (file_exists($img))
            {
                $img = $_FN['siteurl'] . $img;
            }
            $description .= "<img style=\"height:100px;\" src=\"$img\" /><br />";
        }
        $description .= "$elevation_txt $lenght_total_txt $depth_total_txt<br />";
        $description_txt = "$elevation_txt $lenght_total_txt $depth_total_txt";
        $description .= "<div class=\"text-right\"><a  onclick=\"try{window.open(this.href,'r','scrollbars=yes').focus();return false;}catch(e){return true;}\" target=\"_blank\"  href=\"$url\" ><span class=\"glyphicon glyphicon-eye-open\"></span> <b>" . FN_Translate("open") . "</b></a> <a target=\"_blank\" href='https://www.google.it/maps/dir//$lat_,$lon_/@$lat_,$lon_,18z' ><span class=\"glyphicon glyphicon-road\"></span> <b>" . FN_Translate("go to") . "</b></a></div>";
        $description = str_replace("\t", " ", $description);
        $description_txt = str_replace("\t", " ", $description_txt);


        $description = FN_FixEncoding("$description");
        $ItemData['abstract'] = ($description);
        $ItemData['abstracttext'] = strip_tags($description_txt);
        $title = htmlspecialchars($item['code'] . "-" . $item['name']);

        $siteurl = str_replace("https://", "//", $_FN['siteurl']);
        $siteurl = str_replace("http://", "//", $siteurl);
        $icon = $siteurl . openkis_GetIcon($item, $mod);
        if ($elevation == "")
        {
            $elevation = 0;
        }
        $ItemData['title'] = htmlentities($title, ENT_NOQUOTES, "UTF-8");
        $ItemData['title'] = htmlentities($ItemData['title'], ENT_NOQUOTES, "UTF-8");
        $ItemData['abstract'] = "$description";
        $ItemData['size'] = "$size";
        $ItemData['icon'] = "$icon";
        $ItemData['elevation'] = $elevation;
        $ItemData['sourceurl'] = $tplvars['sourceurl'];
        $tplvars['items'][$cx] = $ItemData;
        $cx++;
    }
}

//--------------------make shape file ----------------------------------------->
$filepath = "{$_FN['datadir']}/tmp/$filename";
try
{
    // Open Shapefile
    @mkdir("{$_FN['datadir']}/tmp");
    @mkdir("$filepath");
    $Shapefile = new ShapefileWriter([
        Shapefile::FILE_SHP => fopen("$filepath/$filename.shp", 'c+b'),
        Shapefile::FILE_SHX => fopen("$filepath/$filename.shx", 'c+b'),
        Shapefile::FILE_DBF => fopen("$filepath/$filename.dbf", 'c+b'),
            ],
            array(
        Shapefile::OPTION_SUPPRESS_Z => false
            )
    );
    // Set shape type
    $Shapefile->setShapeType(Shapefile::SHAPE_TYPE_POINTZ);
    $Shapefile->setCharset("UTF-8");
    // Create field structure
    $Shapefile->addNumericField('id', 10);
    $Shapefile->addFloatField('lat');
    $Shapefile->addFloatField('lon');
    $Shapefile->addFloatField('elevation');
    $Shapefile->addFloatField('lenght');
    $Shapefile->addFloatField('depth');
    $Shapefile->addCharField('code', 25);
    $Shapefile->addCharField('name', 128);
    $Shapefile->addCharField('abstract', 128);

    if (isset($item['areas']))
        $Shapefile->addCharField('areas', 128);



    // Write some records (let's pretend we have an array of coordinates)
    foreach ($tplvars['items'] as $i => $coords)
    {
        //dprint_r($coords);
        // Create a Point Geometry
        $Point = new Point($coords['lon'], $coords['lat'], (float) $coords['elevation']);
        // Set its data
        $Point->setData('id', $i);
        $Point->setData('lat', floatval($coords['lat']));
        $Point->setData('lon', floatval($coords['lon']));
        
        $Point->setData('elevation', floatval($coords['elevation']));
        $Point->setData('depth', floatval($coords['depth_total']));
        $Point->setData('lenght', floatval($coords['lenght_total']));
        $Point->setData('code', "{$coords['code']}");
        $Point->setData('name', "{$coords['name']}");
        $Point->setData('abstract', "{$coords['abstracttext']}");
        if (isset($item['areas']))
            $Point->setData('areas', "{$coords['areas']}");
        // Write the record to the Shapefile
        $Shapefile->writeRecord($Point);
    }

    // Finalize and close files to use them
    $Shapefile = null;

    //make zip file------------------------------------------------------------>
    $zip = new ZipArchive;
    $names = array();
    if ($zip->open("$filepath.zip", ZipArchive::CREATE) === TRUE)
    {
        $zip->addFile("$filepath/$filename.shp", "$filename.shp");
        $zip->addFile("$filepath/$filename.shx", "$filename.shx");
        $zip->addFile("$filepath/$filename.dbf", "$filename.dbf");
        $zip->close();
        $contents = file_get_contents("$filepath.zip");
        @unlink("$filepath/$filename.shp");
        @unlink("$filepath/$filename.shx");
        @unlink("$filepath/$filename.dbf");
        @rmdir("$filepath");
        @unlink("$filepath.zip");
        FN_SaveFile($contents, "$filename.zip");
    }
    //make zip file------------------------------------------------------------<
} catch (ShapefileException $e)
{
    // Print detailed error information
    echo "Error Type: " . $e->getErrorType()
    . "\nMessage: " . $e->getMessage()
    . "\nDetails: " . $e->getDetails();
    die();
}
//--------------------make shape file -----------------------------------------<
?>