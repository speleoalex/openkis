<?php

//------rilievi--------<
function fix_charset($text)
{
    global $_FN;
    $text = htmlentities($text, ENT_QUOTES | ENT_IGNORE, $_FN['charset_page']);
    $text = utf8_decode($text);
    $text = str_replace("&npsp;", " ", $text);
    return $text;
}

function get_html($x, $y, $text, $style = "")
{
    global $_FN;

    $text = fix_charset($text, ENT_QUOTES | ENT_IGNORE, $_FN['charset_page']);

    $text = str_replace("\n", "<br />", $text);
    $text = str_replace("\n", "<br />", $text);
    $text = str_replace("&ldquo;", "'", $text);
    $text = str_replace("&rdquo;", "'", $text);

    return "<div style=\"color:#000000;position:absolute;left:{$x}px;top:{$y}px;$style\">$text</div>";
}

function get_html_bottom_right($x, $y, $text, $style = "")
{
    global $_FN;

    $text = fix_charset($text, ENT_QUOTES | ENT_IGNORE, $_FN['charset_page']);

    $text = str_replace("\n", "<br />", $text);
    $text = str_replace("\n", "<br />", $text);
    $text = str_replace("&ldquo;", "'", $text);
    $text = str_replace("&rdquo;", "'", $text);

    return "<div style=\"color:#000000;position:absolute;right:{$x}px;bottom:{$y}px;$style\">$text</div>";
}

function get_html2($x, $y, $text, $style = "")
{
    global $_FN;
    return "<div style=\"color:#000000;position:absolute;left:{$x}px;top:{$y}px;$style\">$text</div>";
}

