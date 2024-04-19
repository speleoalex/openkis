<?php

/**
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2003-2009
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 * @package xmldb
 *
 */
//-----PARSER XML -----
// TODO:
// LA PRIMARYKEY DEVE ESSERE SEMPRE IL PRIMO CAMPO DEL DESCRITTORE
@ini_set("memory_limit", "512M");
define("_MAXTENTATIVIDIACCESSO", "1000");
define("_MAX_FILES_PER_FOLDER", "10000");
define("_MAX_LOCK_TIME", "30"); // seconds
//define("XMLDB_DEBUG_FILE_LOG","/tmp/xmldb.log"); // seconds
function xmldb_Copy($s, $d)
{
    global $_FN;
    if (!file_exists($s) || is_dir($s)) {
        return false;
    }
    $contents = file_get_contents($s);
    if (is_dir($d)) {
        $d .= $_FN['slash'] . basename($s);
    }
    //dprint_r($s." ".$d);
    $h = fopen($d, "wb");
    if ($h === false)
        return false;
    fwrite($h, $contents);
    fclose($h);
    if ($contents != file_get_contents($d)) {
        @unlink($d);
        return false;
    }
    return true;
}
/**
 *
 * @param string $data
 * @param string $elem
 * @param string $fields
 * @return type 
 */
function xmldb_xml2array($data, $elem, $fields = false)
{
    //eliminazione dei commenti
    $data = xmldb_removexmlcomments($data);
    //visualizza solo determinati campi
    if (is_array($fields)) {
        $fields = implode("|", $fields);
    }
    $out = "";
    $ret = null;
    if (preg_match("/<$elem>.*<$elem>[^<]+<\/$elem>/s", $data)) //se il nome del nodo contiene un elemento con lo stesso nome
    {
        preg_match_all("#<$elem>(.*?<$elem>.*?</$elem>.*?)</$elem>#s", $data, $out); //CONTIENE ALL'INTERNO UN NODO CON LO STESSO NOME
    } else {
        preg_match_all("#<$elem>.*?</$elem>#s", $data, $out); //OK
    }
    if (is_array($out[0]))
        foreach ($out[0] as $innerxml) {
            //----------metodo 0 ------------------------
            for ($oi = 0; $oi < 1; $oi++) {
                $tmp2 = $t1 = null;
                preg_match_all('/<(' . $fields . '[^\/]*?)>([^<]*)<\/\1>/s', $innerxml, $t1);
                foreach ($t1[1] as $k => $tt) {
                    if ($t1[2][$k] != null)
                        $tmp2[$tt] = xmldec($t1[2][$k]);
                    else
                        $tmp2[$tt] = "";
                }
            }
            if ($tmp2 != null) {
                $ret[] = ($tmp2);
            }
        }
    return $ret;
}

