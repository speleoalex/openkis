<?php

function fix_charset($text)
{
    global $_FN;
    $text = htmlentities($text, ENT_QUOTES | ENT_IGNORE, $_FN['charset_page']);
    mb_convert_encoding($text, 'UTF-8', 'ISO-8859-1');
    $text = str_replace("&npsp;", " ", $text);
    return $text;
}

// ========================================================================
// Funzioni helper condivise
// ========================================================================

function pdf_get_cave_data($id)
{
    global $_FN;
    $Table = FN_XMDBForm("ctl_caves");
    $record = $Table->xmltable->GetRecord(array("id" => $id));

    $tablerilievi = FN_XMDBTable("ctl_surveys");
    $rilievi = $tablerilievi->GetRecords(array("codecave" => $record['code']));

    // Bibliografia: usa FNDBVIEW con la stessa query LIKE del viewfooter
    $bibliografia = array();
    $bibconfig = FN_LoadConfig("", "bibliography");
    if ($bibconfig) {
        $bibview = new FNDBVIEW($bibconfig);
        $bibparams = array();
        $code = $record['code'];
        $bibparams['appendquery'] = "codecaves LIKE '{$code}' OR codecaves LIKE '{$code},%' OR codecaves LIKE '%,{$code},%' OR codecaves LIKE '%,{$code}' ";
        $bibparams['fields'] = "id,title,authors,year";
        $bibliografia = $bibview->GetResults(false, $bibparams);
        if (is_array($bibliografia)) {
            $bibliografia = FN_ArraySortByKey($bibliografia, "year");
        } else {
            $bibliografia = array();
        }
    }

    // Censimenti
    $strcens = array();
    if ($record['marine'] == "S") $strcens[] = "Cavit&agrave; marina";
    if ($record['archeological'] == "S") $strcens[] = "Cavit&agrave; archeologica";
    if ($record['environmentalrisk'] == "S") $strcens[] = "Rischio ambientale";
    if ($record['closed'] == "S") $strcens[] = "Chiusa";
    if ($record['destroyed'] != "") $strcens[] = "Distrutta";
    if (!empty($record['tourist']) && $record['tourist'] == 'S') $strcens[] = "Grotta turistica";
    if (!empty($record['lake']) && $record['lake'] == 'S') $strcens[] = "Cavit&agrave; lacustre";

    // Area carsica
    $tmpt = @xmetadb_frm($databasename, "ctl_areas", $pathdatabase, $_FN['lang'], $_FN['languages']);
    $areaRec = $tmpt ? $tmpt->xmltable->GetRecord(array("code" => $record['code'])) : null;
    $areaName = (isset($areaRec['name'])) ? $areaRec['code'] . " - " . $areaRec['name'] : '';

    // Formazione geologica
    $tmptGeo = @xmetadb_frm($databasename, "ctl_geologicalformations", $pathdatabase, $_FN['lang'], $_FN['languages']);
    $geoRec = $tmptGeo ? $tmptGeo->xmltable->GetRecord(array("geologicalformation" => $record['geologicalformation'])) : null;
    $geoFM = (isset($geoRec['FM'])) ? $geoRec['FM'] : '';
    $geoValue = $record['geologicalformation'];
    if ($geoFM) $geoValue .= ' - ' . $geoFM;

    $d = strtotime($record['recordupdate']);

    // Foto ingresso
    $photo1 = '';
    $photo1path = '';
    if (isset($record['photo1']) && $record['photo1'] != '') {
        $photo1path = "{$_FN['datadir']}/fndatabase/ctl_caves/{$record['id']}/photo1/{$record['photo1']}";
        if (file_exists($photo1path)) {
            $photo1 = "{$_FN['siteurl']}/{$photo1path}";
        } else {
            $photo1path = '';
        }
    }

    // Coordinate WGS84
    $lat = 0;
    $lon = 0;
    $coordinate = null;
    $hasCoords = (isset($record['latitude']) && isset($record['longitude']) && $record['latitude'] != '' && $record['longitude'] != '');
    if ($hasCoords) {
        $coordinate = openkis_GetItemPosition($record);
        $lat = $coordinate['lat'];
        $lon = $coordinate['lon'];
    }

    $isIGM = (false !== strstr($record['coordinates_type'], "IGM") || false !== strstr(@$record['original_coordinates_type'], "IGM"));

    return array(
        'record' => $record,
        'rilievi' => $rilievi,
        'bibliografia' => $bibliografia,
        'strcens' => implode(", ", $strcens),
        'areaName' => $areaName,
        'geoValue' => $geoValue,
        'dataAggiornamento' => date("d/m/Y", $d),
        'photo1' => $photo1,
        'photo1path' => $photo1path,
        'lat' => $lat,
        'lon' => $lon,
        'coordinate' => $coordinate,
        'hasCoords' => ($lat != 0 && $lon != 0),
        'isIGM' => $isIGM,
        'footertext' => "LI{$record['code']} estratta da www.catastogrotte.net " . strtolower(FN_FormatDate(time())),
    );
}

// ========================================================================
// Funzioni per la versione HTML (pagina web stampabile)
// ========================================================================

