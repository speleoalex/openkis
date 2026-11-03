<?php

/**
 * Record sheets with thematic cards (caves, karst areas, springs, fauna species)
 * and the cards listing the records linked to a sheet (caves of an area,
 * bibliography, sightings, tracer tests...), so that every sheet has the same
 * layout:
 *   - title block: name, place, badges, icon;
 *   - main column (col-lg-8) with a card for each group of fields;
 *   - side column (col-lg-4) with the photos and the blocks pushed by the
 *     section's viewfooter.php into $GLOBALS['openkis_view_side'];
 *   - below, at full width, the cards of the linked records echoed by viewfooter.php.
 *
 * The section's viewheader.php calls openkis_SheetRender(); its detail.tp.html
 * suppresses the default field rendering, prints {innertables} in the side
 * column and closes the side column and the row.
 */

/**
 * Text with the first letter in upper case (card titles come from translations
 * written in lower case).
 *
 * @param string $text
 * @return string
 */
function openkis_SheetUcfirst($text)
{
    $text = (string) $text;
    return mb_strtoupper(mb_substr($text, 0, 1, "UTF-8"), "UTF-8") . mb_substr($text, 1, null, "UTF-8");
}

/**
 * Text for html, keeping the entities already in it (titles stored with &egrave; ...).
 *
 * @param string $text
 * @return string
 */
function openkis_SheetEscape($text)
{
    return htmlspecialchars((string) $text, ENT_QUOTES, "UTF-8", false);
}

/**
 * Render a set of fields of the current record with the view engine.
 * A card made of a single block field (and its credit line) shows the text
 * without the field title, which would repeat the card title.
 *
 * @param XMETADBForm $frm
 * @param array $values translated record
 * @param array $fields field names to render, in order
 * @param array $longtext fields rendered as blocks
 * @param array $author fields rendered as credit lines
 * @param array $titles field => title replacing the one of the table
 * @return string html, empty when no field has a value
 */
function openkis_ViewCardFields($frm, $values, $fields, $longtext = array(), $author = array(), $titles = array())
{
    $fields = array_values(array_filter($fields, function ($f) use ($frm)
    {
        return isset($frm->formvals[$f]);
    }));
    $notauthor = array_values(array_diff($fields, $author));
    $notitle = (count($notauthor) == 1 && in_array($notauthor[0], $longtext));
    $tpl = "<!-- contents --><!-- group --> <!-- end_group --><!-- endgroup --> <!-- end_endgroup --><!-- item --> <!-- end_item -->";
    foreach ($fields as $f)
    {
        if (in_array($f, $author))
        {
            $rowtpl = "<div class=\"openkis-field-author text-right text-end text-muted small\">{input}</div>";
        }
        elseif (in_array($f, $longtext))
        {
            if ($notitle)
                $rowtpl = "<div class=\"openkis-field openkis-field-block\"><div class=\"openkis-field-value\">{input}</div></div>";
            else
                $rowtpl = "<div class=\"openkis-field openkis-field-block\"><div class=\"openkis-field-title\">{title}</div><div class=\"openkis-field-value\">{input}</div></div>";
        }
        elseif ($f == "import_rawdata")
        {
            $rowtpl = "<details class=\"openkis-field small\"><summary class=\"text-muted\">{title}</summary><div class=\"openkis-field-value\">{input}</div></details>";
        }
        else
        {
            $rowtpl = "<div class=\"row openkis-field\"><div class=\"col-sm-4 col-lg-3 openkis-field-title\">{title}</div><div class=\"col-sm-8 col-lg-9 openkis-field-value\">{input}</div></div>";
        }
        if (isset($titles[$f]))
        {
            $rowtpl = str_replace("{title}", htmlspecialchars($titles[$f]), $rowtpl);
        }
        $tpl .= "<!-- item_$f -->$rowtpl<!-- end_item_$f -->";
    }
    $tpl .= "<!-- end_contents -->";
    $frm->SetlayoutTemplateView($tpl);
    // HtmlShowView() renders every field of the table and the template keeps
    // only the ones of the card: hide the others for this call, so that their
    // field classes (linked caves, areas...) do not run their queries once per card
    $hidden = array();
    foreach ($frm->formvals as $fname => $fdef)
    {
        if (!in_array($fname, $fields))
        {
            $hidden[$fname] = array_key_exists('view_show', $fdef) ? $fdef['view_show'] : null;
            $frm->formvals[$fname]['view_show'] = "0";
        }
    }
    $html = $frm->HtmlShowView($values);
    foreach ($hidden as $fname => $old)
    {
        if ($old === null)
            unset($frm->formvals[$fname]['view_show']);
        else
            $frm->formvals[$fname]['view_show'] = $old;
    }
    if (trim(strip_tags((string) $html, "<img><a><iframe>")) === "")
        return "";
    return $html;
}