function get_html_pdf($id)
{
    global $_FN;
    $databasename = "fndatabase";
    $tablename = "ctl_caves";
    $pathdatabase = "misc";
    $Table = xmldb_frm($databasename, $tablename, $pathdatabase, $_FN['lang'], $_FN['languages']);
    $record = $Table->xmltable->GetRecord(array("id" => $id));

//dprint_r($record);
//
//
//
    //calcolo il numero di pagine della scheda -------------------------------->
    $numeropagina = 1;
    $totalepagine = 2;
    $tablerilievi = new XMLTable("fndatabase", "ctl_surveys", $_FN['datadir']);
    $rilievi = $tablerilievi->GetRecords(array("codecave" => $record['code']));

    $table = new XMLTable("fndatabase", "ctl_bibliograpy", $_FN['datadir']);
    $bibliografia = $table->GetRecords(array("codecaves" => '%' . ",{$record['code']}," . '%'));

    $totalepagine += count($rilievi);
    if (!empty($record['description']))
    {
        $totalepagine++;
    }
    $strcens = array();
    if ($record['marine'] == "S")
        $strcens[] = "marina";
    if ($record['archeological'] == "S")
        $strcens[] = "archeologica";
    if ($record['environmentalrisk'] == "S")
        $strcens[] = "a rischio ambientale";
    if ($record['closed'] == "S")
        $strcens[] = "chiusa";
    if ($record['destroyed'] != "")
        $strcens[] = "distrutta";
    $strcens = implode(", ", $strcens);

    if (!empty($record['itinerary']) || count($bibliografia) > 0 || $record['notes'] != "" || $strcens != "")
    {
        $totalepagine++;
    }
    if (!empty($record['associations']) || $record['fauna'] != "" || $record[chronology] != "")
    {
        $totalepagine++;
    }
    if (isset($record['longitude']) && isset($record['latitude']) && $record['longitude'] != "" && $record['latitude'] != "")
    {
        $totalepagine++;
    }
    if (isset($record['photo1']) && $record['photo1'] != "")
    {
        $photo1 = "misc/fndatabase/ctl_caves/{$record['id']}/photo1/{$record['photo1']}";
        if (file_exists($photo1))
        {
            $totalepagine++;
        }
    }

    //calcolo il numero di pagine della scheda --------------------------------<
//---------Scheda catasto PAGINA 1--------------->
    $html = "<style type=\"text/css\">
<!--
img {
	border:1px solid #dadada;	
}
div {
	font-size:12px;
}
-->
</style>
<page>
<div style=\"position:absolute;top:0px;left:0px;height:1124px;width:826px;background-image:url({$_FN['siteurl']}/extra/openkis/custom/liguria/pdf/scheda1.png)\">";
    $footertext = "LI{$record['code']} estratta da www.catastogrotte.net " . strtolower(FN_FormatDate(time())) . " ";



    $html .= get_html(60, 309, $record['name'], "");
    $html .= get_html(60, 425, $record['synonyms'], "");
    $html .= get_html(179, 1014, $record['depth_positive'], "");
    $html .= get_html(442, 1014, $record['depth_negative'], "");
    $html .= get_html(660, 1014, $record['depth_total'], "");
    $html .= get_html(625, 130, "LI", "");
    $html .= get_html(716, 130, $record['provincia'], "");
    if ($record['mount'] == "0")
        $record['mount'] = "-";
    if ($record['valley'] == "0")
        $record['valley'] = "-";
    $html .= get_html(60, 510, $record['comune'], "");
    $html .= get_html(60, 570, $record['localita'], "");
    $html .= get_html(60, 624, $record['mount'], "");
    $html .= get_html(60, 690, $record['valley'], "");
    if ($record['lenght_planimetric'] == 0)
        $record['lenght_planimetric'] = "-";
    if ($record['lenght_extension'] == 0)
        $record['lenght_extension'] = "-";
    if ($record['lenght_total'] == 0)
        $record['lenght_total'] = "-";
    $html .= get_html(179, 933, $record['lenght_total'], "");
    $html .= get_html(470, 933, $record['lenght_planimetric'], "");
    $html .= get_html(690, 933, $record['lenght_extension'], "");
    $html .= get_html(625, 190, $record['code'], "");
    $d = strtotime($record['recordupdate']);
    $html .= get_html(625, 255, date("d/m/Y", $d));
//$html .= get_html(60,748,$record['code'],"");
    $tmpt = xmldb_frm($databasename, "ctl_areas", $pathdatabase, $_FN['lang'], $_FN['languages']);
    $rec = $tmpt->xmltable->GetRecord(array("code" => $record['code']));
    if (isset($rec['name']))
        $html .= get_html(60, 748, $rec['code'] . " - " . $rec['name'], "");
    $tmpt = xmldb_frm($databasename, "ctl_geologicalformations", $pathdatabase, $_FN['lang'], $_FN['languages']);
    $html .= get_html(60, 810, $record['geologicalformation'], "");
    $rec = $tmpt->xmltable->GetRecord(array("geologicalformation" => $record['geologicalformation']));
    if (isset($rec['FM']))
        $html .= get_html(156, 810, $rec['FM'], "");
    //$html.=get_html(446,810,$record['AGE'],"");

    $html .= get_html_bottom_right(50, 11, $footertext . " Pagina $numeropagina/$totalepagine");
    $numeropagina++;
    $html .= "</div>\n</page>";
//---------Scheda catasto PAGINA 1---------------<

//---------Scheda catasto PAGINA 2--------------->
    $html .= "\n<page>
<div style=\"position:absolute;top:0px;left:0px;height:1124px;width:826px;background-image:url({$_FN['siteurl']}/extra/openkis/custom/liguria/pdf/scheda2.png)\" >";
    $html .= get_html(60, 146, $record['coordinates_type'], "");
    $html .= get_html(444, 146, $record['map_denomination'], "");
    $html .= get_html(60, 220, $record['location_evaluation'] . "", "");
    $html .= get_html(570, 182, $record['latitude_txt'], "");
    $html .= get_html(570, 146, $record['longitude_txt'], "");
    $html .= get_html(502, 146, $record['elevation'], "");
    $html .= get_html(502, 182, $record['elevation_map'], "");
    $html .= get_html(502, 220, $record['elevation_gps'], "");
    $html .= get_html(60, 686, $record['hydrology'], "");
    $html .= get_html(60, 284, $record['notes'], "width:700px;");
    $html .= get_html(60, 830, $record['wells'], "");
    $html .= get_html(60, 724, $record['trend'], "");
    $html .= get_html(60, 760, $record['practicability'], "");
    $html .= get_html(60, 990, $record['firstreference'], "");
    $html .= get_html_bottom_right(50, 11, $footertext . " Pagina $numeropagina/$totalepagine");
    $numeropagina++;

    $html .= "</div>\n</page>";
//---------Scheda catasto PAGINA 2---------------<
//---------Scheda catasto PAGINA 3--------------->
    if (!empty($record['description']))
    {
        $html .= "\n<page>
<div style=\"text-align: justify;position:absolute;top:0px;left:0px;height:1124px;width:826px;background-image:url({$_FN['siteurl']}/extra/openkis/custom/liguria/pdf/scheda3.png)\" >";
        $fsize = "12px";
        $len = strlen($record['description']);
        if ($len > 8000)
        {
            $fsize = "8px";
        }
        if ($len > 16000)
        {
            $fsize = "6px";
        }
//$record['description'] = substr($record['description'] ,0,135);
//die ($record['description']);
        $html .= get_html(51, 132, "" . $record['description'], "display:block;height:920px;width:700px;font-size:{$fsize};");
        $html .= get_html_bottom_right(50, 11, $footertext . " Pagina $numeropagina/$totalepagine");
        $numeropagina++;

        $html .= "</div>\n</page>";
    }
//---------Scheda catasto PAGINA 3--------------->

    if (!empty($record['itinerary']) || count($bibliografia) > 0 || $record['notes'] != "" || $strcens != "")
    {
//---------Scheda catasto PAGINA 4--------------->
        $html .= "\n<page><div style=\"text-align: justify;position:absolute;top:0px;left:0px;height:1124px;width:826px;background-image:url({$_FN['siteurl']}/extra/openkis/custom/liguria/pdf/scheda4.png)\">";
        $html .= get_html(51, 140, $record['itinerary'], "display:block;height:920px;width:700px;");
//-----BIBLIOGRAFIA-------------------------->
        dprint_r($bibliografia);
        if ($bibliografia && count($bibliografia) > 0)
        {
            $thtml = "";
            foreach ($bibliografia as $bib)
            {
                $thtml .= "<b>" . fix_charset($bib['Rivista']) . "</b>";
                $thtml .= " " . fix_charset($bib['Titolo']) . "";
                if ($bib['Anno'] != "")
                    $thtml .= " , anno:" . fix_charset($bib['Anno']) . " ";
                if ($bib['Autori'] != "")
                    $thtml .= " <em>" . fix_charset($bib['Autori']) . "</em>";
                $thtml .= " | ";
            }
            $thtml .= "";
            $fsize = "12px";
            $len = strlen(strip_tags($thtml));
            if ($len > 3000)
            {
                $fsize = "8px";
            }
            if ($len > 6000)
            {
                $fsize = "6px";
            }

            $html .= get_html2(51, 490, $thtml, "display:block;height:250px;width:700px;font-size:{$fsize};");
        }
//-----BIBLIOGRAFIA--------------------------<
        $html .= get_html(314, 970, $strcens, "");
        $html .= get_html(314, 1012, $record['notes'], "display:block;height:110px;width:460px;");
        $html .= get_html_bottom_right(50, 11, $footertext . " Pagina $numeropagina/$totalepagine");
        $numeropagina++;

        $html .= "</div>\n</page>";
//---------Scheda catasto PAGINA 4---------------<
    }
    if (!empty($record['associations']) || $record['fauna'] != "" || $record[chronology] != "")
    {
//---------Scheda catasto PAGINA 5--------------->
        $html .= "\n<page><div style=\"text-align: justify;position:absolute;top:0px;left:0px;height:1124px;width:826px;background-image:url({$_FN['siteurl']}/extra/openkis/custom/liguria/pdf/scheda5.png)\">";
        $html .= get_html(51, 140, $record['associations'], "display:block;height:110px;width:700px;");
        $html .= get_html(51, 300, $record['fauna'], "display:block;height:110px;width:700px;");
        $html .= get_html(51, 600, $record['chronology'], "display:block;height:110px;width:700px;");
        $html .= get_html_bottom_right(50, 11, $footertext . " Pagina $numeropagina/$totalepagine");
        $numeropagina++;
        $html .= "</div>\n</page>";
//---------Scheda catasto PAGINA 5---------------<
    }
    
//---------------mappa--------------------------->

    if (isset($record['longitude']) && isset($record['latitude']) && $record['longitude'] != "" && $record['latitude'] != "")
    {
        $projection = "EPSG%3A4326";
        $SRS = "EPSG%3A4326";


        if (false !== strpos(strtolower($record['coordinates_type']), "igm"))
        {
            //http://wms.pcn.minambiente.it/ogc?map=/ms_ogc/WMS_v1.3/raster/IGM_25000.map&service=wms&request=getCapabilities&version=1.3.0

            $wms_base = "http://wms.pcn.minambiente.it/ogc?map=/ms_ogc/WMS_v1.3/raster/IGM_25000.map&LAYERS=CB.IGM25000&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetMap&STYLES=&";
            $layer = "CB.IGM25000"; //CB.IGM25000.32
            //         $layer="CB.IGM25000.32"; //CB.IGM25000.32
            //ok://   http://bbsuite.nivolacloud.com:8080/geoserver/IGM/wms?map=/ms_ogc/WMS_v1.3/raster/IGM_25000.map&SERVICE=WMS&VERSION=1.3.0&REQUEST=GetMap&FORMAT=image%2Fjpeg&TRANSPARENT=true&LAYERS=igm25000_256&PROJECTION=EPSG%3A900913&SRS=EPSG%3A900913&WIDTH=512&HEIGHT=512&CRS=EPSG%3A900913&STYLES=&FORMAT_OPTIONS=dpi%3A180&BBOX=842641.7998157814%2C5435895.703528594%2C842947.5479289221%2C5436201.451641735
            //ko://   http://bbsuite.nivolacloud.com:8080/geoserver/IGM/wms?map=/ms_ogc/WMS_v1.3/raster/IGM_25000.map&SERVICE=WMS&VERSION=1.3.0&REQUEST=GetMap&LAYERS=igm25000_256&PROJECTION=EPSG%3A4326&FORMAT=image%2Fjpeg&SRS=EPSG%3A4326&BBOX=8.83587403,44.42787673,8.85587403,44.44787673&WIDTH=300&HEIGHT=300
            //$wms_base="http://bbsuite.nivolacloud.com:8080/geoserver/IGM/wms?map=/ms_ogc/WMS_v1.3/raster/IGM_25000.map&SERVICE=WMS&VERSION=1.3.0&REQUEST=GetMap&STYLES=&";
            //$layer="igm25000_256"; //CB.IGM25000.32


            $offset = 0.01;
            $h = "900";
            $w = "700";
        }
        else
        {
            $wms_base = "http://www.cartografiarl.regione.liguria.it/MapServer/mapserv.exe?MAP=E:\\Progetti\\mapfiles\\repertoriocartografico\\CARTE_TECNICHE\\1238GC.map&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetMap&STYLES=&";
            $layer = "M1238";
            $offset = 0.005;
            $h = "900";
            $w = "700";
        }

//6.3349900000000003,35.0343000000000018 : 19.8408000000000015,47.3108999999999966

        $coordinate = openkis_GetItemPosition($record);
        //dprint_r($coordinate);
        $lat = $coordinate['lat'];
        $lon = $coordinate['lon'];

        $b1 = $lon - $offset;
        $b2 = $lat - $offset;
        $b3 = $lon + $offset;
        $b4 = $lat + $offset;

        $format = "image%2Fjpeg";

//	$options = "LAYERS=$layer&PROJECTION=$projection&FORMAT=$format&SRS=$SRS&BBOX=$b1,$b2,$b3,$b4&WIDTH=$w&HEIGHT=$h";
        $options = "LAYERS=$layer&PROJECTION=$projection&FORMAT=$format&SRS=$SRS&BBOX=$b1,$b2,$b3,$b4&WIDTH=$w&HEIGHT=$h";
        $url = $wms_base . $options;
        //dprint_r($record['coordinates_type']);
        //die("$url<br /><iframe src=\"$url\" heighr=\"200\" width=\"200\"></iframe>");
//---------------mappa---------------------------<
        $fname = md5($url) . ".jpg";
//dprint_xml($html);
        if (!file_exists("misc/_cache/_THUMBS/"))
        {
            mkdir("misc/_cache/_THUMBS/");
        }
        if (!file_exists("misc/_cache/_THUMBS/$fname") || @!getimagesize("misc/_cache/_THUMBS/$fname"))
        {
            $tmpstr=getWebPage($url);

            FN_Write($tmpstr, "misc/_cache/_THUMBS/$fname");
        }



        $html .= "<page><br /><br /><br /><br /><div style=\"position:relative;margin-left:40px\"><img style=\"position:absolute;top:0px;left:0px;\" src=\"{$_FN['siteurl']}/misc/_cache/_THUMBS/$fname\" />
	<div style=\"position:absolute;top:450px;left:350px;color:red;font-size:20px;\">o</div>
	</div>";
        $html .= get_html(40, 30, "Cartografia:");
        $html .= get_html(40, 980, "Coordinate convertte:");
        $html .= get_html(40, 1000, "Geografiche WGS84:");
        $html .= get_html(300, 1000, round($lon, 5) . " E");
        $html .= get_html(360, 1000, round($lat, 5) . " N");
        $html .= get_html(40, 1020, "Chilometriche WGS84:");
        $html .= get_html(300, 1020, $coordinate['x'] . " E");
        $html .= get_html(360, 1020, $coordinate['y'] . " N");
        $html .= get_html(430, 1020, $coordinate['zone']);

        $html .= get_html_bottom_right(50, 11, $footertext . " Pagina $numeropagina/$totalepagine");
        $numeropagina++;
        $html .= "</page>";
    }

    if (isset($record['photo1']) && $record['photo1'] != "")
    {
        $photo1 = "misc/fndatabase/ctl_caves/{$record['id']}/photo1/{$record['photo1']}";
        if (file_exists($photo1))
        {
            $html .= "<page><br /><br /><br /><br /><div style=\"position:relative;margin-left:40px\"><img style=\"position:absolute;top:0px;left:0px;width:600px;\" src=\"$photo1\" />
	</div>";
            $html .= get_html(40, 30, "Immagine ingresso:");
            $html .= get_html(430, 1020, $record['authorphoto1']);
            $html .= get_html_bottom_right(50, 11, $footertext . " Pagina $numeropagina/$totalepagine");
            $numeropagina++;
            $html .= "</page>";
        }
    }

//------rilievi-------->
    if (true)
    {
        if (count($rilievi) > 0)
        {
            foreach ($rilievi as $rilievo)
            {
                if (isset($rilievo['photo1']))
                {
                    $filename = "misc/fndatabase/ctl_surveys/{$rilievo['id']}/photo1/{$rilievo['photo1']}";
                    if (file_exists($filename) && !is_dir($filename))
                    {
                        //gestione cambio orientamento pagina ---->
                        list($width, $height, $type, $attr) = getimagesize($filename);
                        if (!$width)
                        {
                            echo "<p>$filename - errore immagine</p>";
                        }
                        if ($height < $width)
                        {
                            //orizzontale
                            $maxh = 700;
                            $maxw = 900;
                            $newdim = get_dim($height, $width, $maxh, $maxw);
                            $html .= "\n<page orientation=\"paysage\"  format=\"A4\" >";
                        }
                        else
                        {
                            //verticale
                            $maxh = 900;
                            $maxw = 700;
                            $html .= "\n<page orientation=\"portrait\"  format=\"A4\" >";
                        }
                        $newdim = get_dim($height, $width, $maxh, $maxw);
                        if ($rilievo['NAME'] != "")
                        {
                            $html .= "<div style=\"margin-top:20px;margin-left:40px;border:1px solid #dadada;width:auto;padding:4px;\">{$rilievo['name']}</div>";
                        }
                        else
                        {
                            $html .= "<br />";
                        }
                        $html .= "<div style=\"text-align:center;margin:5px;\">";
                        $html .= "<img border=\"1\" height=\"{$newdim['h']}\"  width=\"{$newdim['w']}\" src=\"$filename\"  />";
                        $html .= "</div>";
                        //---in basso ---->
                        if ($rilievo['accuracy'] != "" || $rilievo['author'] != "" || $rilievo['date'] != "" || $rilievo['description'] != "")
                        {
                            $html .= "<div style=\"position:absolute;bottom:30px;left:40px;border:1px solid #dadada;width:600px;padding:4px;\">";
                            if ($rilievo['accuracy'] != "")
                                $html .= "<b>Precisione rilievo: </b>" . strtoupper($rilievo['accuracy']) . "&nbsp; &nbsp; &nbsp;";
                            if ($rilievo['author'] != "")
                                $html .= "<b>Autore: </b>" . strtoupper($rilievo['author']) . "<br />";
                            if ($rilievo['date'] != "")
                                $html .= "<b>Data: </b>" . $rilievo['date'] . "&nbsp; &nbsp; &nbsp;";
                            if ($rilievo['description'] != "")
                                $html .= "<div style=\"width:600px;\">" . strtoupper($rilievo['description']) . "</div>";
                            $html .= "</div>";
                        }
                        $html .= get_html_bottom_right(50, 11, $footertext . " Pagina $numeropagina/$totalepagine");
                        $numeropagina++;
                        //---in basso ----<
                        $html .= "\n</page>";
                        //gestione cambio orientamento pagina ----<
                    }
                }
            }
        }
    }

    return $html;
}