/**
 * xmldb_readDatabase
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
 * xmldb_readDatabase($filename,"elem")
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
function xmldb_readDatabase($filename, $elem, $fields = false, $usecache = true)
{

    if (!file_exists($filename))
        return false;

    /*
    if (is_array($fields))
    {
        $fields = implode("|", $fields);
    }*/
    $_fields = "_" . $fields;
    static $cache = array();
    static $lastmod = array();
    $filename = realpath($filename);
    if (!isset($lastmod[$filename]) || $lastmod[$filename] != filectime($filename) . filesize($filename)) {
        $lastmod[$filename] = filectime($filename) . filesize($filename);
        $usecache = false;
        //dprint_r("no cache $filename");
    }
    if (is_dir($filename)) {
        $usecache = false;
    }
    if ($usecache === false) {
        if (isset($cache[$filename][$_fields][$elem])) {
            unset($cache[$filename][$_fields][$elem]);
        }
    } else {
        //dprint_r("cache $filename");
    }
    if ($usecache === true && isset($cache[$filename][$_fields][$elem])) {
        return $cache[$filename][$_fields][$elem];
    }
    $tmp = array();
    // --- gestione xml in più files --------->
    if (is_dir($filename)) {
        $data = null;
        $handle = opendir($filename);
        while (false !== ($file = readdir($handle))) {
            $tmp2 = null;
            if (preg_match('/.php$/is', $file))
                $tmp2 = xmldb_readDatabase("$filename/$file", $elem, $fields, $usecache);
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
    for ($i = 0; $i < _MAXTENTATIVIDIACCESSO; $i++) {
        $data = file_get_contents($filename);
        // funziona ma sarebbe da verificare la chiusura di </database>
        if ("" != $data) {
            break;
        }
    }
    //da xml ad array....
    $ret = xmldb_xml2array($data, $elem, $fields); //null if data = ""
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
 * xmldb_create_thumb
 * Crea l' anteprima di un file
 * uso questa funzione per crearmi le anteprime per i campi di tipo immagine
 * occorrono le librerie GD
 * @param string $filename nome del file
 * @param int $max dimensione massima anteprima
 */
function xmldb_create_thumb($filename, $max, $max_h = "", $max_w = "")
{
    if (!$filename)
        return;
    if ($max_h == "")
        $max_h = $max;
    if ($max_w == "")
        $max_w = $max;
    if (!function_exists("getimagesize")) {
        echo "<br />" . _FNNOGDINSTALL;
        return;
    }
    $new_height = $new_width = 0;
    if (!file_exists($filename)) {
        echo "non esiste";
        return;
    }
    if (!getimagesize($filename)) {
        echo "$filename is not image ";
        return;
    }
    list($width, $height, $type, $attr) = getimagesize($filename);
    if (function_exists("exif_read_data")) {
        $exif = @exif_read_data($filename);
        if (!empty($exif['Orientation']) && ($exif['Orientation'] == 6 || $exif['Orientation'] == 8)) {
            $tmp = $height;
            $height = $width;
            $width = $tmp;
        }
    }

    $path = dirname($filename) . "/thumbs";
    $file_thumb = $path . "/" . basename($filename);
    if (!file_exists($path)) {
        mkdir($path);
    }
    if (!file_exists($path)) {
        echo "error make dir $path";
        return false;
    }
    if (!is_dir($path)) {
        echo "<br />$path not exists";
    }
    $new_height = $height;
    $new_width = $width;
    if ($width >= $max_w) {
        $new_width = $max_w;
        $new_height = intval($height * ($new_width / $width));
    }
    //se troppo alta
    if ($new_height >= $max_h) {
        $new_height = $max_h;
        $new_width = intval($width * ($new_height / $height));
    }
    // se l' immagine e gia piccola
    if ($width <= $max_w && $height <= $max_h) {
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
    try {
        switch ($size[2]) {
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
                $source = xmldb_ImageCreateFromBMP($filename);
                break;
            default:
                // unknown file format
                $source = imagecreatetruecolor(300, 300);
                $color = imagecolorallocate($source, 255, 255, 255);
                imagefill($source, 0, 0, $color);
                break;
        }
    } catch (Exception $e) {
        $source = false;
    }

    if (!$source) {
        return;
    }
    xmldb_image_fix_orientation($source, $filename);
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
function xmldb_ImageCreateFromBMP($filename)
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
    if ($BMP['colors'] < 16777216) {
        $PALETTE = unpack('V' . $BMP['colors'], fread($f1, $BMP['colors'] * 4));
    }
    $IMG = fread($f1, $BMP['size_bitmap']);
    $VIDE = chr(0);
    $res = imagecreatetruecolor($BMP['width'], $BMP['height']);
    $P = 0;
    $Y = $BMP['height'] - 1;
    while ($Y >= 0) {
        $X = 0;
        while ($X < $BMP['width']) {
            if ($BMP['bits_per_pixel'] == 24)
                $COLOR = unpack("V", substr($IMG, $P, 3) . $VIDE);
            elseif ($BMP['bits_per_pixel'] == 16) {
                $COLOR = unpack("n", substr($IMG, $P, 2));
                $COLOR[1] = $PALETTE[$COLOR[1] + 1];
            } elseif ($BMP['bits_per_pixel'] == 8) {
                $COLOR = unpack("n", $VIDE . substr($IMG, $P, 1));
                $COLOR[1] = $PALETTE[$COLOR[1] + 1];
            } elseif ($BMP['bits_per_pixel'] == 4) {
                $COLOR = unpack("n", $VIDE . substr($IMG, floor($P), 1));
                if (($P * 2) % 2 == 0)
                    $COLOR[1] = ($COLOR[1] >> 4);
                else
                    $COLOR[1] = ($COLOR[1] & 0x0F);
                $COLOR[1] = $PALETTE[$COLOR[1] + 1];
            } elseif ($BMP['bits_per_pixel'] == 1) {
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
            } else
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
 * xmldb_removexmlcomments
 * rimuove i commenti da un file xml
 *
 * @param string $data
 * @return string xml privo di commenti
 *
 */
function xmldb_removexmlcomments($data)
{
    $data = preg_replace("/<!--(.*?)-->/ms", "", $data);
    $data = preg_replace("/<\\?(.*?)\\?>/", "", $data);
    return $data;
}

//-------------------------FUNZIONI DI CREAZIONE/MODIFICA DATABASE----------------
/**
 * createxmltable
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
 * $fields[0]['name']="unirecid";
 * $fields[0]['primarykey']=1;
 * $fields[0]['defaultvalue']=null;
 * $fields[0]['type']="varchar";
 * $fields[1]['name']="test";
 * $fields[1]['primarykey']=0;
 * $fields[1]['defaultvalue']="pippo";
 * $fields[1]['type']="varchar";
 * createxmltable("plugins","test",$fields,"misc");
 * */
function createxmltable($databasename, $tablename, $fields, $path = ".", $singlefilename = false)
{
    if (!file_exists("$path/$databasename") || !is_dir("$path/$databasename"))
        return "xml databse not exists";
    if (file_exists("$path/$databasename/$tablename") && file_exists("$path/$databasename/$tablename.php"))
        return "xml table exists";
    if (!is_writable("$path/$databasename/"))
        return "xml database not writable";
    $str = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<?php exit(0);?>\n<tables>";
    foreach ($fields as $field) {
        $str .= "\n\t<field>";
        foreach ($field as $key => $value) {
            $str .= "\n\t\t<$key>$value</$key>";
        }
        $str .= "\n\t</field>";
    }
    if ($singlefilename != false) {
        if (is_array($singlefilename)) {
            foreach ($singlefilename as $key => $values) {
                $str .= "\n\t<$key>" . xmlenc($values) . "</$key>";
            }
        } else {
            $str .= "\n<filename>$singlefilename</filename>";
        }
    }
    $str .= "\n</tables>";
    if (!file_exists("$path/$databasename/$tablename")) {
        mkdir("$path/$databasename/$tablename");
        //dprint_r("$path/$databasename/$tablename");
    }
    $file = fopen("$path/$databasename/$tablename.php", "w");
    fwrite($file, $str);
    fclose($file);
    return false;
}

/**
 * createxmldatabase
 * crea un database
 *
 * @param string $databasename
 * @param string $path
 * @return false se il databare e'stato creato oppure una stringa che contiene l' errore
 */
function createxmldatabase($databasename, $path = ".")
{
    if (file_exists("$path/$databasename"))
        return "database $databasename already exists";
    if (!is_writable("$path/"))
        return "database not writable";
    mkdir("$path/$databasename");
    return false;
}

/**
 * xmldatabaseexists
 * verifica se un database esiste
 *
 * @param string $databasename
 * @param string $path
 */
function xmldatabaseexists($databasename, $path = ".", $conn = false)
{
    return (file_exists("$path/$databasename"));
}

function xmltableexists($databasename, $tablename, $path = ".")
{
    return (file_exists("$path/$databasename/$tablename") && file_exists("$path/$databasename/$tablename.php"));
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
    for ($i = 0; $i < _MAXTENTATIVIDIACCESSO; $i++) {
        $oldfilestring = file_get_contents($old);
        if (strpos($oldfilestring, "</tables>") !== false) {
            $readok = true;
            break;
        }
    }
    if (!$readok) {
        die("error update");
    }
    $oldfilestring = xmldb_removexmlcomments($oldfilestring);
    $oldvalues = $newvalues = getxmltablefield($databasename, $tablename, $field['name'], $path);
    foreach ($values as $key => $value) {

        $newvalues[$key] = $value;
    }
    //compongo il nuovo xml per il record da aggiornare
    $strnew = "<field>";
    foreach ($newvalues as $key => $value) {
        $strnew .= "\n\t\t<$key>" . xmlenc($value) . "</$key>";
    }
    $strnew .= "\n\t</field>";

    if ($oldvalues) {
        $pvalue = xmlenc($pvalue);
        $pvalue = xmldb_encode_preg($pvalue);
        $strnew = str_replace('$', '\\$', $strnew);
        $newfilestring = preg_replace('/<field>([^(field)]*)<' . $pkey . '>' . $pvalue . '<\/' . $pkey . '>(.*?)<\/field>/s', $strnew, $oldfilestring);
        if (!is_writable($old)) {
            echo ("$old is readonly,I can't update");
            return ("$old is readonly,I can't update");
        }
        if ($oldfilestring != $newfilestring && $force) {
            $handle = fopen($old, "w");
            fwrite($handle, $newfilestring);
            xmldb_readDatabase($old, 'field', false, false); //aggiorna la cache
        }
        return $newvalues;
    } else // new field
    {
        for ($i = 0; $i < _MAXTENTATIVIDIACCESSO; $i++) {
            $oldfilestring = file_get_contents("$path/$databasename/$tablename.php");
            if (strpos($oldfilestring, "</tables>") !== false) {
                $readok = true;
                break;
            }
        }
        if (!$readok) {
            return "error insert field";
        }
        $strnew = xmldb_encode_preg_replace2nd($strnew);
        $newfilestring = preg_replace('/<\/tables>$/s', xmldb_encode_preg_replace2nd($strnew) . "\n</tables>", trim($oldfilestring)) . "\n";
        $handle = fopen("$path/$databasename/$tablename.php", "w");
        fwrite($handle, $newfilestring);
        fclose($handle);
        xmldb_readDatabase($old, 'field', false, false); //aggiorna la cache
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
    $rows = xmldb_readDatabase("$path/$databasename/$tablename.php", "field");
    foreach ($rows as $row) {
        if ($row['name'] == $fieldname) {
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
function xmldb_remove_dir_rec($dirtodelete)
{
    if (strpos($dirtodelete, "../") !== false)
        die("xmldberror:xmldb_remove_dir_rec");
    if (false != ($objs = glob($dirtodelete . "/.*"))) {
        foreach ($objs as $obj) {
            if (!is_dir($obj))
                unlink($obj);
            else {
                if (basename($obj) != "." && basename($obj) != "..") {
                    xmldb_remove_dir_rec($obj);
                }
            }
        }
    }
    if (false !== ($objs = glob($dirtodelete . "/*"))) {
        foreach ($objs as $obj) {
            is_dir($obj) ? xmldb_remove_dir_rec($obj) : unlink($obj);
        }
    }
    if (file_exists($dirtodelete) && is_dir($dirtodelete))
        rmdir($dirtodelete);
}

/**
 * xmldb_encode_preg_replace2nd
 * prepara la stringa per il secondo parametro
 * dell' preg_replace aggiungendo la \ savanti a \ e $

 *
 */
function xmldb_encode_preg_replace2nd($str)
{
    $str = str_replace("\\", "\\\\", $str);
    $str = str_replace('$', '\\$', $str);
    return $str;
}

/**
 * xmldb_encode_preg_replace2nd
 * prepara la stringa per il primo parametro
 * dell' preg_replace aggiungendo
 * la barra davanti ai cratteri speciali
 *
 *
 */
function xmldb_encode_preg($str)
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
    $xml = xmldb_removexmlcomments($xml);
    $buff = preg_replace("/.*<" . $elem . ">/s", "", $xml);
    if ($buff == $xml)
        return "";
    $buff = preg_replace("/<\/" . $elem . ">.*/s", "", $buff);
    return $buff;
}

function xmldb_get_xml_single_element($elem, $xml)
{
    return get_xml_single_element($elem, $xml);
}

/**
 *
 * @param array $data
 * @param string $order
 * @param bool $desc
 */
function xmldb_array_sort_by_key($data, $order, $desc = false)
{

    $mode = "asc";
    if ($desc)
        $mode = "desc";
    $order = explode(",", $order);
    foreach ($order as $v) {
        $newmode = $mode;
        $newmodes = explode(":", $v);
        if (isset($newmodes[1]))
            $newmode = $newmodes[1];
        $orders[$newmodes[0]] = $newmode;
    }
    $orders = array_reverse($orders);

    foreach ($orders as $order => $mode) {
        $newret = array();
        $ret = array();
        foreach ($data as $key => $value) {
            $ret[$value[$order]][] = $value;
        }
        ksort($ret);
        if ($mode == "desc") {
            $ret = array_reverse($ret);
        }
        foreach ($ret as $key => $value) {
            foreach ($value as $item) {
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
function xmldb_array_natsort_by_key($data, $order, $desc = false)
{

    $ret = array();
    if (!is_array($data))
        return false;
    $mode = "asc";
    if ($desc)
        $mode = "desc";
    $order = explode(",", $order);
    foreach ($order as $v) {
        $newmode = $mode;
        $newmodes = explode(":", $v);
        if (isset($newmodes[1]))
            $newmode = $newmodes[1];
        $orders[$newmodes[0]] = $newmode;
    }
    $orders = array_reverse($orders);
    foreach ($orders as $order => $mode) {
        $newret = array();
        $ret = array();
        foreach ($data as $key => $value) {
            if (!isset($value[$order])) {
                $value[$order] = null;
            }
            $ret[$value[$order]][] = $value;
        }
        uksort($ret, "xmldb_NatSort_callback");
        if ($mode == "desc") {
            $ret = array_reverse($ret);
        }
        foreach ($ret as $key => $value) {
            foreach ($value as $item) {
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
  //$test2 = xmldb_array_sort_by_key($test,"name2:asc,name:desc");
  $test2=xmldb_array_natsort_by_key($test,"name:asc,name2:asc");


  dprint_r($test2);
  die();
 */

/**
 *
 * @param string $a
 * @param string $b
 * @return int 
 */
function xmldb_NatSort_callback($a, $b)
{
    $a = strtolower($a);
    $b = strtolower($b);
    //if ( fn_erg("^[0-9]", $a) && fn_erg("^[0-9]", $b) )
    if (preg_match("/" . str_replace('/', '\\/', "^[0-9]") . "/s", $a, $regs) && preg_match("/" . str_replace('/', '\\/', "^[0-9]") . "/s", $b, $regs)) {
        $aa = explode("_", $a);
        $bb = explode("_", $b);
        $aa = $aa[0];
        $bb = $bb[0];
        if (intval($aa) == intval($bb)) {
            return strnatcmp($a, $b);
        }
        return (intval($aa) < intval($bb)) ? -1 : 1;
    }
    return strnatcmp($a, $b);
}

/**
 *
 * @return type 
 */
function xmldb_now()
{
    return date("Y-m-d H:i:s", time());
}

/**
 *
 * @staticvar boolean $tables
 * @param type $databasename
 * @param type $tablename
 * @param type $path
 * @param type $params
 * @return XMLTable 
 */
function xmldb_table($databasename, $tablename, $path = "misc", $params = false)
{
    static $tables = array();
    if (is_array($tablename)) {
        return new XMLTable($databasename, $tablename, $path, $params);
    }
    $assoc = $params;
    $inglue = ",";
    if (is_array($assoc) && count($assoc) > 0) {
        //ksort($assoc,$assoc);
        foreach ($assoc as $tk => $tv) {
            if (is_array($tv)) {
                $tv = implode("-", $tv);
            }
            $return[] = $tk . $inglue . $tv;
        }
        $assoc = implode($inglue, $return);
    }
    if (is_array($assoc)) {
        $assoc = implode($inglue, $assoc);
    }

    $id = "$databasename," . $tablename . ",$path;" . $assoc;
    if (!isset($tables[$id])) {
        $tables[$id] = new XMLTable($databasename, $tablename, $path, $params);
    }

    return $tables[$id];
}

//---------------------start class XMLField------------------------------------>
/**
 * classe  XMLField
 * classe che descrive un singolo field della tabella
 */
class XMLField
{

    var
        $title = null;
    var
        $readonly = null;
    var
        $foreignkey = null; //foreignkey
    var
        $_defaultvalue;
    var
        $type = null;

    function __construct($descriptionfile, $fieldname)
    {
        //---proprieta' relative al database
        $this->type = "varchar";
        $this->name = "";
        $this->extra = "";
        $this->primarykey = "";
        $this->size = "";
        if (!is_array($descriptionfile))
            $obj = xmldb_readDatabase($descriptionfile, "field");
        else
            $obj = $descriptionfile;
        $fields = null;
        foreach ($obj as $ob) {
            if (isset($ob['name']) && $ob['name'] == $fieldname) {
                $fields = $ob;
                break;
            }
        }
        if ($fields != null) {
            foreach ($fields as $key => $value) {
                $this->$key = $value;
            }
        }
        if ($this->title == null) {
            $this->title = $this->name; // se e' null prende il nome del campo
        }
        if ($this->type == "string") {
            $this->type = "varchar";
        }
        if ($this->type == "varchar" && $this->size == "") {
            $this->size = 255;
        }
    }
}

//-----------------------end class XMLField------------------------------------<
/**
 * Classe per la gestione dei files xml per avere funzioni
 * simili a quelle di un database.
 * I dati sono salvati in files xml con estensione .php
 * <?php exit(0);?> all' inizio del file permette che questo non venga
 * visualizzato da un accesso diretto.
 * Il sistema e' composto da un file che descrive la tabella e di uno
 * o piu' files che contengono i dati.
 *
 * ESEMPIO :
 * -----------FILE DI DESCRIZIONE-----
 *
 * /misc/plugins/stati.php
 *
 * <?php exit(0);?>
 * <tables>
 * <field>
 * <name>unirecid</name>
 * <type>string</type>
 * </field>
 * <field>
 * <name>Codice</name>
 * <type>string</type>
 * </field>
 * <field>
 * <name>Nazione</name>
 * <type>string</type>
 * </field>
 * <field>
 * <name>CodiceISO</name>
 * <type>string</type>
 * </field>
 * <driver>xmlphp</driver>
 * </tables>
 *
 * I dati vengono salvati a seconda del driver utilizzato.
 * il driver di default e' xmlphp
 *
 * --------------FILE DEI DATI xmlphp--------
 * /misc/plugins/stati/stati.php
 * <plugins>
 * <!-- Tabella stati -->
 * <stati>
 * <unirecid>MOAS200312191548500468000002</unirecid>
 * <Codice>I</Codice>
 * <Nazione>ITALIA</Nazione>
 * <en>ITALY</en>
 * <it>ITALIA</it>
 * <iva>0</iva>
 * </stati>
 * <stati>
 * <unirecid>CASH200410080948160634006779</unirecid>
 * <Codice>D</Codice>
 * <Nazione>GERMANY</Nazione>
 * <CodiceISO>DE</CodiceISO>
 * </stati>
 */
class XMLTable
{

    var $databasename;
    var $tablename;
    var $primarykey;
    var $filename;
    var $indexfield;
    var $connection;
    var $driverclass = false;
    var $driver = "xmlphp";
    var $fields = array();
    var $path;
    var $numrecords = -1;
    var $numrecordscache = array();
    var $usecachefile = 0;
    var $xmlfieldname;
    var $xmltagroot;
    var $pathdata = "";


    function __construct($databasename, $tablename, $path = "misc", $params = false)
    {
        $this->connection = false;
        $this->driverclass = false;
        $this->driver = "xmlphp";
        $this->tablename = $tablename;
        $this->databasename = $databasename;
        $this->fields = array();
        $this->path = $path;
        $this->numrecords = -1;
        $this->numrecordscache = array();
        $this->usecachefile = 0;
        $this->xmlfieldname = $tablename;
        $this->xmltagroot = $this->databasename;
        $this->pathdata = "";
        //if is xml
        if (is_array($tablename)) {
            $this->xmldescriptor = $tablename['xml'];
            $fields = xmldb_xml2array($this->xmldescriptor, "field", false);
            if (!is_array($fields))
                return false;
            if (isset($tablename['tablename']))
                $this->tablename = $tablename['tablename'];
            else
                die("tablename is not set");

            foreach ($fields as $field) {
                $xmlfield = new XMLField($fields, $field['name']);
                $this->fields[$field['name']] = $xmlfield;
            }
        } else {
            if ($tablename == "")
                die("tablename is empty");
            $this->tablename = $tablename;
            if (!file_exists("$path/$databasename/{$this->tablename}.php")) {
                return false;
            }
            if (!file_exists("$path/$databasename/{$this->tablename}")) {
                if (!is_writable("$path/$databasename/"))
                    return false;
                mkdir("$path/$databasename/{$this->tablename}");
            }
            //fix old escriptor--->
            $tmp = file_get_contents("$path/$databasename/{$this->tablename}.php");
            $this->xmldescriptor = $tmp;
            if (false !== strpos($tmp, "multilinguage")) {
                if (is_writable("$path/$databasename/{$this->tablename}.php")) {
                    $tmp = str_replace("multilinguage", "multilanguage", $tmp);
                    $h = fopen("$path/$databasename/{$this->tablename}.php", "w");
                    fwrite($h, $tmp);
                    fclose($h);
                }
            }
            $this->xmldescriptor = $tmp;
            //fix old escriptor---<
            $this->usecachefile = get_xml_single_element("usecachefile", $this->xmldescriptor);
            $this->indexfield = get_xml_single_element("indexfield", $this->xmldescriptor);
            $this->pathdata = get_xml_single_element("pathdata", $this->xmldescriptor);

            if (!file_exists("$path/$databasename/{$this->tablename}.php"))
                return false;

            //dprint_r("$path/$databasename/{$this->tablename}.php");
            $fields = xmldb_readDatabase("$path/$databasename/{$this->tablename}.php", "field");
            if (!is_array($fields))
                return false;
            $this->primarykey = '';
            foreach ($fields as $field) {
                $xmlfield = new XMLField("$path/$databasename/{$this->tablename}.php", $field['name']);
                $this->fields[$field['name']] = $xmlfield;
            }
        }
        $this->datafile = $this->path . "/" . $this->databasename . "/" . $this->tablename . "/";
        $this->xmlfieldname = $this->tablename;
        // cerca la chiave primaria
        $this->primarykey = array();
        foreach ($fields as $field) {
            if (isset($field['primarykey']) && $field['primarykey'] == "1")
                $this->primarykey[] = $field['name'];
        }
        if (count($this->primarykey) == 1 && isset($this->primarykey[0])) {
            $this->primarykey = $this->primarykey[0];
        }
        //modalita' database---->
        $this->driver = get_xml_single_element("driver", $this->xmldescriptor);
        global $xmldb_default_driver;
        if ($this->driver == "" && $xmldb_default_driver != "") {
            $this->driver = $xmldb_default_driver;
        }
        if ($this->driver == "") {
            $this->driver = "xmlphp";
        }
        if (is_array($params)) {
            foreach ($params as $k => $v) {
                $this->$k = $v;
            }
        }
        if (file_exists(dirname(__FILE__) . "/xmldb_{$this->driver}.php")) {
            require_once "xmldb_{$this->driver}.php";
        }

        $classname = "XMLTable_" . $this->driver;
        if (!class_exists($classname)) {
            die("xmldberror: $classname not exists in table $tablename");
        }
        $this->driverclass = new $classname($this, $params);
        if (!is_object($this->driverclass))
            die("xmldberror: this->driverclass");
        //modalita' database----<
    }

    function getFilePath($recordvalues, $recordkey)
    {
        if ($recordkey == "")
            return false;
        $databasename = $this->databasename;
        $tablename = $this->tablename;
        $path = realpath($this->path);
        $unirecid = $recordvalues[$this->primarykey];
        if (!isset($recordvalues[$recordkey]))
            $recordvalues = $this->GetRecord($recordvalues);
        $value = isset($recordvalues[$recordkey]) ? $recordvalues[$recordkey] : null;
        $tablepath = $this->FindFolderTable($recordvalues);
        if ($value != "" /* && file_exists("$path/$databasename/$tablepath/$unirecid/$recordkey/$value") */) {
            //die($this->path ."/$databasename/$tablepath/$unirecid/$recordkey/$value");
            return $this->path . "/$databasename/$tablepath/$unirecid/$recordkey/" . $value;
        }
        return false;
    }

    function getThumbPath($recordvalues, $recordkey)
    {
        $databasename = $this->databasename;
        $tablename = $this->tablename;
        $path = realpath($this->path);
        $ret = "";
        $unirecid = $recordvalues[$this->primarykey];
        if (!isset($recordvalues[$recordkey]))
            $recordvalues = $this->GetRecord($recordvalues);
        $value = $recordvalues[$recordkey];
        $tablepath = $this->FindFolderTable($recordvalues);
        if (file_exists("$path/$databasename/$tablepath/$unirecid/$recordkey/thumbs/$value.jpg")) {
            return $this->path . "/$databasename/$tablepath/$unirecid/$recordkey/thumbs/$value.jpg";
        }
        return $this->getFilePath($recordvalues, $recordkey);
    }

    //-----metodi del driver---------------->

    function get_file($recordvalues, $recordkey)
    {
        $file = $this->getFilePath($recordvalues, $recordkey);
        if ($file && file_exists($file)) {
            $php_self = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : "";
            $dirname = dirname($php_self);
            if ($dirname == "/" || $dirname == "\\")
                $dirname = "";
            $protocol = "http://";
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on")
                $protocol = "https://";
            $siteurl = "$protocol" . $_SERVER['HTTP_HOST'] . $dirname;
            if (substr($siteurl, strlen($siteurl) - 1, 1) != "/") {
                $siteurl = $siteurl . "/";
            }
            return "$siteurl" . $file;
        }
        return false;
    }

    function get_thumb($recordvalues, $recordkey)
    {
        $file = $this->getThumbPath($recordvalues, $recordkey);

        if ($file && file_exists($file)) {
            $php_self = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : "";
            $dirname = dirname($php_self);
            if ($dirname == "/" || $dirname == "\\")
                $dirname = "";
            $protocol = "http://";
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on")
                $protocol = "https://";
            $siteurl = "$protocol" . $_SERVER['HTTP_HOST'] . $dirname;
            if (substr($siteurl, strlen($siteurl) - 1, 1) != "/") {
                $siteurl = $siteurl . "/";
            }
            return "$siteurl" . $file;
        }
        return false;
    }

    function SetFile($key, $filepath, $filename = "")
    {
        if (!file_exists($filepath)) {
            dprint_r("$filepath not exists");
        }
        $_FILES[$key]['tmp_name'] = realpath($filepath);

        if ($filename == "")
            $filename = basename($filepath);
        $_FILES[$key]['name'] = $filename;
    }

    //-----metodi del driver---------------->
    function GetNumRecords($restr = null)
    {
        return $this->driverclass ? $this->driverclass->GetNumRecords($restr) : null;
    }

    function GetRecords($restr = false, $min = false, $length = false, $order = false, $reverse = false, $fields = false)
    {

        return $this->driverclass ? $this->driverclass->GetRecords($restr, $min, $length, $order, $reverse, $fields) : null;
    }

    function GetRecord($restr = false)
    {

        return $this->driverclass ? $this->driverclass->GetRecord($restr) : null;
    }

    function GetRecordByPrimaryKey($unirecid)
    {
        return $this->driverclass ? $this->driverclass->GetRecordByPrimaryKey($unirecid) : null;
    }

    function GetAutoincrement($field)
    {
        return $this->driverclass ? $this->driverclass->GetAutoincrement($field) : null;
    }

    function InsertRecord($values)
    {
        if (defined("XMLDB_DEBUG_FILE_LOG") && XMLDB_DEBUG_FILE_LOG) {
            file_put_contents(XMLDB_DEBUG_FILE_LOG, FN_Now() . " " . __METHOD__ . " {$this->tablename}" . "\n", FILE_APPEND);
            if ($this->tablename == "fn_settings") {
                file_put_contents(XMLDB_DEBUG_FILE_LOG, FN_Now() . " " . __METHOD__ . " values: " . json_encode($values) . "\n", FILE_APPEND);
            }
        }
        $this->SetLastUpdateTime();
        return $this->driverclass ? $this->driverclass->InsertRecord($values) : null;
    }

    function SetLastUpdateTime()
    {
        @touch("{$this->path}/{$this->databasename}/{$this->tablename}/updated");
    }

    /**
     * 
     * @return type
     */
    function GetLastUpdateTime()
    {
        if (file_exists("{$this->path}/{$this->databasename}/{$this->tablename}/updated")) {
            return (filectime("{$this->path}/{$this->databasename}/{$this->tablename}/updated"));
        } else {
            return (filectime("{$this->path}/{$this->databasename}/{$this->tablename}.php"));
        }
    }

    function DelRecord($pkvalue)
    {
        if (defined("XMLDB_DEBUG_FILE_LOG") && XMLDB_DEBUG_FILE_LOG) {
            file_put_contents(XMLDB_DEBUG_FILE_LOG, FN_Now() . " " . __METHOD__ . " {$this->tablename}" . "\n", FILE_APPEND);
            if ($this->tablename == "fn_settings") {
                file_put_contents(XMLDB_DEBUG_FILE_LOG, FN_Now() . " " . __METHOD__ . " value: $pkvalue\n", FILE_APPEND);
            }
        }
        $this->SetLastUpdateTime();
        return $this->driverclass ? $this->driverclass->DelRecord($pkvalue) : null;
    }

    function GetFileRecord($pkey, $pvalue)
    {
        return $this->driverclass ? $this->driverclass->GetFileRecord($pkey, $pvalue) : null;
    }

    function Truncate()
    {
        return $this->driverclass ? $this->driverclass->Truncate() : null;
    }

    function GetRecordByPk($pvalue)
    {
        return $this->driverclass ? $this->driverclass->GetRecordByPk($pvalue) : null;
    }

    function UpdateRecordBypk($values, $pkey, $pvalue)
    {
        if (defined("XMLDB_DEBUG_FILE_LOG") && XMLDB_DEBUG_FILE_LOG) {
            file_put_contents(XMLDB_DEBUG_FILE_LOG, FN_Now() . " " . __METHOD__ . " {$this->tablename}" . "\n", FILE_APPEND);
            if ($this->tablename == "fn_settings") {
                file_put_contents(XMLDB_DEBUG_FILE_LOG, FN_Now() . " " . __METHOD__ . " values: " . json_encode($values) . "\n", FILE_APPEND);
            }
        }
        $this->SetLastUpdateTime();
        return $this->driverclass ? $this->driverclass->UpdateRecordBypk($values, $pkey, $pvalue) : null;
    }

    function UpdateRecord($values, $pkvalue = false)
    {
        if (is_array($this->primarykey)) {
            if ($pkvalue && !is_array($pkvalue))
                return false;
            if ($pkvalue !== false)
                $unirecid = $pkvalue;
            else {
                $unirecid = array();
                foreach ($this->primarykey as $pkk) {
                    $unirecid[$pkk] = $values[$this->$pkk];
                }
            }
        } else {
            if (!isset($values[$this->primarykey]) && $pkvalue === false)
                return false;
            if ($pkvalue !== false)
                $unirecid = $pkvalue;
            else
                $unirecid = $values[$this->primarykey];
        }

        return $this->UpdateRecordBypk($values, $this->primarykey, $unirecid);
    }

    //-----metodi del driver----------------<
    function FindFolderTable($oldvalues)
    {

        if (!isset($oldvalues[$this->primarykey])) {
            return false;
        }
        $id = $oldvalues[$this->primarykey];
        $key = $this->primarykey;
        $databasename = $this->databasename;
        $dirtable_oldvalue = $this->tablename;
        if ($this->pathdata)
            $dirtable_oldvalue = $this->pathdata;

        $path = realpath($this->path);
        $found = false;
        $notexists = false;
        //-----------------first folder---------------------------------------->
        $oldfileimage = "$path/$databasename/$dirtable_oldvalue/$id";
        //dprint_r($oldfileimage);
        if (file_exists($oldfileimage)) {
            return $dirtable_oldvalue;
        }
        //-----------------first folder----------------------------------------<
        $i = 1;
        $ret = $dirtable_oldvalue;
        $max = count(glob("$path/$databasename/*"));
        while ($i < $max) {
            $tmp = explode(".", $dirtable_oldvalue);
            $dirtable_oldvalue = $tmp[0] . ".$i";
            $oldfileimage = "$path/$databasename/$dirtable_oldvalue/$id/";
            if (file_exists($oldfileimage)) {
                $ret = $dirtable_oldvalue;
            }
            $i++;
        }

        return $ret;
    }

    /**
     * gestfiles
     * Gestione ei files ricevuti per post
     * @param array $values
     */
    function gestfiles($values, $oldvalues = null)
    {

        $databasename = $this->databasename;
        $tablename = $this->tablename;
        $path = realpath($this->path);
        $newvalues = $values;
        //----gestione campi d tipo FILES o IMAGE
        if (is_array($this->primarykey) || !isset($newvalues[$this->primarykey]))
            return;
        $unirecid = $newvalues[$this->primarykey];
        $dirtable_new = false;
        if (isset($oldvalues[$this->primarykey]) && $oldvalues[$this->primarykey] != $values[$this->primarykey]) {
            $dirtable = $this->FindFolderTable($oldvalues);
            if (false !== $dirtable) {
                if (file_exists("$path/$databasename/$dirtable/" . $oldvalues[$this->primarykey])) {
                    rename("$path/$databasename/$dirtable/" . $oldvalues[$this->primarykey], "$path/$databasename/$dirtable/" . $values[$this->primarykey]);
                    $dirtable_new = $this->FindFolderTable($oldvalues);
                }
            }
        } elseif (isset($oldvalues[$this->primarykey])) {
            $dirtable_new = $this->FindFolderTable($oldvalues);
        }
        if ($dirtable_new === false) {
            $i = 1;
            $maxfiles = intval(_MAX_FILES_PER_FOLDER);
            $dirtable_new = $tablename;
            if ($this->pathdata)
                $dirtable_oldvalue = $this->pathdata;

            while (file_exists("$path/$databasename/$dirtable_new") && count(glob("$path/$databasename/$dirtable_new/*")) >= $maxfiles) {
                $tmp = explode(".", $dirtable_new);
                $dirtable_new = $tmp[0] . ".$i";
                $i++;
            }
        }


        //die ($dirtable_new);
        foreach ($newvalues as $key => $value) {
            $type = isset($this->fields[$key]) ? $this->fields[$key] : null;
            if (isset($type->type) && ($type->type == 'file' || $type->type == 'image')) {
                //cancello i vecchi record se esiste il nuovo
                $dirtable_oldvalue = false;
                if (isset($values[$this->primarykey])) {

                    if (isset($_FILES[$key]['tmp_name']) && $_FILES[$key]['tmp_name'] != "" && $oldvalues != null && isset($values[$key])) // se e' un aggiornamento
                    {
                        //find folder--->
                        $dirtable_oldvalue = $this->FindFolderTable($values);
                        if ($dirtable_oldvalue == false)
                            $dirtable_oldvalue = $tablename;
                        //find folder---<
                    }
                    if (!empty($values[$this->primarykey]) && !empty($oldvalues[$key])) {

                        $oldfileimage = "$path/$databasename/$dirtable_oldvalue/" . $values[$this->primarykey] . "/" . $key . "/" . $oldvalues[$key];
                        $oldfilethumb = "$path/$databasename/$dirtable_oldvalue/" . $values[$this->primarykey] . "/" . $key . "/thumbs/" . $oldvalues[$key] . ".jpg";
                        if ($dirtable_oldvalue != false && $oldvalues[$key] != "" && file_exists($oldfilethumb)) {
                            unlink($oldfilethumb);
                        }
                        if ($dirtable_oldvalue != false && $oldvalues[$key] != "" && file_exists($oldfileimage)) {
                            unlink($oldfileimage);
                        }

                        // cancellazione di un record
                        if (isset($_POST["__isnull__$key"]) && $_POST["__isnull__$key"] == "null") {
                            $dirtable_oldvalue = $this->FindFolderTable($values);
                            $oldfileimage = "$path/$databasename/$dirtable_oldvalue/" . $values[$this->primarykey] . "/" . $key . "/" . $oldvalues[$key];
                            $oldfilethumb = "$path/$databasename/$dirtable_oldvalue/" . $values[$this->primarykey] . "/" . $key . "/thumbs/" . $oldvalues[$key] . ".jpg";
                            if ($oldvalues[$key] != "" && file_exists($oldfilethumb)) {
                                unlink($oldfilethumb);
                                rmdir(dirname($oldfilethumb));
                            }
                            if ($oldvalues[$key] != "" && file_exists($oldfileimage)) {
                                unlink($oldfileimage);
                                rmdir(dirname($oldfileimage));
                            }
                        }
                    }
                }
                if (isset($_FILES[$key]['tmp_name']) && $_FILES[$key]['tmp_name'] != "") {
                    $name_clean = $_FILES["$key"]['name'];
                    if (ini_get('magic_quotes_gpc') == 1) {
                        $name_clean = stripslashes($_FILES["$key"]['name']);
                    }
                    $name_clean = str_replace("\\", "", $name_clean);
                    $name_clean = str_replace("/", "", $name_clean);

                    //die ($name_clean);
                    if (preg_match('/.php/is', $name_clean) || preg_match('/.php3/is', $name_clean) || preg_match('/.php4/is', $name_clean) || preg_match('/.php5/is', $name_clean) || preg_match('/.phtml/is', $name_clean)) {
                        touch("$path/$databasename/$dirtable_new/$unirecid/$key/" . $name_clean);
                    } else {

                        if (!file_exists("$path/$databasename/$dirtable_new/"))
                            mkdir("$path/$databasename/$dirtable_new/");
                        if (!file_exists("$path/$databasename/$dirtable_new/$unirecid"))
                            mkdir("$path/$databasename/$dirtable_new/$unirecid");
                        if (!file_exists("$path/$databasename/$dirtable_new/$unirecid/$key"))
                            mkdir("$path/$databasename/$dirtable_new/$unirecid/$key");
                        //workarround: alla insert non funziona move_uploaded_file
                        //se elimino il file temporaneo non funziona nemmeno copy  
                        if ($oldvalues) {
                            move_uploaded_file($_FILES[$key]['tmp_name'], "$path/$databasename/$dirtable_new/$unirecid/$key/" . $name_clean);
                            if (!file_exists("$path/$databasename/$dirtable_new/$unirecid/$key/" . $name_clean)) {
                                xmldb_Copy($_FILES[$key]['tmp_name'], "$path/$databasename/$dirtable_new/$unirecid/$key/" . $name_clean);
                            }
                            if (!file_exists("$path/$databasename/$dirtable_new/$unirecid/$key/" . $name_clean)) {
                                trigger_error("failed copy {$_FILES[$key]['tmp_name']}");
                                dprint_r("failed copy {$_FILES[$key]['tmp_name']} to " . "$path/$databasename/$dirtable_new/$unirecid/$key/" . $name_clean);
                            }
                        } else {
                            $tmpname = $_FILES[$key]['tmp_name'];
                            FN_Copy($tmpname, "$path/$databasename/$dirtable_new/$unirecid/$key/" . $name_clean);
                            if (!file_exists("$path/$databasename/$dirtable_new/$unirecid/$key/" . $name_clean)) {
                                trigger_error("failed copy {$_FILES[$key]['tmp_name']}", E_USER_WARNING);
                                dprint_r("failed copy {$_FILES[$key]['tmp_name']} to " . "$path/$databasename/$dirtable_new/$unirecid/$key/" . $name_clean);
                            }
                        }
                        $create_thumb[$key] = true;
                    }
                }
            }
        }
        //---------------- creazione anteprime per le immagini ----------------------

        foreach ($this->fields as $field) {
            switch ($field->type) {
                case "image":
                    if (isset($values[$field->name]) && $values[$field->name] != "") // se il campo e' stato aggiornato
                    {
                        $dirtable = $dirtable_new;
                        if ($this->pathdata)
                            $dirtable = $this->pathdata;

                        $fileimage = isset($values[$this->primarykey]) ? "$path/$databasename/$dirtable/" . $values[$this->primarykey] . "/" . $field->name . "/" . $values[$field->name] : "";
                        $filethumb = isset($values[$this->primarykey]) ? "$path/$databasename/$dirtable/" . $values[$this->primarykey] . "/" . $field->name . "/thumbs/" . $values[$field->name] . ".jpg" : false;
                        if (file_exists($fileimage) && (isset($create_thumb[$key]) || ($filethumb && !file_exists($filethumb)))) {
                            $size = isset($field->thumbsize) ? $field->thumbsize : 22;
                            $size_w = isset($field->thumbsize_w) ? $field->thumbsize_w : "";
                            $size_h = isset($field->thumbsize_h) ? $field->thumbsize_h : "";
                            if ($size < 16)
                                $size = 16;
                            xmldb_create_thumb($fileimage, $size, $size_h, $size_w);
                        }
                    }
                    break;
            }
        }
    }
}

/**
 * driver xmlphp per Xmltable
 *
 */
class XMLTable_xmlphp
{

    var
        $databasename;
    var
        $tablename;
    var
        $primarykey;
    var
        $filename;
    var
        $indexfield;
    var
        $fields;

    function __construct(&$xmltable, $params = false)
    {
        $this->xmltable = &$xmltable;
        $this->tablename = &$xmltable->tablename;
        $this->databasename = &$xmltable->databasename;
        $this->fields = &$xmltable->fields;
        $this->path = &$xmltable->path;
        $this->numrecords = &$xmltable->numrecords;
        $this->usecachefile = &$xmltable->usecachefile;
        $this->filename = &$xmltable->filename;
        $this->indexfield = &$xmltable->indexfield;
        $this->primarykey = &$xmltable->primarykey;
        $this->driver = &$xmltable->driver;
        $this->xmldescriptor = &$xmltable->xmldescriptor;
        $this->xmlfieldname = &$xmltable->xmlfieldname;
        $this->datafile = &$xmltable->datafile;
        $this->xmltagroot = &$xmltable->xmltagroot;
        //propriera' relative a i file xml
        $path = $this->path;
        $databasename = $this->databasename;
        $tablename = $this->tablename;
        // dati su singolo file
        $this->filename = get_xml_single_element("filename", $this->xmldescriptor);
        if (is_array($params)) {
            foreach ($params as $k => $v) {
                $this->$k = $v;
            }
        }
        //dprint_r($this->datafile);
        return true;
    }

    /**
     * GetNumRecords
     * Torna il numero di records
     */
    function GetNumRecords($restr = null)
    {
        $cacheid = $restr;
        if (is_array($restr))
            $cacheid = implode("|", $restr);
        if ($restr == null)
            $cacheid = " ";
        $cacheid = md5($cacheid);
        if (isset($this->numrecordscache[$cacheid])) {
            return $this->numrecordscache[$cacheid];
        }
        $c = count($this->GetRecords($restr, false, false, false, false, $this->primarykey));
        $this->numrecordscache[$cacheid] = $c;
        if ($restr == null)
            $this->numrecords = $c;
        return $c;
    }

    function ClearCachefile()
    {
        if ($this->usecachefile != 1)
            return;
        $databasename = $this->databasename;
        $tablename = $this->tablename;
        $path = $this->path;
        $files = glob($cachefile = "$path/" . $databasename . "/cache/$tablename*");
        if (is_array($files))
            foreach ($files as $file) {
                @unlink($file);
            }
    }

    /**
     * GetRecords
     * recupera tutti i records
     */
    function GetRecords($restr = false, $min = false, $length = false, $order = false, $reverse = false, $fields = false)
    {

        $ret = false;
        $databasename = $this->databasename;
        $tablename = $this->tablename;
        $path = $this->path;
        $fieldname = $this->xmlfieldname;
        if ($order && !isset($this->fields[$order])) {
            $order = false;
        }

        if (is_array($fields)) {
            $fields = implode("|", $fields);
        }
        $tmf = "";
        if ($fields != false && is_array($restr)) {
            foreach ($restr as $key => $value)
                $fields .= "|$key";
        }
        $rc = $restr;
        if (is_array($restr))
            $rc = implode("|", $restr);
        if ($restr && is_string($restr)) {
            die("TODO xmldb: not yet implemented function for this driver");
        }

        //cache su file---->
        if ($this->usecachefile == 1) {
            $cacheindex = $rc . $min . $length . $order . $reverse . $fields;
            if (!file_exists("$path/" . $databasename . "/cache"))
                mkdir("$path/" . $databasename . "/cache");
            $cachefile = "$path/" . $databasename . "/cache/" . $tablename . "." . md5($cacheindex) . ".cache";
            if (file_exists($cachefile)) {
                $ret = file_get_contents($cachefile);
                $ret = @unserialize($ret);
                //dprint_r("[$cachefile]");
                //dprint_r ($ret);
                if ($ret !== false)
                    return $ret;
            }
        }
        //cache su file----<
        // filtro i field che non sono associati alla tabella
        if ($fields === false) {
            $fields = array();
            foreach ($this->fields as $v) {
                $fields[] = $v->name;
            }
            $fields = implode("|", $fields);
        }
        $all = xmldb_readDatabase($this->datafile, $fieldname, $fields, false);

        if ($all === false) //il file non esiste
        {
            return false;
        }
        if ($all === null) //errore lettura
        {
            return null;
        }
        //se il campo manca lo forzo a default oppure null
        foreach ($all as $k => $r) {
            foreach ($this->fields as $field) {
                if (!isset($r[$field->name]))
                    $r[$field->name] = isset($this->fields[$field->name]->defaultvalue) ? $this->fields[$field->name]->defaultvalue : null;
            }
            $all[$k] = $r;
        }
        if (is_array($restr)) {
            $ret = array();
            foreach ($all as $r) {
                $ok = true;
                foreach ($restr as $key => $value) {
                    //-----%xxx%------>
                    if (isset($restr[$key]) && preg_match("/^%/s", $restr[$key]) && preg_match('/%$/s', $restr[$key])) {

                        $t = xmldb_encode_preg(substr($restr[$key], 1, strlen($restr[$key]) - 2));
                        if (preg_match("/$t/is", $r[$key]) == false) {
                            $ok = false;
                            break;
                        }
                    } elseif (isset($restr[$key]) && preg_match("/%" . '$/is', $restr[$key])) {

                        $t = xmldb_encode_preg(substr($restr[$key], 0, strlen($restr[$key]) - 1));
                        if (preg_match("/^$t/is", $r[$key]) == false) {
                            $ok = false;
                            break;
                        }
                    } elseif (isset($restr[$key]) && preg_match("/^%/is", $restr[$key])) {
                        $t = xmldb_encode_preg(substr($restr[$key], 1));

                        if (preg_match("/" . $t . '$/is', $r[$key]) == false) {
                            $ok = false;
                            break;
                        }
                    }
                    //-----%xxx%------<
                    else {
                        if (!isset($r[$key]) || $r[$key] != $restr[$key]) {
                            $ok = false;
                            break;
                        }
                    }
                }
                if ($ok == true) {
                    $ret[] = $r;
                }
            }
        } else
            $ret = $all;
        //ordinamento dei records ------>

        if ($order !== false && $order !== "" && /*  isset($this->fields[$order]) && */ is_array($ret)) {
            $ret = xmldb_array_sort_by_key($ret, $order);

            /*
              $newret=array();
              foreach($ret as $key=> $value)
              {
              if (isset($value[$order]))
              {
              $i=0;
              $r=$value[$order]."0";
              while(isset($newret[$r.$i]))
              {
              $i++;
              }
              $newret["$r"."$i"]=$ret[$key];
              }
              else
              {
              $i=0;
              $r="";
              while(isset($newret[$r.$i]))
              {
              $i++;
              }
              $newret["$r"."$i"]=$ret[$key];
              }
              }
              ksort($newret);
              $ret=$newret;
             * */
        }
        if ($reverse) {
            $ret = array_reverse($ret);
        }
        //ordinamento dei records ------<
        // minimo e massimo
        if ($min != false && $length != false)
            $ret = array_slice($ret, $min - 1, $length);
        $ret = array_values($ret);
        //cache su file---->
        if ($this->usecachefile == 1) {
            $cachestring = serialize($ret);
            $fp = fopen($cachefile, "wb");
            fwrite($fp, $cachestring);
            fclose($fp);
        }
        //cache su file----<

        return $ret;
    }

    /**
     * GetRecord
     * recupera un singolo record
     *
     * @param array restrizione
     */
    function GetRecord($restr = false)
    {
        $rec = $this->GetRecords($restr, 0, 1);
        if (is_array($rec) && isset($rec[0])) {

            return $rec[0];
        }
        return null;
    }

    /**
     * GetRecordByUnirecid
     *
     * Torna un record in formato array partendo dall' unirecid (nomefile)
     * */
    function GetRecordByPrimaryKey($unirecid)
    {
        return $this->GetRecordByPk($unirecid);
    }

    /**
     * GetAutoincrement
     *
     * gestisce l' autoincrement di un campo della tabella
     *
     * @param string nome del campo
     * @return indice disponibile
     */
    function GetAutoincrement($field)
    {
        /*
          if (isset($this->maxautoincrement[$field]))
          {
          //dprint_r("Xai=".($this->maxautoincrement[$field] + 1));
          return $this->maxautoincrement[$field] + 1;
          }
         */
        $records = $this->GetRecords();
        $max = 0;
        $contrec = 0;
        if (is_array($records)) {
            foreach ($records as $rec) {
                $contrec++;
                if (isset($rec[$field]) && intval($rec[$field]) > intval($max))
                    $max = intval($rec[$field]);
            }
        }
        $this->numrecords = $contrec;
        //		return ($max + 1).uniqid(".");
        return $max + 1;
    }

    /**
     * InsertRecord
     * Aggiunge un record
     *
     * @param array $values
     * */
    function InsertRecord($values)
    {
        //dprint_r($values);
        $this->numrecords = -1;
        $this->numrecordscache = array();
        $databasename = $this->databasename;
        $tablename = $this->tablename;
        $path = $this->path;
        for ($tlock = time(); $this->dbIsLocked();) {
            if (time() - $tlock > _MAX_LOCK_TIME) {
                $this->dbunlock();
                return "error:table is locked";
            }
            usleep(rand(1, 500));
        }
        if (!$this->dblock()) {
            return "error:table lock failed";
        }
        if (isset($values[$this->primarykey])) {
            $t = $this->GetRecordByPrimaryKey($values[$this->primarykey]);
            if (is_array($t)) {
                $this->dbunlock();
                return "error:there is already a record with this primary key {$this->primarykey}={$values[$this->primarykey]}";
            }
        }
        foreach ($this->fields as $f) {
            if (!isset($values[$f->name]) || (isset($values[$f->name]) && $values[$f->name] == "")) {
                if (isset($this->fields[$f->name]->extra) && $this->fields[$f->name]->extra == "autoincrement") {
                    $newid = $this->GetAutoincrement($f->name);
                    $values[$f->name] = $newid;
                    $this->maxautoincrement[$f->name] = $newid;
                }
            }
            if ((!isset($values[$f->name]) || $values[$f->name] === null) && (isset($this->fields[$f->name]->defaultvalue) && $this->fields[$f->name]->defaultvalue != "")) {
                $dv = $this->fields[$f->name]->defaultvalue;
                $fname = $f->name;
                $rv = "";
                eval("\$rv=\"$dv\";");
                $rv = str_replace("\\", "\\\\", $rv);
                $rv = str_replace("'", "\\'", $rv);
                eval("\$values" . "['$fname'] = '$rv' ;");
            }
        }
        if (!isset($values[$this->primarykey]) || $values[$this->primarykey] == "") {
            $this->dbunlock();
            return "error:missing the primary key in table  $tablename";
        }
        // cerco il file da modificare o creare----->
        if (!preg_match("/\\/$/si", $this->datafile)) //datafile
            $xmltowritefullpath = $this->datafile;
        else {
            $unirecid = urlencode($values[$this->primarykey]);
            $xmltowritefullpath = "{$this->datafile}" . $unirecid . ".php"; //default
            if ($this->filename != "") {
                $xmltowritefullpath = "{$this->datafile}" . urlencode($this->filename) . ".php"; //filename
            }
            if ($this->indexfield != "" && isset($values[$this->indexfield])) {
                $xmltowritefullpath = "{$this->datafile}" . urlencode($values[$this->indexfield]) . ".php"; //indexfield
            }
        }
        // cerco il file da modificare o creare-----<
        // se esiste gia'
        if (file_exists($xmltowritefullpath)) {
            $readok = false;
            for ($i = 0; $i < _MAXTENTATIVIDIACCESSO; $i++) {
                $oldfilestring = file_get_contents($xmltowritefullpath);
                if (strpos($oldfilestring, "</{$this->xmltagroot}") !== false) {
                    $readok = true;
                    break;
                }
            }
            if (!$readok) {
                $this->dbunlock();
                return "error:insert record db is locked";
            }
            $str = "\t<{$this->xmlfieldname}>";
            foreach ($this->fields as $field) {
                $valtowrite = isset($values[$field->name]) ? $values[$field->name] : "";
                $valtowrite = xmlenc("$valtowrite");
                $str .= "\n\t\t<" . $field->name . ">" . $valtowrite . "</" . $field->name . ">";
            }
            $str .= "\n\t</{$this->xmlfieldname}>\n</{$this->xmltagroot}>";
            $newfilestring = preg_replace('/<\/' . $this->xmltagroot . '>$/s', xmldb_encode_preg_replace2nd($str), trim(ltrim($oldfilestring)));
            if (file_exists("$xmltowritefullpath") && !is_writable("$xmltowritefullpath")) {
                $this->dbunlock();
                return "error:not file writable";
            }
            $handle = fopen($xmltowritefullpath, "w");
            fwrite($handle, $newfilestring);
            fclose($handle);
        } else {
            $str = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<?php exit(0);?>";
            $str .= "\n<{$this->xmltagroot}>\n\t<{$this->xmlfieldname}>";
            foreach ($this->fields as $field) {
                $valtowrite = isset($values[$field->name]) ? $values[$field->name] : "";
                $valtowrite = xmlenc("$valtowrite");
                $str .= "\n\t\t<" . $field->name . ">" . $valtowrite . "</" . $field->name . ">";
            }
            $str .= "\n\t</{$this->xmlfieldname}>\n</{$this->xmltagroot}>";
            if (file_exists("$xmltowritefullpath") && !is_writable("$xmltowritefullpath")) {
                $this->dbunlock();
                return false;
            }
            if (!file_exists(dirname("$xmltowritefullpath")))
                mkdir(dirname("$xmltowritefullpath"));
            $handle = fopen($xmltowritefullpath, "w");
            fwrite($handle, $str);
            fclose($handle);
        }
        $this->xmltable->gestfiles($values);
        $this->dbunlock();
        $this->ClearCachefile();
        xmldb_readDatabase($xmltowritefullpath, $this->xmlfieldname, false, false);
        return $values;
    }

    /**
     * 
     * @return type
     */
    function dbIsLocked()
    {
        if ($this->tablename == "____empty_____")
            return false;
        if (file_exists("{$this->path}/{$this->databasename}/{$this->tablename}/lock")) {
            if (!empty($_GET['debug'])) {
                die("table is locked " . $this->tablename);
            }

            return true;
        }
        return false;
    }

    /**
     * 
     * @return type
     */
    function dblock()
    {
        if ($this->tablename == "____empty_____")
            return true;
        if (false !== ($fp = @fopen("{$this->path}/{$this->databasename}/{$this->tablename}/lock", "x"))) {
            fclose($fp);
            return true;
        }
        return false;
    }

    /**
     * 
     * @return type
     */
    function dbunlock()
    {
        if ($this->tablename == "____empty_____") {
            return true;
        }
        $r = unlink("{$this->path}/{$this->databasename}/{$this->tablename}/lock");
        return $r;
    }

    /**
     * elimina tutti i dati da una tabella
     */
    function Truncate()
    {
        $databasename = $this->databasename;
        $tablename = $this->tablename;
        $path = $this->path;
        $this->numrecords = -1;
        $this->numrecordscache = array();
        if ($tablename == "____empty_____") {
            if (!empty($this->datafile))
                unlink($this->datafile);
        }
        $oldfiles = glob("$path/$databasename/$tablename/*.php");
        xmldb_remove_dir_rec("$path/$databasename/$tablename");
        $this->ClearCachefile();
        foreach ($oldfiles as $oldfile)
            xmldb_readDatabase($oldfile, $this->xmlfieldname, false, false);
        return true;
    }

    /**
     * DelRecord
     * Elimina un record.
     * @param string $unirecid
     * <b>$values[$this->primarykey] deve essere presente</b>
     * @return array record appena inserito o null
     * */
    function DelRecord($pkvalue)
    {
        $databasename = $this->databasename;
        $tablename = $this->tablename;
        $path = $this->path;
        $this->numrecords = -1;
        $this->numrecordscache = array();
        $oldfile = $this->GetFileRecord($this->primarykey, $pkvalue);
        $dirold = dirname($oldfile) . "/" . basename($oldfile, ".php");
        if (!file_exists($oldfile))
            return false;
        if (preg_match("/\\/$/si", $this->datafile))
            if (!strpos($pkvalue, "..") !== false && file_exists("{$this->datafile}$pkvalue/") && is_dir("{$this->datafile}$pkvalue/"))
                xmldb_remove_dir_rec("{$this->datafile}$pkvalue");
        $this->ClearCachefile();
        $n = xmldb_readDatabase($oldfile, $this->xmlfieldname, false, false);
        // se e' l' ultimo record
        if (is_array($n) && count($n) == 1) {
            if (preg_match("/\\/$/si", $this->datafile)) {
                @unlink($oldfile);
                if (file_exists($oldfile) && is_dir($oldfile)) {
                    xmldb_remove_dir_rec($oldfile);
                }
            }
            xmldb_readDatabase($oldfile, $this->xmlfieldname, false, false);
            return true;
        }
        $pkey = $this->primarykey;
        $pvalue = $pkvalue;
        $readok = false;
        for ($i = 0; $i < _MAXTENTATIVIDIACCESSO; $i++) {
            if (!file_exists($oldfile)) //errore
                break;
            $oldfilestring = file_get_contents("$oldfile");
            if (strpos($oldfilestring, "</{$this->xmltagroot}>") !== false) {
                $readok = true;
                break;
            }
        }
        if (!$readok) {
            return false;
        }
        $oldfilestring = xmldb_removexmlcomments($oldfilestring);
        $strnew = "";
        $newfilestring = preg_replace('/<' . $this->xmlfieldname . '>([^(' . $this->xmlfieldname . ')]*)<' . $pkey . '>' . $pvalue . '<\/' . $pkey . '>(.*?)<\/' . $this->xmlfieldname . '>/s', $strnew, $oldfilestring);
        $newfilestring = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<?php exit(0);?>\n" . trim($newfilestring, "\n ");
        $file = fopen($oldfile, "w");
        fwrite($file, $newfilestring);
        fclose($file);
        $this->ClearCachefile();
        xmldb_readDatabase($oldfile, $this->xmlfieldname, false, false);
        return true;
    }

    /**
     * GetFileRecord
     * torna il nome del file che contiene il record
     * @param string $pkey
     * @param string $pvalue
     */
    function GetFileRecord($pkey, $pvalue)
    {
        $databasename = $this->databasename;
        $tablename = $this->tablename;
        $path = $this->path;

        //$pvalue=urlencode($pvalue);
        if (!preg_match("/\\/$/si", $this->datafile)) {
            return $this->datafile;
        }
        //guardo prima quelo con la chiave primaria
        if (file_exists($this->datafile . "/" . urlencode($pvalue) . ".php")) {
            $data = file_get_contents($this->datafile . "/" . urlencode($pvalue) . ".php");
            $data = xmldb_removexmlcomments($data);
            if (preg_match('/<' . $tablename . '>(.*)<' . $pkey . '>' . xmlenc(xmldb_encode_preg($pvalue)) . '<\/' . $pkey . '>/s', $data)) {
                $this->cache_filerecord[$pvalue] = $this->datafile . "/" . urlencode($pvalue) . ".php";
                return $this->datafile . "/" . urlencode($pvalue) . ".php";
            }
        }

        //cerco in tutti i files
        $pvalue = xmlenc($pvalue);
        $pvalue = xmldb_encode_preg($pvalue);
        if (!file_exists($this->datafile))
            return false;
        $handle = opendir($this->datafile);
        while (false !== ($file = readdir($handle))) {
            $tmp2 = null;
            if (preg_match('/.php$/s', $file) and !is_dir($this->datafile . "/$file")) {
                $data = file_get_contents($this->datafile . "/$file");
                $data = xmldb_removexmlcomments($data);
                ///dprint_xml('/<' . $pkey . '>' . $pvalue . '<\/' . $pkey . '>/s');
                //dprint_r($this->datafile . "/$file");
                if (preg_match('/<' . $pkey . '>' . $pvalue . '<\/' . $pkey . '>/s', $data)) {
                    $this->cache_filerecord[$pvalue] = $this->datafile . "/$file";
                    return $this->datafile . "/$file";
                }
            }
        }

        return false;
    }

    /**
     * GetRecordByPk
     * torna il record passandogli la chiave primaria
     * @param string $pvalue valore chiave
     */
    function GetRecordByPk($pvalue)
    {
        $pkey = $this->primarykey;
        $databasename = $this->databasename;
        $tablename = $this->tablename;
        $path = $this->path;
        //cache su file---->
        if (!is_array($pkey) && $this->usecachefile == 1) {
            $cacheindex = $pvalue;
            if (!file_exists("$path/" . $databasename . "/cache"))
                mkdir("$path/" . $databasename . "/cache");
            $cachefile = "$path/" . $databasename . "/cache/" . $tablename . "." . urlencode($pvalue) . ".cache";
            if (file_exists($cachefile)) {
                $ret = file_get_contents($cachefile);
                $ret = @unserialize($ret);
                if ($ret !== false)
                    return $ret;
            }
        }
        //cache su file----<
        $old = $this->GetFileRecord($pkey, $pvalue);
        //dprint_r("$pkey.$old.$pvalue");
        $values = xmldb_readDatabase($old, $this->xmlfieldname);
        $ret = false;
        $found = false;
        if (!is_array($values)) {
            return $values;
        }
        foreach ($values as $value) {
            if ($value[$pkey] == ($pvalue)) {
                $found = true;
                $ret = $value;
                break;
            }
        }
        //riempo i campi che mancano
        if ($found)
            foreach ($this->fields as $field) {
                if (!isset($ret[$field->name]))
                    $ret[$field->name] = isset($field->defaultvalue) ? $field->defaultvalue : null;
            }
        //cache su file---->
        if ($this->usecachefile == 1) {
            $cachestring = serialize($ret);
            $fp = fopen($cachefile, "wb");
            fwrite($fp, $cachestring);
            fclose($fp);
        }
        //cache su file----<
        return $ret;
    }

    /**
     * UpdateRecordBypk
     * aggiorna il record passandogli la chiave primaria
     * @param array $values
     * @param string $pkey
     * @param string $pvalue
     */
    function UpdateRecordBypk($values, $pkey, $pvalue)
    {
        if (!isset($values[$pkey]))
            $values[$pkey] = $pvalue;
        $databasename = $this->databasename;
        $tablename = $this->tablename;
        $path = $this->path;
        $strnew = ""; {
            $old = $this->GetFileRecord($pkey, $pvalue);
            if (!file_exists($old))
                return false;
            //$oldfilestring = file_get_contents($old);
            $readok = false;
            for ($i = 0; $i < _MAXTENTATIVIDIACCESSO; $i++) {
                $oldfilestring = file_get_contents($old);
                if (strpos($oldfilestring, "</") !== false) {
                    $readok = true;
                    break;
                }
            }
            if (!$readok) {
                return "error update";
            }

            $oldfilestring = xmldb_removexmlcomments($oldfilestring);
            $oldvalues = $newvalues = $this->GetRecordByPk($pvalue);
            if (isset($values[$this->primarykey]) && $values[$this->primarykey] != $pvalue) {
                $tatget = $this->GetRecordByPk($values[$this->primarykey]);
                if ($tatget) {
                    return "duplicate primarykey";
                }
            }
            //dprint_r($oldvalues);
            //die();
            foreach ($values as $key => $value) {
                $newvalues[$key] = $value;
            }
            $oldvalues[$this->primarykey] = $pvalue;
            $this->xmltable->gestfiles($values, $oldvalues);
            //compongo il nuovo xml per il record da aggiornare
            $strnew = "<{$this->xmlfieldname}>";
            foreach ($newvalues as $key => $value) {
                if (is_array($value)) {
                    error_log("$value is not array");
                }
                $strnew .= "\n\t\t<$key>" . xmlenc("$value") . "</$key>";
            }
            $strnew .= "\n\t</{$this->xmlfieldname}>";
            $strnew = xmldb_encode_preg_replace2nd($strnew);
            $pvalue = xmlenc($pvalue);
            $pvalue = xmldb_encode_preg($pvalue);
            $newfilestring = preg_replace('/<' . $this->xmlfieldname . '>([^(' . $this->xmlfieldname . ')]*)<' . $pkey . '>' . $pvalue . '<\/' . $pkey . '>(.*?)<\/' . $this->xmlfieldname . '>/s', $strnew, $oldfilestring);
            $newfilestring = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<?php exit(0);?>\n" . trim(ltrim($newfilestring));
            if (!is_writable($old)) {
                echo ("$old is readonly,I can't update");
                return ("$old is readonly,I can't update");
            }
            $handle = fopen($old, "w");
            fwrite($handle, $newfilestring);
            $this->ClearCachefile();
            $newvalues = xmldb_readDatabase($old, $this->xmlfieldname, false, false); //aggiorna la cache
            $newvalues = $this->GetRecordByPk($values[$pkey]);

            if (!isset($newvalues[$pkey]))
                return false;
            return $newvalues;
        }
    }
}

/**
 * 
 * @param type $image
 * @param type $filename
 */
function xmldb_image_fix_orientation(&$image, $filename)
{
    if (function_exists("exif_read_data")) {
        $exif = @exif_read_data($filename);
        if (!empty($exif['Orientation'])) {
            switch ($exif['Orientation']) {
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
    } else {
    }
}