/**
 * Surveys of a cave visible to the user (section and per-record permissions
 * apply), every field, read once per request.
 *
 * @param string $code cadastre code of the cave
 * @return array records of ctl_surveys, empty without code or section
 */
function openkis_SheetCaveSurveys($code)
{
    global $_FN;
    static $cache = array();
    $code = trim((string) $code);
    if (isset($cache[$code]))
        return $cache[$code];
    $items = array();
    if ($code !== "" && file_exists("sections/survey") && FN_UserCanViewSection("survey"))
    {
        require_once "{$_FN['src_finis']}/modules/dbview/FNDBVIEW.php";
        $config = FN_LoadConfig("modules/dbview/config.php", "survey");
        $dbview = new FNDBVIEW($config);
        // every column (the default is the few fields of the list), so that the
        // records can be rendered without reading them again one by one
        $table = FN_XMDBTable("ctl_surveys");
        $fields = is_array($table->fields) ? implode(",", array_keys($table->fields)) : "";
        $params = array("appendquery" => "codecave LIKE '" . addslashes($code) . "'");
        if ($fields != "")
            $params['fields'] = $fields;
        $items = $dbview->GetResults($config, $params);
    }
    $cache[$code] = is_array($items) ? $items : array();
    return $cache[$code];
}

/**
 * Records of an inner table of a sheet (e.g. the surveys of a cave) rendered
 * as the generic inner tables of dbview do, with the template
 * sections/<mod>/detail_inner.tp.html, but from records already read: one
 * query for all of them instead of four queries and a template per record.
 * The records are sorted as dbview does (date, then priority, descending).
 *
 * @param string $tablename table of the records
 * @param array $records full records (every field), as read by FNDBVIEW::GetResults()
 * @param string $title title printed before the first record
 * @return string html, empty when there is no record
 */
function openkis_SheetInnerCards($tablename, $records, $title)
{
    global $_FN;
    if (!is_array($records) || !count($records))
        return "";
    $tplfile = "{$_FN['src_application']}/sections/{$_FN['mod']}/detail_inner.tp.html";
    if (!file_exists($tplfile))
        $tplfile = FN_FromTheme("modules/dbview/detail_inner.tp.html", false);
    if (!$tplfile || !file_exists($tplfile))
        return "";
    $template = file_get_contents($tplfile);
    $frm = FN_XMDBForm($tablename);
    // same starting order as the generic inner tables (records read in primary key order)
    $pk = $frm->xmltable->primarykey;
    if (!is_array($pk))
    {
        usort($records, function ($a, $b) use ($pk)
        {
            return strcmp((string) (isset($a[$pk]) ? $a[$pk] : ""), (string) (isset($b[$pk]) ? $b[$pk] : ""));
        });
    }
    if (!empty($frm->xmltable->fields['date']))
        $records = xmetadb_array_natsort_by_key($records, "date", true);
    if (!empty($frm->xmltable->fields['priority']))
        $records = xmetadb_array_natsort_by_key($records, "priority", true);
    $html = "";
    $tpvars = array("navigationbar" => array(), "url_offlineform" => "", "header" => "", "footer" => "", "innertables" => "", "title_inner" => $title);
    foreach ($records as $rec)
    {
        // only the columns of the table: with any other key GetRecordTranslated()
        // would read the record again from the database
        if (is_array($frm->xmltable->fields))
            $rec = array_intersect_key($rec, $frm->xmltable->fields);
        $frm->SetlayoutTemplateView(FN_TPL_ApplyTplString($template, $tpvars));
        $html .= $frm->HtmlShowView($frm->GetRecordTranslated($rec));
        $tpvars['title_inner'] = "";
    }
    return $html;
}

