<?php
include_once __DIR__ . "/xmetadb/XMETATable.php";

/**
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2024
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 * @package xmetadb
 *
 */
//-----PARSER XML -----
// TODO:
// LA PRIMARYKEY DEVE ESSERE SEMPRE IL PRIMO CAMPO DEL DESCRITTORE
@ini_set("memory_limit", "512M");
define("_MAX_FILE_ACCESS_ATTEMPTS", "1000");
define("_MAX_FILES_PER_FOLDER", "10000");
define("_MAX_LOCK_TIME", "30"); // seconds

function removePhpTags($inputString)
{
    // Define the termination string
    $terminationString = '<?php exit(0);?>';
    // Find the position of the termination string
    $position = strpos($inputString, $terminationString);

    // If the termination string is found
    if ($position !== false)
    {
        // Calculate the starting position of the remaining content
        $startPosition = $position + strlen($terminationString);

        // Return the substring starting after the termination string
        return substr($inputString, $startPosition);
    }

    // If the termination string is not found, return the original string
    return $inputString;
}

function xmetadb_xml2array($data, $elem, $fields = false)
{
    //eliminazione dei commenti
    if (!isset($data[2]) && $data[2] != "x")
    {
        return json_decode(removePhpTags($data));
    }
    $data = xmetadb_removexmlcomments($data);
    //visualizza solo determinati campi
    if (is_array($fields))
    {
        $fields = implode("|", $fields);
    }
    $out = "";
    $ret = null;
    if (preg_match("/<$elem>.*<$elem>[^<]+<\/$elem>/s", $data)) //se il nome del nodo contiene un elemento con lo stesso nome
    {
        preg_match_all("#<$elem>(.*?<$elem>.*?</$elem>.*?)</$elem>#s", $data, $out); //CONTIENE ALL'INTERNO UN NODO CON LO STESSO NOME
    }
    else
    {
        preg_match_all("#<$elem>.*?</$elem>#s", $data, $out); //OK
    }
    if (is_array($out[0]))
        foreach ($out[0] as $innerxml)
        {
            //----------metodo 0 ------------------------
            for ($oi = 0; $oi < 1; $oi++)
            {
                $tmp2 = $t1 = null;
                preg_match_all('/<(' . $fields . '[^\/]*?)>([^<]*)<\/\1>/s', $innerxml, $t1);
                foreach ($t1[1] as $k => $tt)
                {
                    if ($t1[2][$k] != null)
                        $tmp2[$tt] = xmldec($t1[2][$k]);
                    else
                        $tmp2[$tt] = "";
                }
            }
            if ($tmp2 != null)
            {
                $ret[] = ($tmp2);
            }
        }
    return $ret;
}


/**
 * xmetadb_readDatabase
 * legge un file xml e restituisce un array
 * <db>
 * <elem>
 * <pippo>1</pippo>
 * <pluto>1</pluto>
 * </elem>
 * <elem>
 * <pippo>2</pippo>
 * <pluto>2</pluto>
 * </elem>
 * </db>
 *
 * xmetadb_readDatabase($filename,"elem")
 * ritorna:
 *
 * $ret[0]['pippo']=1
 * $ret[0]['pluto']=1
 * $ret[1]['pippo']=2
 * $ret[1]['pluto']=2
 *
 * oppure null se non e' stato possibile leggere il file
 *
 * @todo Da risolvere il problema che avviene
 * nel caso un campo abbia lo steso nome della tebella !!!!
 *
 *
 * */