function html_field_row($label, $value)
{
    if ($value === null || $value === '' || $value === '0')
        return '';
    $value = fix_charset($value);
    $value = str_replace("\n", "<br>", $value);
    return '<div class="row field-row">
        <div class="col-label">' . $label . '</div>
        <div class="col-value">' . $value . '</div>
    </div>';
}

function html_section_title($title)
{
    return '<h5 class="section-title">' . $title . '</h5>';
}

function html_text_block($text)
{
    $text = fix_charset($text);
    $text = str_replace("\n", "<br>", $text);
    return '<div class="text-block">' . $text . '</div>';
}

/**
 * Genera una pagina HTML standalone stampabile (per browser)
 */
function get_html_pdf($id)
{
    global $_FN;
    $data = pdf_get_cave_data($id);
    $record = $data['record'];

    $html = '<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LI' . fix_charset($record['code']) . ' - ' . fix_charset($record['name']) . ' - Scheda Catastale</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; font-size: 14px; color: #333; background: #f5f5f5; }
    .page { max-width: 800px; margin: 0 auto; background: #fff; padding: 24px 30px; }
    .header-ssi { border: 2px solid #333; padding: 12px 16px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; }
    .header-ssi-left .ssi-name { font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #444; }
    .header-ssi-left .ssi-catasto { font-size: 20px; font-weight: bold; letter-spacing: 2px; margin-top: 2px; }
    .header-ssi-left .ssi-regione { font-size: 10px; color: #666; margin-top: 2px; }
    .header-ssi-right { text-align: right; }
    .header-ssi-right .meta-label { font-size: 9px; color: #888; text-transform: uppercase; }
    .header-ssi-right .meta-value { font-size: 14px; font-weight: bold; }
    .header-ssi-right .meta-block { display: inline-block; margin-left: 16px; text-align: center; }
    .cave-name { text-align: center; color: #0078a3; font-size: 22px; font-weight: bold; margin: 8px 0 2px 0; }
    .cave-code { text-align: right; font-size: 15px; color: #555; font-weight: bold; }
    .cave-synonyms { text-align: center; font-size: 12px; color: #888; margin-bottom: 8px; }
    .cave-update { text-align: right; font-size: 11px; color: #aaa; margin-bottom: 12px; }
    .photo-entrance { text-align: center; margin: 10px 0; }
    .photo-entrance img { max-width: 320px; max-height: 220px; border: 1px solid #ddd; border-radius: 4px; }
    .photo-entrance .photo-author { font-size: 10px; color: #999; margin-top: 2px; }
    .section-title { font-size: 13px; font-weight: bold; text-transform: uppercase; color: #555; background: #f0f0f0; padding: 5px 10px; margin: 14px 0 4px 0; border-left: 3px solid #0078a3; }
    .row { display: flex; flex-wrap: wrap; border-bottom: 1px solid #f0f0f0; }
    .field-row .col-label { flex: 0 0 200px; max-width: 200px; font-size: 12px; color: #0078a3; text-align: right; padding: 3px 12px 3px 4px; }
    .field-row .col-value { flex: 1; padding: 3px 4px; font-size: 13px; }
    .dim-grid { display: flex; flex-wrap: wrap; margin: 4px 0 8px 0; }
    .dim-cell { flex: 1 1 33%; text-align: center; padding: 6px 4px; border: 1px solid #eee; }
    .dim-cell .dim-label { font-size: 10px; color: #888; }
    .dim-cell .dim-value { font-size: 16px; font-weight: bold; color: #333; }
    .text-block { padding: 8px 12px; font-size: 13px; line-height: 1.5; text-align: justify; border: 1px solid #eee; margin-bottom: 4px; background: #fafafa; }
    .bib-entry { padding: 4px 12px; font-size: 12px; border-bottom: 1px solid #f0f0f0; }
    .survey-item { text-align: center; margin: 10px 0; page-break-inside: avoid; }
    .survey-item img { max-width: 100%; max-height: 600px; border: 1px solid #ddd; }
    .survey-info { font-size: 11px; color: #666; padding: 4px 8px; background: #fafafa; border: 1px solid #eee; margin-top: 4px; display: inline-block; text-align: left; }
    .footer { text-align: center; font-size: 10px; color: #aaa; margin-top: 20px; padding-top: 8px; border-top: 1px solid #eee; }
    .badge-list { padding: 4px 0 4px 210px; }
    .badge { display: inline-block; background: #fff3cd; color: #856404; border: 1px solid #ffc107; border-radius: 3px; padding: 2px 8px; font-size: 11px; margin: 2px 4px 2px 0; }
    @media print {
        body { background: #fff; }
        .page { max-width: 100%; padding: 10px; box-shadow: none; }
        .no-print { display: none; }
        .section-title { break-after: avoid; }
        .survey-item { break-inside: avoid; }
    }
    @media screen {
        .page { box-shadow: 0 1px 8px rgba(0,0,0,0.1); margin: 20px auto; border-radius: 2px; }
    }
    @media (max-width: 600px) {
        .header-ssi { flex-direction: column; text-align: center; }
        .header-ssi-right { margin-top: 8px; }
        .field-row .col-label { flex: 0 0 120px; max-width: 120px; font-size: 11px; }
        .badge-list { padding-left: 0; }
        .dim-cell { flex: 1 1 50%; }
    }
</style>
</head>
<body>
<div class="page">';

    // Header SSI
    $html .= '
    <div class="header-ssi">
        <div class="header-ssi-left">
            <div class="ssi-name">Societ&agrave; Speleologica Italiana</div>
            <div class="ssi-catasto">Catasto delle Grotte d\'Italia</div>
            <div class="ssi-regione">Catasto Speleologico Ligure</div>
        </div>
        <div class="header-ssi-right">
            <div class="meta-block"><div class="meta-label">Regione</div><div class="meta-value">LI</div></div>
            <div class="meta-block"><div class="meta-label">Provincia</div><div class="meta-value">' . fix_charset($record['provincia']) . '</div></div>
            <div class="meta-block"><div class="meta-label">Numero</div><div class="meta-value">' . fix_charset($record['code']) . '</div></div>
        </div>
    </div>';

    $html .= '<div class="cave-code">LI ' . fix_charset($record['code']) . '</div>';
    $html .= '<div class="cave-name">' . fix_charset($record['name']) . '</div>';
    if (!empty($record['synonyms']))
        $html .= '<div class="cave-synonyms">' . fix_charset($record['synonyms']) . '</div>';
    $html .= '<div class="cave-update">Ultimo aggiornamento: ' . $data['dataAggiornamento'] . '</div>';

    if ($data['photo1'] != '') {
        $html .= '<div class="photo-entrance"><img src="' . $data['photo1'] . '" alt="Ingresso">';
        if (!empty($record['authorphoto1']))
            $html .= '<div class="photo-author">Foto: ' . fix_charset($record['authorphoto1']) . '</div>';
        $html .= '</div>';
    }

    // Contenuto campi
    $html .= _build_fields_html($data, 'html');

    // Mappa Leaflet
    if ($data['hasCoords']) {
        $html .= html_section_title('Mappa');
        $html .= '<div id="map" style="height:400px;border:1px solid #dddddd;margin-bottom:8px;"></div>';
        $html .= '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />';
        $html .= '<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>';
        $html .= '<script>document.addEventListener("DOMContentLoaded", function() {
            var map = L.map("map").setView([' . $data['lat'] . ', ' . $data['lon'] . '], ' . ($data['isIGM'] ? '13' : '15') . ');';
        if ($data['isIGM']) {
            $html .= 'L.tileLayer("https://cartodb-basemaps-a.global.ssl.fastly.net/light_nolabels/{z}/{x}/{y}.png",{maxZoom:18,attribution:"CartoDB"}).addTo(map);
            L.tileLayer.wms("https://sgi2.isprambiente.it/arcgis/services/raster/igm25k_liguria_wgs/ImageServer/WMSServer",{layers:"igm25k_liguria_wgs",format:"image/png",transparent:true,version:"1.1.1",attribution:"IGM 1:25000 Liguria"}).addTo(map);';
        } else {
            $html .= 'L.tileLayer("https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png",{maxZoom:17,attribution:"OpenTopoMap"}).addTo(map);';
        }
        $html .= 'L.marker([' . $data['lat'] . ',' . $data['lon'] . ']).addTo(map).bindPopup("<b>' . addslashes(fix_charset($record['name'])) . '</b><br>LI ' . addslashes(fix_charset($record['code'])) . '").openPopup();});</script>';
    }

    // Rilievi
    $html .= _build_surveys_html($data, 'html');

    $html .= '<div class="footer">' . $data['footertext'] . '</div>';
    // Costruisci URL per il PDF diretto (stessa pagina con &format=pdf)
    $pdfUrl = $_SERVER['REQUEST_URI'];
    $pdfUrl .= (strpos($pdfUrl, '?') !== false) ? '&format=pdf' : '?format=pdf';

    $html .= '<div class="no-print" style="text-align:center;margin:16px 0;">
        <button onclick="window.print()" style="background:#0078a3;color:#fff;border:none;padding:10px 24px;font-size:14px;border-radius:4px;cursor:pointer;">Stampa</button>
        <a href="' . htmlspecialchars($pdfUrl) . '" style="background:#555;color:#fff;text-decoration:none;padding:10px 24px;font-size:14px;border-radius:4px;margin-left:8px;display:inline-block;">Scarica PDF</a>
    </div>';
    $html .= '</div></body></html>';

    return $html;
}

// ========================================================================
// Funzioni per la versione PDF (HTML2PDF compatibile - solo tabelle, no flex/JS)
// ========================================================================

function tpdf_field_row($label, $value)
{
    if ($value === null || $value === '' || $value === '0')
        return '';
    $value = fix_charset($value);
    $value = str_replace("\n", "<br>", $value);
    return '<tr>
        <td style="font-size:10px;color:#0078a3;padding:3px 8px;width:170px;text-align:right;border-bottom:1px solid #eeeeee;vertical-align:top;">' . $label . '</td>
        <td style="font-size:11px;padding:3px 8px;border-bottom:1px solid #eeeeee;vertical-align:top;">' . $value . '</td>
    </tr>';
}

function tpdf_section_title($title)
{
    return '<table cellspacing="0" cellpadding="0" style="width:100%;margin-top:8px;margin-bottom:2px;">
    <tr><td bgcolor="#f0f0f0" style="font-size:11px;font-weight:bold;text-transform:uppercase;color:#555555;padding:4px 8px;border-left:3px solid #0078a3;">' . $title . '</td></tr>
    </table>';
}

function tpdf_text_block($text)
{
    $text = fix_charset($text);
    $text = str_replace("\n", "<br>", $text);
    return '<table cellspacing="0" cellpadding="0" style="width:100%;margin-bottom:3px;"><tr><td bgcolor="#fafafa" style="padding:6px 10px;font-size:11px;line-height:1.4;text-align:justify;border:1px solid #eeeeee;">' . $text . '</td></tr></table>';
}

/**
 * Genera il contenuto HTML per HTML2PDF (solo tabelle, no flex, no JS)
 */
function get_pdf_content($id)
{
    global $_FN;
    $data = pdf_get_cave_data($id);
    $record = $data['record'];

    $html = '<style type="text/css">
        table { border-collapse: collapse; }
        td { font-family: Arial, sans-serif; }
    </style>';

    // ===== HEADER SSI =====
    $html .= '<table cellspacing="0" cellpadding="0" style="width:100%;border:2px solid #333333;margin-bottom:10px;">
    <tr>
        <td style="width:60%;padding:10px;vertical-align:middle;">
            <div style="font-size:10px;font-weight:bold;text-transform:uppercase;letter-spacing:1px;color:#444444;">Societ&agrave; Speleologica Italiana</div>
            <div style="font-size:18px;font-weight:bold;letter-spacing:2px;margin-top:3px;">Catasto delle Grotte d\'Italia</div>
            <div style="font-size:9px;color:#666666;margin-top:2px;">Catasto Speleologico Ligure</div>
        </td>
        <td style="width:40%;padding:10px;text-align:right;vertical-align:middle;">
            <table cellspacing="0" cellpadding="2" style="float:right;">
                <tr><td style="font-size:8px;color:#888888;">Regione</td><td style="font-size:8px;color:#888888;">Provincia</td><td style="font-size:8px;color:#888888;">Numero</td></tr>
                <tr><td style="font-size:13px;font-weight:bold;padding-right:12px;">LI</td><td style="font-size:13px;font-weight:bold;padding-right:12px;">' . fix_charset($record['provincia']) . '</td><td style="font-size:13px;font-weight:bold;">' . fix_charset($record['code']) . '</td></tr>
            </table>
        </td>
    </tr>
    </table>';

    // ===== NOME =====
    $html .= '<div style="text-align:right;font-size:13px;color:#555555;font-weight:bold;">LI ' . fix_charset($record['code']) . '</div>';
    $html .= '<div style="text-align:center;color:#0078a3;font-size:20px;font-weight:bold;margin:4px 0 2px 0;">' . fix_charset($record['name']) . '</div>';
    if (!empty($record['synonyms']))
        $html .= '<div style="text-align:center;font-size:10px;color:#888888;margin-bottom:4px;">' . fix_charset($record['synonyms']) . '</div>';
    $html .= '<div style="text-align:right;font-size:9px;color:#aaaaaa;margin-bottom:8px;">Ultimo aggiornamento: ' . $data['dataAggiornamento'] . '</div>';

    // ===== FOTO INGRESSO =====
    if ($data['photo1path'] != '') {
        $photodim = @getimagesize($data['photo1path']);
        if ($photodim) {
            $pdim = get_dim($photodim[1], $photodim[0], 180, 250);
            $html .= '<div style="text-align:center;margin:6px 0;">';
            $html .= '<img src="' . $data['photo1path'] . '" width="' . intval($pdim['w']) . '" height="' . intval($pdim['h']) . '" />';
            if (!empty($record['authorphoto1']))
                $html .= '<br><span style="font-size:8px;color:#999999;">Foto: ' . fix_charset($record['authorphoto1']) . '</span>';
            $html .= '</div>';
        }
    }

    // ===== CAMPI =====
    $html .= _build_fields_html($data, 'pdf');

    // ===== MAPPA STATICA WMS =====
    if ($data['hasCoords']) {
        $html .= _build_static_map($data);
    }

    // ===== RILIEVI =====
    $html .= _build_surveys_html($data, 'pdf');

    // ===== FOOTER =====
    $html .= '<div style="text-align:center;font-size:8px;color:#aaaaaa;margin-top:12px;padding-top:6px;border-top:1px solid #eeeeee;">' . $data['footertext'] . '</div>';

    return $html;
}

// ========================================================================
// Funzioni condivise per generazione campi
// ========================================================================

function _build_fields_html($data, $mode)
{
    $record = $data['record'];
    $html = '';

    // Scorciatoie per le funzioni
    $field_row = ($mode === 'pdf') ? 'tpdf_field_row' : 'html_field_row';
    $section_title = ($mode === 'pdf') ? 'tpdf_section_title' : 'html_section_title';
    $text_block = ($mode === 'pdf') ? 'tpdf_text_block' : 'html_text_block';

    // Localizzazione
    $html .= $section_title('Localizzazione');
    if ($mode === 'pdf') $html .= '<table cellspacing="0" cellpadding="0" style="width:100%;">';
    $html .= $field_row('Comune', $record['comune']);
    $html .= $field_row('Localit&agrave;', $record['localita']);
    if ($record['mount'] != '' && $record['mount'] != '0')
        $html .= $field_row('Monte', $record['mount']);
    if ($record['valley'] != '' && $record['valley'] != '0')
        $html .= $field_row('Valle', $record['valley']);
    $html .= $field_row('Area carsica', $data['areaName']);
    if ($mode === 'pdf') $html .= '</table>';

    // Geologia
    $html .= $section_title('Geologia');
    if ($mode === 'pdf') $html .= '<table cellspacing="0" cellpadding="0" style="width:100%;">';
    $html .= $field_row('Formazione geologica', $data['geoValue']);
    if (!empty($record['lithology']))
        $html .= $field_row('Litologia', $record['lithology']);
    if ($mode === 'pdf') $html .= '</table>';

    // Coordinate
    $html .= $section_title('Posizione ingresso');
    if ($mode === 'pdf') $html .= '<table cellspacing="0" cellpadding="0" style="width:100%;">';
    $html .= $field_row('Tipo coordinate', $record['coordinates_type']);
    $html .= $field_row('Carta', $record['map_denomination']);
    if (!empty($record['map_edition']))
        $html .= $field_row('Edizione', $record['map_edition']);
    $html .= $field_row('Valutazione dato', $record['location_evaluation']);
    $html .= $field_row('Longitudine', $record['longitude_txt']);
    $html .= $field_row('Latitudine', $record['latitude_txt']);
    $html .= $field_row('Quota altimetrica', $record['elevation']);
    $html .= $field_row('Quota cartografica', $record['elevation_map']);
    $html .= $field_row('Quota GPS', $record['elevation_gps']);
    if ($mode === 'pdf') $html .= '</table>';

    // Dimensioni
    $html .= $section_title('Dimensioni');
    if ($mode === 'pdf') {
        $html .= '<table cellspacing="0" cellpadding="0" style="width:100%;">
        <tr>
            <td style="font-size:9px;color:#888888;padding:4px;text-align:center;width:33%;border:1px solid #dddddd;">Sviluppo reale<br><b style="font-size:13px;color:#000000;">' . ($record['lenght_total'] ?: '-') . '</b></td>
            <td style="font-size:9px;color:#888888;padding:4px;text-align:center;width:33%;border:1px solid #dddddd;">Sviluppo planimetrico<br><b style="font-size:13px;color:#000000;">' . ($record['lenght_planimetric'] ?: '-') . '</b></td>
            <td style="font-size:9px;color:#888888;padding:4px;text-align:center;width:34%;border:1px solid #dddddd;">Estensione<br><b style="font-size:13px;color:#000000;">' . ($record['lenght_extension'] ?: '-') . '</b></td>
        </tr>
        <tr>
            <td style="font-size:9px;color:#888888;padding:4px;text-align:center;border:1px solid #dddddd;">Dislivello positivo (+)<br><b style="font-size:13px;color:#000000;">' . ($record['depth_positive'] ?: '-') . '</b></td>
            <td style="font-size:9px;color:#888888;padding:4px;text-align:center;border:1px solid #dddddd;">Dislivello negativo (-)<br><b style="font-size:13px;color:#000000;">' . ($record['depth_negative'] ?: '-') . '</b></td>
            <td style="font-size:9px;color:#888888;padding:4px;text-align:center;border:1px solid #dddddd;">Dislivello totale<br><b style="font-size:13px;color:#000000;">' . ($record['depth_total'] ?: '-') . '</b></td>
        </tr></table>';
    } else {
        $html .= '<div class="dim-grid">
            <div class="dim-cell"><div class="dim-label">Sviluppo reale</div><div class="dim-value">' . ($record['lenght_total'] ?: '-') . '</div></div>
            <div class="dim-cell"><div class="dim-label">Sviluppo planimetrico</div><div class="dim-value">' . ($record['lenght_planimetric'] ?: '-') . '</div></div>
            <div class="dim-cell"><div class="dim-label">Estensione</div><div class="dim-value">' . ($record['lenght_extension'] ?: '-') . '</div></div>
            <div class="dim-cell"><div class="dim-label">Dislivello positivo (+)</div><div class="dim-value">' . ($record['depth_positive'] ?: '-') . '</div></div>
            <div class="dim-cell"><div class="dim-label">Dislivello negativo (-)</div><div class="dim-value">' . ($record['depth_negative'] ?: '-') . '</div></div>
            <div class="dim-cell"><div class="dim-label">Dislivello totale</div><div class="dim-value">' . ($record['depth_total'] ?: '-') . '</div></div>
        </div>';
    }

    // Caratteristiche
    $html .= $section_title('Caratteristiche');
    if ($mode === 'pdf') $html .= '<table cellspacing="0" cellpadding="0" style="width:100%;">';
    $html .= $field_row('Idrologia', $record['hydrology']);
    $html .= $field_row('Andamento', $record['trend']);
    $html .= $field_row('Percorribilit&agrave;', $record['practicability']);
    if (!empty($record['wells']))
        $html .= $field_row('Sequenza pozzi', $record['wells']);
    if ($mode === 'pdf') $html .= '</table>';

    // Classificazioni
    $strcens = $data['strcens'];
    if ($strcens != '' || !empty($record['closed_notes'])) {
        $html .= $section_title('Classificazioni');
        if ($mode === 'pdf') {
            $html .= '<table cellspacing="0" cellpadding="0" style="width:100%;">';
            if ($strcens != '') $html .= tpdf_field_row('Censimenti', $strcens);
            if (!empty($record['closed_notes'])) $html .= tpdf_field_row('Note accesso', $record['closed_notes']);
            $html .= '</table>';
        } else {
            if ($strcens != '') {
                $html .= '<div class="badge-list">';
                foreach (explode(", ", $strcens) as $b) $html .= '<span class="badge">' . $b . '</span>';
                $html .= '</div>';
            }
            if (!empty($record['closed_notes']))
                $html .= html_field_row('Note accesso', $record['closed_notes']);
        }
    }

    // Testi lunghi
    $textFields = array(
        'notes' => 'Note',
        'description' => 'Descrizione',
        'itinerary' => 'Itinerario',
        'associations' => 'Gruppi',
        'fauna' => 'Fauna',
        'chronology' => 'Cronologia catastale',
        'history' => 'Storia',
        'geology' => 'Geologia dettagliata',
        'meteorology' => 'Meteorologia',
    );
    foreach ($textFields as $field => $label) {
        if (!empty($record[$field])) {
            $html .= $section_title($label);
            $html .= $text_block($record[$field]);
        }
    }

    // Dati compilazione
    $html .= $section_title('Dati compilazione');
    if ($mode === 'pdf') $html .= '<table cellspacing="0" cellpadding="0" style="width:100%;">';
    $html .= $field_row('Primo censitore', $record['firstreference']);
    if (!empty($record['check']) && $record['check'] == 'S')
        $html .= $field_row('Posizione verificata', 'S&igrave;');
    if (!empty($record['check_date']))
        $html .= $field_row('Data verifica', $record['check_date']);
    $html .= $field_row('Ultimo aggiornamento', $data['dataAggiornamento']);
    if ($mode === 'pdf') $html .= '</table>';

    // Bibliografia
    $bibliografia = $data['bibliografia'];
    if ($bibliografia && count($bibliografia) > 0) {
        $html .= $section_title('Bibliografia');
        foreach ($bibliografia as $bib) {
            $bibtext = '';
            if (!empty($bib['authors'])) $bibtext .= '<b>' . fix_charset($bib['authors']) . '</b> - ';
            $bibtext .= fix_charset($bib['title']);
            if (!empty($bib['year'])) $bibtext .= ' (' . fix_charset($bib['year']) . ')';
            if ($mode === 'pdf') {
                $html .= '<div style="padding:3px 10px;font-size:10px;border-bottom:1px solid #f0f0f0;">' . $bibtext . '</div>';
            } else {
                $html .= '<div class="bib-entry">' . $bibtext . '</div>';
            }
        }
    }

    return $html;
}

/**
 * Genera immagine mappa statica WMS per il PDF
 */
/**
 * Genera mappa statica da tile OpenTopoMap
 * Scarica le tile necessarie e le compone in un'unica immagine
 */
function _build_tile_map($lat, $lon, $zoom, $w, $h)
{
    $xtile_f = (($lon + 180) / 360) * pow(2, $zoom);
    $ytile_f = (1 - log(tan(deg2rad($lat)) + 1 / cos(deg2rad($lat))) / M_PI) / 2 * pow(2, $zoom);

    $cx = intval($w / 2);
    $cy = intval($h / 2);

    // Posizione del punto all'interno della tile centrale
    $xtile_center = floor($xtile_f);
    $ytile_center = floor($ytile_f);
    $xpx_offset = intval(($xtile_f - $xtile_center) * 256);
    $ypx_offset = intval(($ytile_f - $ytile_center) * 256);

    // Quante tile servono in ogni direzione
    $tiles_left = intval(ceil(($cx - (256 - $xpx_offset)) / 256)) + 1;
    $tiles_right = intval(ceil(($cx - $xpx_offset) / 256)) + 1;
    $tiles_up = intval(ceil(($cy - (256 - $ypx_offset)) / 256)) + 1;
    $tiles_down = intval(ceil(($cy - $ypx_offset) / 256)) + 1;

    $img = imagecreatetruecolor($w, $h);
    $white = imagecolorallocate($img, 255, 255, 255);
    imagefill($img, 0, 0, $white);

    $servers = array('a', 'b', 'c');
    $si = 0;

    for ($tx = -$tiles_left; $tx <= $tiles_right; $tx++) {
        for ($ty = -$tiles_up; $ty <= $tiles_down; $ty++) {
            $tilex = $xtile_center + $tx;
            $tiley = $ytile_center + $ty;
            if ($tilex < 0 || $tiley < 0) continue;

            $s = $servers[$si % 3];
            $si++;
            $tile_url = "https://{$s}.tile.opentopomap.org/{$zoom}/{$tilex}/{$tiley}.png";
            $tiledata = getWebPage($tile_url);
            if (!$tiledata) continue;
            $tile_img = @imagecreatefromstring($tiledata);
            if (!$tile_img) continue;

            // Posizione della tile nell'immagine finale
            $dx = $cx - $xpx_offset + ($tx * 256);
            $dy = $cy - $ypx_offset + ($ty * 256);
            imagecopy($img, $tile_img, $dx, $dy, 0, 0, 256, 256);
            imagedestroy($tile_img);
        }
    }
    return $img;
}

function _build_static_map($data)
{
    global $_FN;
    $record = $data['record'];
    $lat = $data['lat'];
    $lon = $data['lon'];

    $w = 1200;
    $h = intval($w * 1.35); // ~A4 portrait ratio

    $cachedir = "{$_FN['datadir']}/_cache/_THUMBS";
    if (!file_exists($cachedir)) @mkdir($cachedir, 0777, true);

    if ($data['isIGM']) {
        // IGM 1:25000 Liguria via WMS
        $offset_lon = 0.025;
        $offset_lat = $offset_lon * 0.7;
        $b1 = $lon - $offset_lon;
        $b2 = $lat - $offset_lat;
        $b3 = $lon + $offset_lon;
        $b4 = $lat + $offset_lat;
        $wms_url = "https://sgi2.isprambiente.it/arcgis/services/raster/igm25k_liguria_wgs/ImageServer/WMSServer"
            . "?SERVICE=WMS&VERSION=1.1.1&REQUEST=GetMap&LAYERS=igm25k_liguria_wgs"
            . "&SRS=EPSG:4326&BBOX={$b1},{$b2},{$b3},{$b4}"
            . "&WIDTH={$w}&HEIGHT={$h}&FORMAT=image/jpeg&STYLES=";
        $mapTitle = "IGM 1:25000";
        $fname = md5($wms_url) . "_marker.png";
        $cachefile = "{$cachedir}/{$fname}";

        if (!file_exists($cachefile)) {
            $imgdata = getWebPage($wms_url);
            if ($imgdata) {
                $img = @imagecreatefromstring($imgdata);
            }
            if (isset($img) && $img) {
                _draw_marker($img, intval($w / 2), intval($h / 2));
                imagepng($img, $cachefile);
                imagedestroy($img);
            }
        }
    } else {
        // OpenTopoMap - mappa topografica dettagliata da tile
        $mapTitle = "OpenTopoMap";
        $zoom = 15; // buon dettaglio per grotte
        $fname = "otm_{$zoom}_" . round($lat, 5) . "_" . round($lon, 5) . "_{$w}x{$h}_marker.png";
        $cachefile = "{$cachedir}/{$fname}";

        if (!file_exists($cachefile)) {
            $img = _build_tile_map($lat, $lon, $zoom, $w, $h);
            if ($img) {
                _draw_marker($img, intval($w / 2), intval($h / 2));
                imagepng($img, $cachefile);
                imagedestroy($img);
            }
        }
    }

    // Pagina dedicata per la mappa
    $html = '<page orientation="portrait" format="A4">';
    $html .= '<div style="margin:10px 20px;font-size:12px;font-weight:bold;border-bottom:1px solid #cccccc;padding-bottom:4px;">' . $mapTitle . ': ' . fix_charset($record['name']) . ' - LI ' . fix_charset($record['code']) . '</div>';
    if (file_exists($cachefile) && @getimagesize($cachefile)) {
        $html .= '<div style="text-align:center;margin:5px;">';
        $html .= '<img src="' . $cachefile . '" width="540" height="729" />';
        $html .= '</div>';
    }
    // Coordinate in basso
    $html .= '<div style="position:absolute;bottom:30px;left:40px;font-size:9px;color:#666666;">';
    $html .= 'Coordinate WGS84: ' . round($lon, 5) . ' E, ' . round($lat, 5) . ' N';
    if ($data['coordinate'] && !empty($data['coordinate']['x']))
        $html .= ' &nbsp;|&nbsp; UTM: ' . $data['coordinate']['x'] . ' E, ' . $data['coordinate']['y'] . ' N ' . $data['coordinate']['zone'];
    $html .= '</div>';
    $html .= '<div style="position:absolute;bottom:10px;right:20px;font-size:8px;color:#aaaaaa;">' . $data['footertext'] . '</div>';
    $html .= '</page>';
    return $html;
}

/**
 * Disegna marker rosso (cerchio + croce) su un'immagine GD
 */
function _draw_marker($img, $cx, $cy)
{
    $red = imagecolorallocate($img, 255, 0, 0);
    imageellipse($img, $cx, $cy, 30, 30, $red);
    imageellipse($img, $cx, $cy, 31, 31, $red);
    imageellipse($img, $cx, $cy, 32, 32, $red);
    imageline($img, $cx - 18, $cy, $cx + 18, $cy, $red);
    imageline($img, $cx, $cy - 18, $cx, $cy + 18, $red);
}

/**
 * Genera la sezione rilievi
 */
function _build_surveys_html($data, $mode)
{
    global $_FN;
    $rilievi = $data['rilievi'];
    $html = '';

    if (count($rilievi) == 0) return '';

    // Nel PDF ogni rilievo ha la sua pagina, il titolo "Rilievi" e' superfluo
    if ($mode !== 'pdf') {
        $html .= html_section_title('Rilievi');
    }

    foreach ($rilievi as $rilievo) {
        if (!isset($rilievo['photo1'])) continue;
        $filename = "{$_FN['datadir']}/fndatabase/ctl_surveys/{$rilievo['id']}/photo1/{$rilievo['photo1']}";
        if (!file_exists($filename) || is_dir($filename)) continue;

        if ($mode === 'pdf') {
            // Per HTML2PDF: usa <page> con orientamento auto
            // A4 = 210x297mm, margini 5mm => area utile ~200x287mm => ~566x813px @72dpi
            // Lasciamo spazio per titolo e info in basso
            list($width, $height) = @getimagesize($filename);
            if (!$width) continue;
            $orientation = ($height < $width) ? 'paysage' : 'portrait';
            if ($orientation === 'paysage') {
                // Landscape: area utile ~813x566, meno spazio per titolo/info
                $maxw = 780;
                $maxh = 480;
            } else {
                // Portrait: area utile ~566x813
                $maxw = 540;
                $maxh = 720;
            }
            $newdim = get_dim($height, $width, $maxh, $maxw);
            $html .= '<page orientation="' . $orientation . '" format="A4">';
            if (!empty($rilievo['name']))
                $html .= '<div style="margin:10px 20px;font-size:12px;font-weight:bold;border-bottom:1px solid #cccccc;padding-bottom:4px;">' . fix_charset($rilievo['name']) . '</div>';
            $html .= '<div style="text-align:center;margin:5px;"><img height="' . intval($newdim['h']) . '" width="' . intval($newdim['w']) . '" src="' . $filename . '" /></div>';
            if ($rilievo['author'] != '' || $rilievo['date'] != '' || $rilievo['accuracy'] != '' || $rilievo['description'] != '') {
                $html .= '<table cellspacing="0" cellpadding="0" style="position:absolute;bottom:30px;left:40px;width:600px;"><tr><td bgcolor="#fafafa" style="border:1px solid #cccccc;padding:4px 8px;font-size:9px;">';
                if ($rilievo['accuracy'] != '') $html .= '<b>Precisione: </b>' . fix_charset($rilievo['accuracy']) . ' &nbsp; ';
                if ($rilievo['author'] != '') $html .= '<b>Autore: </b>' . fix_charset($rilievo['author']) . '<br>';
                if ($rilievo['date'] != '') $html .= '<b>Data: </b>' . fix_charset($rilievo['date']) . ' &nbsp; ';
                if ($rilievo['description'] != '') $html .= '<div style="width:580px;">' . fix_charset($rilievo['description']) . '</div>';
                $html .= '</td></tr></table>';
            }
            $html .= '</page>';
        } else {
            // Per HTML browser
            $fileurl = "{$_FN['siteurl']}/{$filename}";
            $html .= '<div class="survey-item">';
            if (!empty($rilievo['name']))
                $html .= '<div style="font-weight:bold;font-size:14px;margin-bottom:6px;color:#333333;">' . fix_charset($rilievo['name']) . '</div>';
            $html .= '<a href="' . $fileurl . '" target="_blank"><img src="' . $fileurl . '" alt="Rilievo"></a>';
            if ($rilievo['author'] != '' || $rilievo['date'] != '' || $rilievo['accuracy'] != '' || $rilievo['description'] != '') {
                $html .= '<div class="survey-info">';
                if ($rilievo['author'] != '') $html .= '<strong>Autore:</strong> ' . fix_charset($rilievo['author']) . ' &nbsp; ';
                if ($rilievo['date'] != '') $html .= '<strong>Data:</strong> ' . fix_charset($rilievo['date']) . ' &nbsp; ';
                if ($rilievo['accuracy'] != '') $html .= '<strong>Precisione:</strong> ' . fix_charset($rilievo['accuracy']) . '<br>';
                if ($rilievo['description'] != '') $html .= fix_charset($rilievo['description']);
                $html .= '</div>';
            }
            $html .= '</div>';
        }
    }
    return $html;
}

// ========================================================================
// Funzioni utility
// ========================================================================

function get_dim($height, $width, $maxh, $maxw)
{
    $new_height = $height;
    $new_width = $width;
    if ($maxw != "" && $width >= $maxw) {
        $new_width = $maxw;
        $new_height = $height * ($new_width / $width);
    }
    if ($maxh != "" && $new_height >= $maxh) {
        $new_height = $maxh;
        $new_width = $width * ($new_height / $height);
    }
    if ($maxw != "" && $maxh != "" && $width <= $maxw && $height <= $maxh) {
        $new_width = $width;
        $new_height = $height;
    }
    return array("h" => $new_height, "w" => $new_width);
}

function getWebPage($url)
{
    $options = array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => "Mozilla/5.0 (X11; Linux x86_64; rv:2.0.1) Gecko/20100101 Firefox/4.0.1",
        CURLOPT_AUTOREFERER => true,
        CURLOPT_CONNECTTIMEOUT => 120,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_MAXREDIRS => 10,
    );
    $ch = curl_init($url);
    curl_setopt_array($ch, $options);
    $content = curl_exec($ch);
    curl_close($ch);
    return $content;
}