/**
 * Card of a sheet.
 *
 * @param string $title
 * @param string $body html after the header (card-body, table, map...)
 * @param array $options
 *   "count" number shown after the title, "footer" html of the card footer,
 *   "side" true for a card of the side column, "id" id of the card
 * @return string html
 */
function openkis_SheetCard($title, $body, $options = array())
{
    $class = !empty($options['side']) ? "openkis-side-card" : "openkis-card";
    $title = openkis_SheetUcfirst($title);
    if (isset($options['count']) && $options['count'] !== null)
    {
        $title .= " (" . intval($options['count']) . ")";
    }
    $id = !empty($options['id']) ? " id=\"" . htmlspecialchars($options['id']) . "\"" : "";
    $html = "<div class=\"card $class mb-3\"$id><div class=\"card-header\"><h5 class=\"mb-0\">$title</h5></div>";
    $html .= $body;
    if (!empty($options['footer']))
    {
        $html .= "<div class=\"card-footer bg-transparent\">{$options['footer']}</div>";
    }
    $html .= "</div>";
    return $html;
}

/**
 * Card listing the records linked to a sheet: title with the number of
 * records, an optional map above a striped table, an optional footer.
 *
 * @param string $title
 * @param array $head column titles; an item can be array(title, class), the
 *                    class is given to the column cells too (e.g. numbers on the right)
 * @param array $rows rows of cells html; a cell can be array(html, class)
 * @param array $options "count" (default: the rows), "map" html above the table,
 *                       "footer", "id"; "count" null hides the number
 * @return string html
 */
function openkis_SheetListCard($title, $head, $rows, $options = array())
{
    $classes = array();
    $html = "<div class=\"card-body p-0\">";
    if (!empty($options['map']))
    {
        $html .= $options['map'];
    }
    $html .= "<div class=\"table-responsive\"><table class=\"table table-sm table-striped mb-0\"><thead><tr>";
    foreach ($head as $i => $column)
    {
        $classes[$i] = is_array($column) ? $column[1] : "";
        $text = is_array($column) ? $column[0] : $column;
        $html .= "<th" . ($classes[$i] != "" ? " class=\"{$classes[$i]}\"" : "") . ">" . openkis_SheetUcfirst($text) . "</th>";
    }
    $html .= "</tr></thead><tbody>";
    foreach ($rows as $row)
    {
        $html .= "<tr>";
        foreach (array_values($row) as $i => $cell)
        {
            $class = is_array($cell) ? $cell[1] : (isset($classes[$i]) ? $classes[$i] : "");
            $text = is_array($cell) ? $cell[0] : $cell;
            $html .= "<td" . ($class != "" ? " class=\"$class\"" : "") . ">$text</td>";
        }
        $html .= "</tr>";
    }
    $html .= "</tbody></table></div></div>";
    if (!array_key_exists("count", $options))
    {
        $options['count'] = count($rows);
    }
    return openkis_SheetCard($title, $html, $options);
}

/**
 * Button that opens a linked record, last cell of the rows of the list cards.
 *
 * @param string $url
 * @return array cell for openkis_SheetListCard()
 */
function openkis_SheetViewButton($url)
{
    return array("<a class=\"btn btn-sm btn-outline-primary\" href=\"$url\">" . FN_Translate("view") . "</a>", "text-right text-end");
}

/**
 * Map of a set of caves centred and zoomed on them.
 *
 * @param array $caves records with code, latitude, longitude
 * @param string $filter query string selecting the caves on the map, e.g.
 *                       "nv_areas=IM1"; empty for the list of their codes
 * @param string $mod section of the records
 * @return string url of the map, empty when no record has coordinates
 */