function xmetadb_readDatabase($filename, $elem, $fields = false, $usecache = true)
{
    if (!file_exists($filename))
        return false;
    $_fields = "_" . $fields;
    static $cache = array();
    static $lastmod = array();
    $filename = realpath($filename);
    if (!isset($lastmod[$filename]) || $lastmod[$filename] != filectime($filename) . filesize($filename))
    {
        $lastmod[$filename] = filectime($filename) . filesize($filename);
        $usecache = false;
    }
    if (is_dir($filename))
    {
        $usecache = false;
    }
    if ($usecache === false)
    {
        if (isset($cache[$filename][$_fields][$elem]))
        {
            unset($cache[$filename][$_fields][$elem]);
        }
    }
    else
    {
        //dprint_r("cache $filename");
    }
    if ($usecache === true && isset($cache[$filename][$_fields][$elem]))
    {
        return $cache[$filename][$_fields][$elem];
    }
    $tmp = array();
    // --- gestione xml in più files --------->
    if (is_dir($filename))
    {
        $data = null;
        $handle = opendir($filename);
        while (false !== ($file = readdir($handle)))
        {
            $tmp2 = null;
            if (preg_match('/.php$/is', $file))
                $tmp2 = xmetadb_readDatabase("$filename/$file", $elem, $fields, $usecache);
            if ($tmp2 != null)
                foreach ($tmp2 as $t)
                    $tmp[] = $t;
        }
        closedir($handle);
        $cache[$filename][$_fields][$elem] = $tmp;
        return $tmp;
    }
    //<--------- gestione xml in piu' files ---
    //tenta di accedere al file
    for ($i = 0; $i < _MAX_FILE_ACCESS_ATTEMPTS; $i++)
    {
        $data = file_get_contents($filename);
        // funziona ma sarebbe da verificare la chiusura di </database>
        if ("" != $data)
        {
            break;
        }
    }
    //da xml ad array....
    $ret = xmetadb_xml2array($data, $elem, $fields); //null if data = ""
    //echo "fname=$filename";
    $cache[$filename][$_fields][$elem] = $ret;
    return $ret;
}

/**
 * xmlenc
 *
 * codifica i dati per inserirli tra i tag xml
 * @param string $str
 * @return stringa codificata
 */
function xmlenc($str, $charset = "ISO-8859-1")
{
    //return htmlentities ( $str, ENT_QUOTES, "ISO-8859-1" );
    $str = str_replace("&", "&amp;", $str);
    $str = str_replace("<", "&lt;", $str);
    $str = str_replace(">", "&gt;", $str);
    return $str;
}

/**
 * xmldec
 *
 * decodifica i dati inseriti tra i tag xml
 * @param string $str
 * @return stringa codificata
 */
function xmldec($str, $charset = "ISO-8859-1")
{
    if (!is_string($str))
        return "";
    //return html_entity_decode($str, ENT_QUOTES, $charset);
    $str = str_replace("&gt;", ">", $str);
    $str = str_replace("&lt;", "<", $str);
    $str = str_replace("&amp;", "&", $str);
    return $str;
}

/**
 * xmetadb_create_thumb
 * Crea l' anteprima di un file
 * uso questa funzione per crearmi le anteprime per i campi di tipo immagine
 * occorrono le librerie GD
 * @param string $filename nome del file
 * @param int $max dimensione massima anteprima
 */