/**
 * 
 * @param $height
 * @param $width
 * @param $maxh
 * @param $maxw
 */
function get_dim($height, $width, $maxh, $maxw)
{
    $new_height = $height;
    if ($maxw != "" && $width >= $maxw)
    {
        $new_width = $maxw;
        $new_height = $height * ($new_width / $width);
    }
    //se troppo alta
    if ($maxh != "" && $new_height >= $maxh)
    {
        $new_height = $maxh;
        $new_width = $width * ($new_height / $height);
    }
    // se l' immagine e gia piccola
    if ($maxw != "" && $maxh != "" && $width <= $maxw && $height <= $maxh)
    {
        $new_width = $width;
        $new_height = $height;
    }
    return array("h" => $new_height, "w" => $new_width);
}

/**
 *
 * @global type $_FN
 * @param type $url
 * @return type 
 */
function getWebPage($url)
{
    //if (!empty($_GET['debug']))
    //    die("<a href=\"$url\">" . $url . "</a>");
    $options = array(
        CURLOPT_RETURNTRANSFER => true, // ritorna la pagina
        CURLOPT_HEADER => false, // non ritornare l'header
        // CURLOPT_REFERER => $url,      // settiamo il referer
        CURLOPT_FOLLOWLOCATION => true, // seguiamo i redirects
        // CURLOPT_ENCODING => FN_i18n("_CHARSET"), // tutti gli encodings
        CURLOPT_USERAGENT => "Mozilla/5.0 (X11; Linux x86_64; rv:2.0.1) Gecko/20100101 Firefox/4.0.1", // L'identit� del browser
        CURLOPT_AUTOREFERER => true, // setta il referer nel redirect
        CURLOPT_CONNECTTIMEOUT => 120, // timeout sulla connessione
        CURLOPT_TIMEOUT => 120, // timeout sulla risposta
        CURLOPT_MAXREDIRS => 10, // fermati dopo il decimo redirect
    );
    $ch = curl_init($url);     // impostiamo l'url per il download
    curl_setopt_array($ch, $options);   //settiamo le opzioni
    $content = curl_exec($ch);    //facciamo richiesta della pagina
    $err = curl_errno($ch);
    $errmsg = curl_error($ch);
    $header = curl_getinfo($ch);
    curl_close($ch);

    $header['errno'] = $err;   //eventuali errori
    $header['errmsg'] = $errmsg;  //header
    $header['content'] = $content;   //il contenuto della pagina quello che ci interessa
    return $header['content'];
}

?>