function openkis_SheetCavesMapUrl($caves, $filter = "", $mod = "caves")
{
    global $_FN;
    $codes = array();
    $lats = array();
    $lons = array();
    foreach ($caves as $cave)
    {
        $codes[] = rawurlencode($cave['code']);
        if (!empty($cave['latitude']) && !empty($cave['longitude']))
        {
            $lats[] = floatval(str_replace(",", ".", $cave['latitude']));
            $lons[] = floatval(str_replace(",", ".", $cave['longitude']));
        }
    }
    if (!count($lats))
        return "";
    $lat = (max($lats) + min($lats)) / 2;
    $lon = (max($lons) + min($lons)) / 2;
    // zoom that fits the extent in a map about 800x400 px (256 px tiles), between 8 and 15
    $span = max(max($lons) - min($lons), (max($lats) - min($lats)) * 3);
    $zoom = $span > 0 ? floor(log(3 * 360 / ($span * 1.3), 2)) : 15;
    $zoom = max(8, min(15, $zoom));
    if ($filter == "")
    {
        $filter = "filter_code=" . implode(",", array_unique($codes));
    }
    return "{$_FN['siteurl']}bs_map.htm?mod=$mod&zoom=$zoom&lat=" . sprintf("%.6F", $lat) . "&lon=" . sprintf("%.6F", $lon) . "&$filter";
}

/**
 * Options of openkis_SheetListCard() for a map at the top of the card and a
 * footer link that opens it in a new page.
 *
 * @param string $url url of the map, see openkis_SheetCavesMapUrl()
 * @return array "map" and "footer", empty without url
 */
function openkis_SheetMapOptions($url)
{
    if ($url == "")
        return array();
    $url = htmlspecialchars($url);
    return array("map" => "<iframe style=\"border:0px;width:100%;height:400px;display:block\" src=\"$url\"></iframe>",
        "footer" => "<a class=\"btn btn-sm btn-outline-secondary\" target=\"_blank\" href=\"$url\">" . FN_Translate("show on the map") . "</a>");
}

/**
 * Card listing caves (or other records with code and name: artificial
 * cavities, springs) with their map above the table.
 *
 * @param string $title
 * @param array $caves records: id, code, name and the fields of the columns,
 *                     latitude and longitude for the map
 * @param array $options
 *   "mod" section of the records (default caves), for the links and the map;
 *   "columns" fields shown after the name, among comune, elevation,
 *             lenght_total, depth_total (default all);
 *   "extra" more columns: list of array("title" => ..., "class" => ..., "values" => array(code => html));
 *   "map" query string selecting the records on the map ("" their codes), false for no map
 * @return string html, empty when there is no record
 */
function openkis_SheetCavesCard($title, $caves, $options = array())
{
    if (!is_array($caves) || !count($caves))
        return "";
    $mod = isset($options['mod']) ? $options['mod'] : "caves";
    $columns = isset($options['columns']) ? $options['columns'] : array("comune", "elevation", "lenght_total", "depth_total");
    $extra = isset($options['extra']) ? $options['extra'] : array();
    usort($caves, function ($a, $b)
    {
        return strnatcmp($a['code'], $b['code']);
    });
    $titles = array("comune" => FN_Translate("municipality"), "elevation" => FN_Translate("elevation"),
        "lenght_total" => FN_Translate("development"), "depth_total" => FN_Translate("difference in level"));
    $head = array(FN_Translate("number"), FN_Translate("name"));
    foreach ($columns as $column)
    {
        $head[] = ($column == "comune") ? $titles[$column] : array($titles[$column], "text-right text-end text-nowrap");
    }
    foreach ($extra as $column)
    {
        $head[] = array($column['title'], isset($column['class']) ? $column['class'] : "");
    }
    $head[] = array("", "text-right text-end");
    $rows = array();
    foreach ($caves as $cave)
    {
        $url = FN_RewriteLink("index.php?mod=$mod&op=view&id={$cave['id']}");
        $row = array(array(htmlspecialchars($cave['code']), "text-nowrap openkis-card-code"),
            "<a href=\"$url\">" . htmlspecialchars($cave['name']) . "</a>");
        foreach ($columns as $column)
        {
            $value = isset($cave[$column]) ? trim((string) $cave[$column]) : "";
            if ($column == "elevation")
            {
                // the elevation of the sheet, else the one from the map or the GPS
                foreach (array("elevation", "elevation_map", "elevation_gps") as $f)
                {
                    if (isset($cave[$f]) && trim((string) $cave[$f]) !== "" && trim((string) $cave[$f]) !== "0")
                    {
                        $value = trim((string) $cave[$f]);
                        break;
                    }
                }
            }
            if ($column != "comune" && $value !== "")
            {
                $value .= " m";
            }
            $row[] = htmlspecialchars($value);
        }
        foreach ($extra as $column)
        {
            $row[] = isset($column['values'][$cave['code']]) ? $column['values'][$cave['code']] : "";
        }
        $row[] = openkis_SheetViewButton($url);
        $rows[] = $row;
    }
    $listoptions = array();
    if (!isset($options['map']) || $options['map'] !== false)
    {
        $listoptions = openkis_SheetMapOptions(openkis_SheetCavesMapUrl($caves, isset($options['map']) ? $options['map'] : "", $mod));
    }
    return openkis_SheetListCard($title, $head, $rows, $listoptions);
}