function xmetadb_create_thumb($filename, $max, $max_h = "", $max_w = "")
{
    if (!$filename)
        return;
    if ($max_h == "")
        $max_h = $max;
    if ($max_w == "")
        $max_w = $max;
    if (!function_exists("getimagesize"))
    {
        echo "<br />" . _FNNOGDINSTALL;
        return;
    }
    $new_height = $new_width = 0;
    if (!file_exists($filename))
    {
        echo "non esiste";
        return;
    }
    if (!getimagesize($filename))
    {
        echo "$filename is not image ";
        return;
    }
    list($width, $height, $type, $attr) = getimagesize($filename);
    if (function_exists("exif_read_data"))
    {
        $exif = @exif_read_data($filename);
        if (!empty($exif['Orientation']) && ($exif['Orientation'] == 6 || $exif['Orientation'] == 8))
        {
            $tmp = $height;
            $height = $width;
            $width = $tmp;
        }
    }

    $path = dirname($filename) . "/thumbs";
    $file_thumb = $path . "/" . basename($filename);
    if (!file_exists($path))
    {
        mkdir($path);
    }
    if (!file_exists($path))
    {
        echo "error make dir $path";
        return false;
    }
    if (!is_dir($path))
    {
        echo "<br />$path not exists";
    }
    $new_height = $height;
    $new_width = $width;
    if ($width >= $max_w)
    {
        $new_width = $max_w;
        $new_height = intval($height * ($new_width / $width));
    }
    //se troppo alta
    if ($new_height >= $max_h)
    {
        $new_height = $max_h;
        $new_width = intval($width * ($new_height / $height));
    }
    // se l' immagine e gia piccola
    if ($width <= $max_w && $height <= $max_h)
    {
        $new_width = $width;
        $new_height = $height;
        //return;
    }

    //die("h=$new_height w=$new_width");
    // Load
    $thumb = imagecreatetruecolor($new_width, $new_height);
    $white = imagecolorallocate($thumb, 255, 255, 255);
    $size = getimagesize($filename);
    //	dprint_r(IMAGETYPE_WBMP);
    try
    {
        switch ($size[2])
        {
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($filename);
                break;
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($filename);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($filename);
                break;
            case IMAGETYPE_WBMP:
                $source = imagecreatefromwbmp($filename);
                break;
            case IMG_XPM:
                $source = imagecreatefromxpm($filename);
                break;
            case 6:
                $source = xmetadb_ImageCreateFromBMP($filename);
                break;
            default:
                // unknown file format
                $source = imagecreatetruecolor(300, 300);
                $color = imagecolorallocate($source, 255, 255, 255);
                imagefill($source, 0, 0, $color);
                break;
        }
    } catch (Exception $e)
    {
        $source = false;
    }

    if (!$source)
    {
        return;
    }
    xmetadb_image_fix_orientation($source, $filename);
    // Resize
    imagefilledrectangle($thumb, 0, 0, $width, $width, $white);
    imagecopyresampled($thumb, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    // Output
    $file_to_open = $file_thumb;
    //forzo estensione jpg
    imagejpeg($thumb, $file_to_open . ".jpg");
}

/**
 *
 * @param string $filename
 * @return resource
 */
function xmetadb_ImageCreateFromBMP($filename)
{
    //Ouverture du fichier en mode binaire
    if (!$f1 = fopen($filename, "rb"))
        return FALSE;
    $FILE = unpack("vfile_type/Vfile_size/Vreserved/Vbitmap_offset", fread($f1, 14));
    if ($FILE['file_type'] != 19778)
        return FALSE;
    $BMP = unpack('Vheader_size/Vwidth/Vheight/vplanes/vbits_per_pixel' . '/Vcompression/Vsize_bitmap/Vhoriz_resolution' . '/Vvert_resolution/Vcolors_used/Vcolors_important', fread($f1, 40));
    $BMP['colors'] = pow(2, $BMP['bits_per_pixel']);
    if ($BMP['size_bitmap'] == 0)
        $BMP['size_bitmap'] = $FILE['file_size'] - $FILE['bitmap_offset'];
    $BMP['bytes_per_pixel'] = $BMP['bits_per_pixel'] / 8;
    $BMP['bytes_per_pixel2'] = ceil($BMP['bytes_per_pixel']);
    $BMP['decal'] = ($BMP['width'] * $BMP['bytes_per_pixel'] / 4);
    $BMP['decal'] -= floor($BMP['width'] * $BMP['bytes_per_pixel'] / 4);
    $BMP['decal'] = 4 - (4 * $BMP['decal']);
    if ($BMP['decal'] == 4)
        $BMP['decal'] = 0;
    $PALETTE = array();
    if ($BMP['colors'] < 16777216)
    {
        $PALETTE = unpack('V' . $BMP['colors'], fread($f1, $BMP['colors'] * 4));
    }
    $IMG = fread($f1, $BMP['size_bitmap']);
    $VIDE = chr(0);
    $res = imagecreatetruecolor($BMP['width'], $BMP['height']);
    $P = 0;
    $Y = $BMP['height'] - 1;
    while ($Y >= 0)
    {
        $X = 0;
        while ($X < $BMP['width'])
        {
            if ($BMP['bits_per_pixel'] == 24)
                $COLOR = unpack("V", substr($IMG, $P, 3) . $VIDE);
            elseif ($BMP['bits_per_pixel'] == 16)
            {
                $COLOR = unpack("n", substr($IMG, $P, 2));
                $COLOR[1] = $PALETTE[$COLOR[1] + 1];
            }
            elseif ($BMP['bits_per_pixel'] == 8)
            {
                $COLOR = unpack("n", $VIDE . substr($IMG, $P, 1));
                $COLOR[1] = $PALETTE[$COLOR[1] + 1];
            }
            elseif ($BMP['bits_per_pixel'] == 4)
            {
                $COLOR = unpack("n", $VIDE . substr($IMG, floor($P), 1));
                if (($P * 2) % 2 == 0)
                    $COLOR[1] = ($COLOR[1] >> 4);
                else
                    $COLOR[1] = ($COLOR[1] & 0x0F);
                $COLOR[1] = $PALETTE[$COLOR[1] + 1];
            }
            elseif ($BMP['bits_per_pixel'] == 1)
            {
                $COLOR = unpack("n", $VIDE . substr($IMG, floor($P), 1));
                if (($P * 8) % 8 == 0)
                    $COLOR[1] = $COLOR[1] >> 7;
                elseif (($P * 8) % 8 == 1)
                    $COLOR[1] = ($COLOR[1] & 0x40) >> 6;
                elseif (($P * 8) % 8 == 2)
                    $COLOR[1] = ($COLOR[1] & 0x20) >> 5;
                elseif (($P * 8) % 8 == 3)
                    $COLOR[1] = ($COLOR[1] & 0x10) >> 4;
                elseif (($P * 8) % 8 == 4)
                    $COLOR[1] = ($COLOR[1] & 0x8) >> 3;
                elseif (($P * 8) % 8 == 5)
                    $COLOR[1] = ($COLOR[1] & 0x4) >> 2;
                elseif (($P * 8) % 8 == 6)
                    $COLOR[1] = ($COLOR[1] & 0x2) >> 1;
                elseif (($P * 8) % 8 == 7)
                    $COLOR[1] = ($COLOR[1] & 0x1);
                $COLOR[1] = $PALETTE[$COLOR[1] + 1];
            }
            else
                return FALSE;
            imagesetpixel($res, $X, $Y, $COLOR[1]);
            $X++;
            $P += $BMP['bytes_per_pixel'];
        }
        $Y--;
        $P += $BMP['decal'];
    }
    fclose($f1);
    return $res;
}

/**
 * xmetadb_removexmlcomments
 * rimuove i commenti da un file xml
 *
 * @param string $data
 * @return string xml privo di commenti
 *
 */
function xmetadb_removexmlcomments($data)
{
    $data = preg_replace("/<!--(.*?)-->/ms", "", $data);
    $data = preg_replace("/<\\?(.*?)\\?>/", "", $data);
    return $data;
}

//-------------------------FUNZIONI DI CREAZIONE/MODIFICA DATABASE----------------
/**
 * XMETATable::createMetadbTable
 *
 * crea una nuova tabella xml
 * @param string nome database
 * @param string nome tabella
 * @param array campi
 * @param string path dei databases
 * @param misc $singlefilename se su un solo file specificarne il nome, se su database
 * mettere la connessione di tipo array(host=>'' user=>'' password=>'')
 *
 *
 * -- ESEMPIO : --
 * $fields[0]['name']="id";
 * $fields[0]['primarykey']=1;
 * $fields[0]['defaultvalue']=null;
 * $fields[0]['type']="varchar";
 * $fields[1]['name']="test";
 * $fields[1]['primarykey']=0;
 * $fields[1]['defaultvalue']="pippo";
 * $fields[1]['type']="varchar";
 * XMETATable::createMetadbTable("plugins","test",$fields,"misc");
 * */
function createxmltable($databasename, $tablename, $fields, $path = ".", $singlefilename = false)
{
    return XMETATable::createMetadbTable($databasename, $tablename, $fields, $path, $singlefilename);
}

/**
 * XMETATable::createMetadbDatabase
 * crea un database
 *
 * @param string $databasename
 * @param string $path
 * @return false se il databare e'stato creato oppure una stringa che contiene l' errore
 */
function createxmldatabase($databasename, $path = ".")
{
    return XMETATable::createMetadbDatabase($databasename, $path);
}

/**
 * XMETATable::meteDatabaseExists
 * verifica se un database esiste
 *
 * @param string $databasename
 * @param string $path
 */
function xmldatabaseexists($databasename, $path = ".", $conn = false)
{
    return XMETATable::meteDatabaseExists($databasename, $path, $conn);
}

function xmltableexists($databasename, $tablename, $path = ".")
{
    return XMETATable::metaTableExists($databasename, $tablename, $path);
}

/**
 * addfield
 * add field in table
 *
 * @param string $databasename
 * @param string $tablename
 * @param array $field
 * @param string $path
 * @param bool $force
 *
 */
function addxmltablefield($databasename, $tablename, $field, $path = ".", $force = true)
{
    if (!isset($field['name']))
        return null;
    if (is_array($tablename))
        return null;
    $newvalues = array();
    $values = $field;
    $pvalue = $field['name'];
    $pkey = "name";
    $old = "$path/$databasename/$tablename.php";
    if (!file_exists($old))
        return null;
    $readok = false;
    for ($i = 0; $i < _MAX_FILE_ACCESS_ATTEMPTS; $i++)
    {
        $oldfilestring = file_get_contents($old);
        if (strpos($oldfilestring, "</tables>") !== false)
        {
            $readok = true;
            break;
        }
    }
    if (!$readok)
    {
        die("error update");
    }
    $oldfilestring = xmetadb_removexmlcomments($oldfilestring);
    $oldvalues = $newvalues = getxmltablefield($databasename, $tablename, $field['name'], $path);
    foreach ($values as $key => $value)
    {

        $newvalues[$key] = $value;
    }
    //compongo il nuovo xml per il record da aggiornare
    $strnew = "<field>";
    foreach ($newvalues as $key => $value)
    {
        $strnew .= "\n\t\t<$key>" . xmlenc($value) . "</$key>";
    }
    $strnew .= "\n\t</field>";

    if ($oldvalues)
    {
        $pvalue = xmlenc($pvalue);
        $pvalue = xmetadb_encode_preg($pvalue);
        $strnew = str_replace('$', '\\$', $strnew);
        $newfilestring = preg_replace('/<field>([^(field)]*)<' . $pkey . '>' . $pvalue . '<\/' . $pkey . '>(.*?)<\/field>/s', $strnew, $oldfilestring);
        if (!is_writable($old))
        {
            echo ("$old is readonly,I can't update");
            return ("$old is readonly,I can't update");
        }
        if ($oldfilestring != $newfilestring && $force)
        {
            $handle = fopen($old, "w");
            fwrite($handle, $newfilestring);
            xmetadb_readDatabase($old, 'field', false, false); //aggiorna la cache
        }
        return $newvalues;
    }
    else // new field
    {
        for ($i = 0; $i < _MAX_FILE_ACCESS_ATTEMPTS; $i++)
        {
            $oldfilestring = file_get_contents("$path/$databasename/$tablename.php");
            if (strpos($oldfilestring, "</tables>") !== false)
            {
                $readok = true;
                break;
            }
        }
        if (!$readok)
        {
            return "error insert field";
        }
        $strnew = xmetadb_encode_preg_replace2nd($strnew);
        $newfilestring = preg_replace('/<\/tables>$/s', xmetadb_encode_preg_replace2nd($strnew) . "\n</tables>", trim($oldfilestring)) . "\n";
        $handle = fopen("$path/$databasename/$tablename.php", "w");
        fwrite($handle, $newfilestring);
        fclose($handle);
        xmetadb_readDatabase($old, 'field', false, false); //aggiorna la cache
        return $newvalues;
    }
}

/**
 * getxmltablefield
 * ritorna tutte le proprieta' di un campo di una tabella xml
 *
 * @param string databasename
 * @param string tablename
 * @param string fieldname
 * @param string path
 */
function getxmltablefield($databasename, $tablename, $fieldname, $path = ".")
{
    if (!file_exists("$path/$databasename/$tablename.php"))
        return null;
    $rows = xmetadb_readDatabase("$path/$databasename/$tablename.php", "field");
    foreach ($rows as $row)
    {
        if ($row['name'] == $fieldname)
        {
            return $row;
        }
    }
    return null;
}

/**
 * Elimina ricorsivamente una cartella
 *
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @param $dirtodelete cartella da eliminare
 *
 * */
function xmetadb_remove_dir_rec($dirtodelete)
{
    if (strpos($dirtodelete, "../") !== false)
        die("xmetadberror:xmetadb_remove_dir_rec");
    if (false != ($objs = glob($dirtodelete . "/.*")))
    {
        foreach ($objs as $obj)
        {
            if (!is_dir($obj))
                unlink($obj);
            else
            {
                if (basename($obj) != "." && basename($obj) != "..")
                {
                    xmetadb_remove_dir_rec($obj);
                }
            }
        }
    }
    if (false !== ($objs = glob($dirtodelete . "/*")))
    {
        foreach ($objs as $obj)
        {
            is_dir($obj) ? xmetadb_remove_dir_rec($obj) : unlink($obj);
        }
    }
    if (file_exists($dirtodelete) && is_dir($dirtodelete))
        rmdir($dirtodelete);
}

/**
 * xmetadb_encode_preg_replace2nd
 * prepara la stringa per il secondo parametro
 * dell' preg_replace aggiungendo la \ savanti a \ e $

 *
 */
function xmetadb_encode_preg_replace2nd($str)
{
    $str = str_replace("\\", "\\\\", $str);
    $str = str_replace('$', '\\$', $str);
    return $str;
}

/**
 * xmetadb_encode_preg_replace2nd
 * prepara la stringa per il primo parametro
 * dell' preg_replace aggiungendo
 * la barra davanti ai cratteri speciali
 *
 *
 */
function xmetadb_encode_preg($str)
{
    $str = str_replace('\\', '\\\\', $str);
    $str = str_replace('/', '\\/', $str);
    $str = str_replace('(', '\\(', $str);
    $str = str_replace(')', '\\)', $str);
    $str = str_replace('^', '\\^', $str);
    $str = str_replace('$', '\\$', $str);
    $str = str_replace('*', '\\*', $str);
    $str = str_replace('+', '\\+', $str);
    $str = str_replace('?', '\\?', $str);
    $str = str_replace('[', '\\[', $str);
    $str = str_replace(']', '\\]', $str);
    $str = str_replace('|', '\\|', $str);
    return $str;
}

/**
 * Restituisce un elemento XML
 *
 * Restituisce un elemento XML da un file passato come parametro.
 *
 *
 * @param string $elem Nome dell'elemento XML da cercare
 * @param string $xml Nome del file XML da processare
 * @return string Stringa contenente il valore dell'elemento XML
 */
function get_xml_single_element($elem, $xml)
{
    $xml = xmetadb_removexmlcomments($xml);
    $buff = preg_replace("/.*<" . $elem . ">/s", "", $xml);
    if ($buff == $xml)
        return "";
    $buff = preg_replace("/<\/" . $elem . ">.*/s", "", $buff);
    return $buff;
}

function xmetadb_get_xml_single_element($elem, $xml)
{
    return get_xml_single_element($elem, $xml);
}

/**
 *
 * @param array $data
 * @param string $order
 * @param bool $desc
 */
function xmetadb_array_sort_by_key($data, $order, $desc = false)
{

    $mode = "asc";
    if ($desc)
        $mode = "desc";
    $order = explode(",", $order);
    foreach ($order as $v)
    {
        $newmode = $mode;
        $newmodes = explode(":", $v);
        if (isset($newmodes[1]))
            $newmode = $newmodes[1];
        $orders[$newmodes[0]] = $newmode;
    }
    $orders = array_reverse($orders);

    foreach ($orders as $order => $mode)
    {
        $newret = array();
        $ret = array();
        foreach ($data as $key => $value)
        {
            $ret[$value[$order]][] = $value;
        }
        ksort($ret);
        if ($mode == "desc")
        {
            $ret = array_reverse($ret);
        }
        foreach ($ret as $key => $value)
        {
            foreach ($value as $item)
            {
                $newret[] = $item;
            }
        }
        $data = $newret;
    }

    return $newret;
}

/**
 *
 * @param array $data
 * @param string $order
 * @param bool $desc
 */
function xmetadb_array_natsort_by_key($data, $order, $desc = false)
{

    $ret = array();
    if (!is_array($data))
        return false;
    $mode = "asc";
    if ($desc)
        $mode = "desc";
    $order = explode(",", $order);
    foreach ($order as $v)
    {
        $newmode = $mode;
        $newmodes = explode(":", $v);
        if (isset($newmodes[1]))
            $newmode = $newmodes[1];
        $orders[$newmodes[0]] = $newmode;
    }
    $orders = array_reverse($orders);
    foreach ($orders as $order => $mode)
    {
        $newret = array();
        $ret = array();
        foreach ($data as $key => $value)
        {
            if (!isset($value[$order]))
            {
                $value[$order] = null;
            }
            $ret[$value[$order]][] = $value;
        }
        uksort($ret, "xmetadb_NatSort_callback");
        if ($mode == "desc")
        {
            $ret = array_reverse($ret);
        }
        foreach ($ret as $key => $value)
        {
            foreach ($value as $item)
            {
                $newret[] = $item;
            }
        }
        $data = $newret;
    }
    return $data;
}

/*
  $test[]=array("name"=>1,"name2"=>"1","name3"=>12);
  $test[]=array("name"=>1,"name2"=>"2","name3"=>12);
  $test[]=array("name"=>2,"name2"=>"2","name3"=>10);
  $test[]=array("name"=>2,"name2"=>"1","name3"=>14);
  $test[]=array("name"=>3,"name2"=>"4","name3"=>22);
  $test[]=array("name"=>4,"name2"=>"5","name3"=>1);
  $test[]=array("name"=>5,"name2"=>"6","name3"=>5);
  $test[]=array("name"=>6,"name2"=>"7","name3"=>1);
  $test[]=array("name"=>7,"name2"=>"8","name3"=>5);
  $test[]=array("name"=>8,"name2"=>"9","name3"=>66);
  $test[]=array("name"=>9,"name2"=>"10","name3"=>21);
  //$test2 = xmetadb_array_sort_by_key($test,"name2:asc,name:desc");
  $test2=xmetadb_array_natsort_by_key($test,"name:asc,name2:asc");


  dprint_r($test2);
  die();
 */

/**
 *
 * @param string $a
 * @param string $b
 * @return int 
 */
function xmetadb_NatSort_callback($a, $b)
{
    $a = strtolower($a);
    $b = strtolower($b);
    //if ( fn_erg("^[0-9]", $a) && fn_erg("^[0-9]", $b) )
    if (preg_match("/" . str_replace('/', '\\/', "^[0-9]") . "/s", $a, $regs) && preg_match("/" . str_replace('/', '\\/', "^[0-9]") . "/s", $b, $regs))
    {
        $aa = explode("_", $a);
        $bb = explode("_", $b);
        $aa = $aa[0];
        $bb = $bb[0];
        if (intval($aa) == intval($bb))
        {
            return strnatcmp($a, $b);
        }
        return (intval($aa) < intval($bb)) ? -1 : 1;
    }
    return strnatcmp($a, $b);
}

/**
 *
 * @staticvar boolean $tables
 * @param type $databasename
 * @param type $tablename
 * @param type $path
 * @param type $params
 * @return XMETATable 
 */
function xmetadb_table($databasename, $tablename, $path = "misc", $params = false)
{
    return XMETATable::xmetadbTable($databasename, $tablename, $path , $params );
}

/**
 * 
 * @param type $image
 * @param type $filename
 */
function xmetadb_image_fix_orientation(&$image, $filename)
{
    if (function_exists("exif_read_data"))
    {
        $exif = @exif_read_data($filename);
        if (!empty($exif['Orientation']))
        {
            switch ($exif['Orientation'])
            {
                default:
                    break;
                case 3:
                    $image = imagerotate($image, 180, 0);
                    break;

                case 6:
                    $image = imagerotate($image, -90, 0);
                    break;

                case 8:
                    $image = imagerotate($image, 90, 0);
                    break;
            }
        }
    }
    else
    {
        
    }
}