/**
 * Records of a dbview section with the given codes, visible to the user (section
 * and per-record permissions apply).
 *
 * @param string $mod section (caves, artificials, springs)
 * @param array $codes
 * @param string $fields fields to read
 * @return array records, empty when the section is missing or hidden to the user
 */
function openkis_SheetRecordsByCode($mod, $codes, $fields)
{
    global $_FN;
    $codes = array_unique(array_filter(array_map("trim", $codes), "strlen"));
    if (!count($codes) || !file_exists("sections/$mod") || !FN_UserCanViewSection($mod))
        return array();
    require_once "{$_FN['src_finis']}/modules/dbview/FNDBVIEW.php";
    $config = FN_LoadConfig("modules/dbview/config.php", $mod);
    $dbview = new FNDBVIEW($config);
    $in = array();
    foreach ($codes as $code)
        $in[] = "'" . addslashes($code) . "'";
    $items = $dbview->GetResults($config, array("fields" => $fields, "appendquery" => "code IN (" . implode(",", $in) . ")"));
    return is_array($items) ? $items : array();
}

/**
 * Card listing bibliography items.
 *
 * @param array $items id, title, authors, year and optionally magazine, in the order to show
 * @return string html, empty when there is no item
 */
function openkis_SheetBibliographyCard($items)
{
    if (!is_array($items) || !count($items))
        return "";
    $rows = array();
    foreach ($items as $item)
    {
        $url = FN_RewriteLink("index.php?mod=bibliography&op=view&id={$item['id']}");
        $title = "<a href=\"$url\">" . openkis_SheetEscape($item['title']) . "</a>";
        if (!empty($item['magazine']))
        {
            $title .= " <span class=\"text-muted\">- " . openkis_SheetEscape($item['magazine']) . "</span>";
        }
        $rows[] = array(openkis_SheetEscape($item['year']), $title, openkis_SheetEscape($item['authors']), openkis_SheetViewButton($url));
    }
    $head = array(array(FN_Translate("year"), "text-nowrap"), FN_Translate("title"), FN_Translate("authors"), array("", "text-right text-end"));
    return openkis_SheetListCard(FN_Translate("bibliography"), $head, $rows, array("id" => "card_bibliography"));
}

/**
 * Side cards with the map of a point record (cave, spring), the links to
 * route planners and the KML, and its WGS84 coordinates in decimal degrees,
 * degrees minutes seconds and UTM.
 *
 * @param string $mod section of the record
 * @param array $row record with latitude, longitude, coordinates_type, code, name, id
 * @return array html blocks for $GLOBALS['openkis_view_side'], empty without coordinates
 */
function openkis_SheetMapCards($mod, $row)
{
    global $_FN;
    if (empty($row['latitude']) || empty($row['longitude']))
        return array();
    $lat = openkis_floatfrmt($row['latitude']);
    $lon = openkis_floatfrmt($row['longitude']);
    $baselayer = "";
    $types = (isset($row['coordinates_type']) ? $row['coordinates_type'] : "") . (isset($row['original_coordinates_type']) ? $row['original_coordinates_type'] : "");
    if (false !== strstr($types, "IGM"))
    {
        $baselayer = "IGM 1:25000";
    }
    $blocks = array();
    //---- map card ----------------------------------------------------------->
    // the map reads "history" as the id of a cave (layer of its past coordinates)
    $history = ($mod == "caves") ? "&history={$row['id']}" : "";
    $body = "<iframe style=\"border:0px;width:100%;height:300px;\" src=\"{$_FN['siteurl']}bs_map.htm?mod=$mod&baselayer={$baselayer}&point=circle&lat=$lat&lon=$lon&zoom=17$history\"></iframe>";
    $body .= "<div class=\"card-body p-2\">";
    $body .= "<div class=\"small text-muted mb-1\">" . FN_Translate("calculate the route") . "</div>";
    $body .= "<a class=\"btn btn-sm btn-outline-secondary m-1\" target=\"_blank\" href=\"//www.google.it/maps/dir//$lat,$lon/@$lat,$lon,18z\">GMaps</a>";
    $body .= "<a class=\"btn btn-sm btn-outline-secondary m-1\" target=\"_blank\" href=\"https://maps.openrouteservice.org/directions?n1=44.075342&n2=9.807849&n3=17&a=null,null,$lat,$lon&b=0&c=0&k1=it&k2=km\">OpenRouteService</a>";
    $body .= "<a class=\"btn btn-sm btn-outline-secondary m-1\" target=\"_blank\" href=\"https://www.openstreetmap.org/directions?engine=graphhopper_foot&route=$lat%2C$lon%3B$lat%2C$lon\">OpenStreetmap</a>";
    if (!empty($row['code']))
    {
        $forcename = openkis_TextToAscii($row['name']);
        $body .= "<a class=\"btn btn-sm btn-outline-secondary m-1\" target=\"_blank\" href=\"{$_FN['siteurl']}openkis_kml.php?mod=$mod&big_icons=1&filter_code=" . urlencode($row['code']) . "&filename=$forcename&absolute=1\">KML</a>";
    }
    $body .= "</div>";
    $blocks[] = openkis_SheetCard(FN_Translate("map"), $body, array("side" => true));
    //---- map card -----------------------------------------------------------<
    //---- coordinates card: decimal degrees, sexagesimal (DMS) and UTM ------->
    $position = openkis_GetItemPosition($row);
    $body = "<div class=\"card-body p-2 small\">";
    $body .= "<div class=\"text-muted\">" . FN_Translate("decimal degrees (Lon, Lat)") . "</div><input class=\"form-control form-control-sm mb-2\" readonly value=\"$lon,$lat\" onclick=\"this.select();\" />";
    if (!empty($position['lon_dms']))
    {
        $body .= "<div class=\"text-muted\">" . FN_Translate("sexagesimal degrees (Lon, Lat)") . "</div><input class=\"form-control form-control-sm mb-2\" readonly value=\"" . htmlspecialchars("{$position['lon_dms']},{$position['lat_dms']}") . "\" onclick=\"this.select();\" />";
    }
    if (!empty($position['x']))
    {
        $body .= "<div class=\"text-muted\">" . FN_Translate("UTM (X, Y, zone)") . "</div><input class=\"form-control form-control-sm\" readonly value=\"" . htmlspecialchars("{$position['x']},{$position['y']},{$position['zone']}") . "\" onclick=\"this.select();\" />";
    }
    $body .= "</div>";
    $blocks[] = openkis_SheetCard(FN_Translate("coordinates WGS84"), $body, array("side" => true));
    //---- coordinates card ---------------------------------------------------<
    return $blocks;
}

/**
 * Card of the side column with the photos of the record.
 *
 * @param XMETADBForm $frm
 * @param array $values translated record
 * @param array $photos photo field => field of its author ("" for none)
 * @param string $title
 * @return string html, empty without photos
 */
function openkis_SheetPhotoCard($frm, $values, $photos, $title)
{
    $tpl = "<!-- contents --><!-- group --> <!-- end_group --><!-- endgroup --> <!-- end_endgroup --><!-- item --> <!-- end_item -->";
    foreach ($photos as $photo => $author)
    {
        $tpl .= "<!-- item_$photo --><div class=\"text-center openkis-photo\">{input}</div><!-- end_item_$photo -->";
        if ($author != "")
            $tpl .= "<!-- item_$author --><div class=\"text-center text-muted small mt-1\">{input}</div><!-- end_item_$author -->";
    }
    $tpl .= "<!-- end_contents -->";
    $frm->SetlayoutTemplateView($tpl);
    $html = $frm->HtmlShowView($values);
    if (trim(strip_tags((string) $html, "<img>")) === "")
        return "";
    return openkis_SheetCard($title, "<div class=\"card-body p-2\">$html</div>", array("side" => true));
}

/**
 * Prints the sheet of a record: title block, the cards of the fields in the
 * main column, the photos and the blocks of $GLOBALS['openkis_view_side'] in
 * the side column. The side column and the row are left open: detail.tp.html
 * prints {innertables} there and closes both.
 * Fields of the table that are in no card and not excluded go into a final
 * "other data" card.
 *
 * @param XMETADBForm $frm form of the table
 * @param array $values translated record
 * @param array $sheet
 *   "title" html of the name, "place" html under the name, "badges" html next
 *   to the name, "icon" url of the icon;
 *   "cards" card title => field names; "exclude" fields never shown in a card;
 *   "longtext" fields shown as blocks; "author" fields shown as credit lines;
 *   "titles" field => title replacing the one of the table;
 *   "photos" photo field => author field, "photos_title" title of their card
 */
function openkis_SheetRender($frm, $values, $sheet)
{
    $sheet = array_merge(array("title" => "", "place" => "", "badges" => "", "icon" => "", "cards" => array(), "exclude" => array(),
        "longtext" => array(), "author" => array(), "titles" => array(), "photos" => array(), "photos_title" => ""), $sheet);
    //---- leftover fields go into a final "other data" card ------------------->
    $cards = $sheet['cards'];
    $used = array_merge($sheet['exclude'], array_keys($sheet['photos']), array_values($sheet['photos']));
    foreach ($cards as $cardfields)
    {
        $used = array_merge($used, $cardfields);
    }
    $other = array();
    foreach ($frm->formvals as $fname => $fdef)
    {
        if (!isset($fdef['name']) || in_array($fname, $used))
            continue;
        if (isset($fdef['frm_show']) && (string) $fdef['frm_show'] === "0" && empty($fdef['view_show']))
            continue;
        $other[] = $fname;
    }
    if (count($other))
    {
        $cards["other data"] = $other;
    }
    //---- leftover fields -----------------------------------------------------<
    //---- title block: name and place on the left, badges and icon on the right ->
    echo "<div class=\"openkis-sheet-title d-flex align-items-start mb-3\">";
    echo "<div class=\"openkis-sheet-heading\">";
    echo "<h2 class=\"openkis-sheet-name mb-1\">{$sheet['title']}</h2>";
    if ($sheet['place'] != "")
        echo "<div class=\"text-muted\">{$sheet['place']}</div>";
    echo "</div>";
    echo "<div class=\"openkis-sheet-tags d-flex align-items-start\">";
    echo "<div class=\"openkis-sheet-badges\">{$sheet['badges']}</div>";
    if ($sheet['icon'] != "")
        echo "<img class=\"openkis-sheet-icon\" src=\"" . htmlspecialchars($sheet['icon']) . "\" alt=\"\" />";
    echo "</div></div>";
    //---- title block ---------------------------------------------------------<
    echo "<div class=\"row openkis-sheet\">";
    echo "<div class=\"col-lg-8 openkis-sheet-main\">";
    foreach ($cards as $cardtitle => $cardfields)
    {
        $html = openkis_ViewCardFields($frm, $values, $cardfields, $sheet['longtext'], $sheet['author'], $sheet['titles']);
        if ($html === "")
            continue;
        $cardid = "card_" . preg_replace('/[^a-z0-9]+/', '_', strtolower($cardtitle));
        echo openkis_SheetCard(FN_Translate($cardtitle), "<div class=\"card-body\">$html</div>", array("id" => $cardid));
    }
    echo "</div>";
    echo "<div class=\"col-lg-4 openkis-sheet-side\">";
    if (count($sheet['photos']))
    {
        echo openkis_SheetPhotoCard($frm, $values, $sheet['photos'], FN_Translate($sheet['photos_title']));
    }
    //---- side blocks prepared by viewfooter.php ------------------------------>
    if (!empty($GLOBALS['openkis_view_side']) && is_array($GLOBALS['openkis_view_side']))
    {
        foreach ($GLOBALS['openkis_view_side'] as $block)
        {
            echo $block;
        }
    }
    $GLOBALS['openkis_view_side'] = array();
    //---- side blocks prepared by viewfooter.php ------------------------------<
}
