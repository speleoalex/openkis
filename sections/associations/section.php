<?php
// the "groups" field of the surveys (multicave) links here as op=view&id=CODE
if (FN_GetParam("op", $_GET, "flat") == "view" && FN_GetParam("id", $_GET, "flat") != "")
{
    $_GET["op___xdb_ctl_associations"] = "view";
    $_GET["pk___xdb_ctl_associations"] = FN_GetParam("id", $_GET, "flat");
}
if (!function_exists("openkis_AssociationsCards"))
{
    /**
     * Names of the Italian provinces by code, for the headings of the list.
     *
     * @return array
     */
    function openkis_AssociationsProvinces()
    {
        return array(
            "AG" => "Agrigento", "AL" => "Alessandria", "AN" => "Ancona", "AO" => "Aosta", "AP" => "Ascoli Piceno",
            "AQ" => "L'Aquila", "AR" => "Arezzo", "AT" => "Asti", "AV" => "Avellino", "BA" => "Bari",
            "BG" => "Bergamo", "BI" => "Biella", "BL" => "Belluno", "BN" => "Benevento", "BO" => "Bologna",
            "BR" => "Brindisi", "BS" => "Brescia", "BT" => "Barletta-Andria-Trani", "BZ" => "Bolzano", "CA" => "Cagliari",
            "CB" => "Campobasso", "CE" => "Caserta", "CH" => "Chieti", "CL" => "Caltanissetta", "CN" => "Cuneo",
            "CO" => "Como", "CR" => "Cremona", "CS" => "Cosenza", "CT" => "Catania", "CZ" => "Catanzaro",
            "EN" => "Enna", "FC" => "Forlì-Cesena", "FE" => "Ferrara", "FG" => "Foggia", "FI" => "Firenze",
            "FM" => "Fermo", "FR" => "Frosinone", "GE" => "Genova", "GO" => "Gorizia", "GR" => "Grosseto",
            "IM" => "Imperia", "IS" => "Isernia", "KR" => "Crotone", "LC" => "Lecco", "LE" => "Lecce",
            "LI" => "Livorno", "LO" => "Lodi", "LT" => "Latina", "LU" => "Lucca", "MB" => "Monza e Brianza",
            "MC" => "Macerata", "ME" => "Messina", "MI" => "Milano", "MN" => "Mantova", "MO" => "Modena",
            "MS" => "Massa-Carrara", "MT" => "Matera", "NA" => "Napoli", "NO" => "Novara", "NU" => "Nuoro",
            "OR" => "Oristano", "PA" => "Palermo", "PC" => "Piacenza", "PD" => "Padova", "PE" => "Pescara",
            "PG" => "Perugia", "PI" => "Pisa", "PN" => "Pordenone", "PO" => "Prato", "PR" => "Parma",
            "PT" => "Pistoia", "PU" => "Pesaro e Urbino", "PV" => "Pavia", "PZ" => "Potenza", "RA" => "Ravenna",
            "RC" => "Reggio Calabria", "RE" => "Reggio Emilia", "RG" => "Ragusa", "RI" => "Rieti", "RM" => "Roma",
            "RN" => "Rimini", "RO" => "Rovigo", "SA" => "Salerno", "SI" => "Siena", "SO" => "Sondrio",
            "SP" => "La Spezia", "SR" => "Siracusa", "SS" => "Sassari", "SU" => "Sud Sardegna", "SV" => "Savona",
            "TA" => "Taranto", "TE" => "Teramo", "TN" => "Trento", "TO" => "Torino", "TP" => "Trapani",
            "TR" => "Terni", "TS" => "Trieste", "TV" => "Treviso", "UD" => "Udine", "VA" => "Varese",
            "VB" => "Verbano-Cusio-Ossola", "VC" => "Vercelli", "VE" => "Venezia", "VI" => "Vicenza", "VR" => "Verona",
            "VT" => "Viterbo", "VV" => "Vibo Valentia"
        );
    }

    /**
     * Short code of a club for its badge and for the box without a logo: the
     * acronym field, else the primary key when it is a code (Liguria: GSS, SIAL),
     * else the initials of the name (Lombardia: the key is the name).
     *
     * @param array $item
     * @param string $pk
     * @return array (text, true when it is a real code)
     */
    function openkis_AssociationsSigla($item, $pk)
    {
        if (!empty($item['acronym']))
            return array(trim($item['acronym']), true);
        $key = isset($item[$pk]) ? trim($item[$pk]) : "";
        $name = isset($item['name']) ? trim($item['name']) : "";
        if ($key != "" && $key != $name && strlen($key) <= 12 && preg_match('/^[A-Za-z0-9_.]+$/', $key))
            return array($key, true);
        $initials = "";
        foreach (preg_split('/[\s"\'.,()\-]+/u', $name) as $word)
        {
            if (mb_strlen($word) > 2 || (mb_strlen($word) > 0 && mb_strtoupper($word) === $word))
                $initials .= mb_strtoupper(mb_substr($word, 0, 1));
        }
        return array(mb_substr($initials, 0, 4), false);
    }

    /**
     * The clubs as cards with the logo in sight, grouped by province (headings
     * only when there is more than one), with a filter by text and by active
     * clubs. The rules are in style.css of the section (inlined by finis).
     *
     * @param bool $canedit
     * @return string
     */
    function openkis_AssociationsCards($canedit)
    {
        global $_FN;
        $it = ($_FN['lang'] == "it");
        $t = array(
            "search" => $it ? "Cerca un gruppo, una città, un bollettino" : "Search a club, a town, a journal",
            "onlyactive" => $it ? "Solo gruppi attivi" : "Active clubs only",
            "active" => $it ? "Attivo" : "Active",
            "clubs" => $it ? "gruppi" : "clubs",
            "of" => $it ? "di" : "of",
            "none" => $it ? "Nessun gruppo trovato" : "No club found",
            "noprov" => $it ? "Provincia non indicata" : "Province not given",
            "journal" => $it ? "Bollettino/Rivista" : "Journal",
            "website" => $it ? "Sito web" : "Website",
            "new" => $it ? "Nuovo gruppo" : "New club",
            "table" => $it ? "Vista tabella" : "Table view",
            "edit" => $it ? "Modifica" : "Edit",
            "logo" => $it ? "Logo di" : "Logo of",
        );
        $table = FN_XMDBTable("ctl_associations");
        $pk = $table->primarykey;
        $items = $table->GetRecords();
        if (!is_array($items))
            $items = array();
        $provinces = openkis_AssociationsProvinces();
        $groups = array();
        $countactive = 0;
        foreach ($items as $item)
        {
            $prov = isset($item['provincia']) ? strtoupper(trim($item['provincia'])) : "";
            $groups[$prov][] = $item;
            if (!empty($item['active']))
                $countactive++;
        }
        // provinces in alphabetical order of their names, the clubs without one at the end
        uksort($groups, function ($a, $b) use ($provinces) {
            if ($a == "" || $b == "")
                return ($a == "") - ($b == "");
            $na = isset($provinces[$a]) ? $provinces[$a] : $a;
            $nb = isset($provinces[$b]) ? $provinces[$b] : $b;
            return strcasecmp($na, $nb);
        });
        $headings = count($groups) > 1;
        $total = count($items);
        $h = function ($s) {
            return htmlspecialchars((string) $s, ENT_QUOTES, "UTF-8");
        };

        $html = "<div class=\"okis-assoc\" data-okis-assoc>\n";
        $html .= "<div class=\"okis-assoc-tools\">";
        $html .= "<input type=\"search\" class=\"form-control okis-assoc-search\" placeholder=\"{$h($t['search'])}\" aria-label=\"{$h($t['search'])}\">";
        if ($countactive > 0 && $countactive < $total)
            $html .= "<label class=\"okis-assoc-onlyactive\"><input type=\"checkbox\"> {$h($t['onlyactive'])}</label>";
        $html .= "<span class=\"okis-assoc-count\" aria-live=\"polite\" data-total=\"$total\" data-of=\"{$h($t['of'])}\" data-clubs=\"{$h($t['clubs'])}\">$total {$h($t['clubs'])}</span>";
        if ($canedit)
        {
            $urlnew = FN_RewriteLink("index.php?mod={$_FN['mod']}&op___xdb_ctl_associations=insnew");
            $urltable = FN_RewriteLink("index.php?mod={$_FN['mod']}&table=1");
            $html .= "<span class=\"okis-assoc-admin\"><a class=\"btn btn-sm btn-primary\" href=\"{$h($urlnew)}\">{$h($t['new'])}</a> <a class=\"btn btn-sm btn-outline-secondary\" href=\"{$h($urltable)}\">{$h($t['table'])}</a></span>";
        }
        $html .= "</div>\n";

        foreach ($groups as $prov => $list)
        {
            usort($list, function ($a, $b) {
                return strnatcasecmp(isset($a['name']) ? $a['name'] : "", isset($b['name']) ? $b['name'] : "");
            });
            $html .= "<section class=\"okis-assoc-prov\">";
            if ($headings)
            {
                if ($prov == "")
                    $title = $h($t['noprov']);
                else
                    $title = isset($provinces[$prov]) ? $h($provinces[$prov]) . " <span class=\"okis-assoc-provcode\">{$h($prov)}</span>" : $h($prov);
                $html .= "<h2 class=\"okis-assoc-provtitle\">$title</h2>";
            }
            $html .= "<div class=\"okis-assoc-grid\">\n";
            foreach ($list as $item)
            {
                $key = isset($item[$pk]) ? $item[$pk] : "";
                $name = isset($item['name']) && trim($item['name']) != "" ? trim($item['name']) : $key;
                list($sigla, $realcode) = openkis_AssociationsSigla($item, $pk);
                if (preg_match('/^[A-Za-z0-9_]+$/', $key))
                    $url = FN_RewriteLink("index.php?mod={$_FN['mod']}&op=view&id=$key");
                else
                    $url = FN_RewriteLink("index.php?mod={$_FN['mod']}&op___xdb_ctl_associations=view&pk___xdb_ctl_associations=" . urlencode($key));
                $active = !empty($item['active']);

                $logo = "";
                if (!empty($item['logo']))
                {
                    $thumb = $table->get_thumb($item, "logo");
                    $file = $table->get_file($item, "logo");
                    if ($thumb || $file)
                    {
                        $src = $thumb ? $thumb : $file;
                        // in srcset a space ends the address and a comma the candidate: the
                        // files of Lombardia are in folders named after the club ("A.S. Bresciana")
                        $enc = function ($u) {
                            return str_replace(array(" ", ","), array("%20", "%2C"), $u);
                        };
                        $srcset = ($thumb && $file && $thumb != $file) ? " srcset=\"{$h($enc($thumb))} 1x, {$h($enc($file))} 2x\"" : "";
                        $logo = "<img src=\"{$h($src)}\"$srcset alt=\"{$h($t['logo'] . " " . $name)}\" loading=\"lazy\" decoding=\"async\">";
                    }
                }
                if ($logo == "")
                {
                    $len = mb_strlen($sigla);
                    $size = $len <= 4 ? "l" : ($len <= 7 ? "m" : "s");
                    $logo = "<span class=\"okis-assoc-initials okis-assoc-initials-$size\" aria-hidden=\"true\">{$h($sigla)}</span>";
                }

                $city = isset($item['city']) ? trim($item['city']) : "";
                $where = $city;
                if ($prov != "" && ($city == "" || !$headings))
                    $where .= ($where != "" ? " " : "") . "($prov)";

                $html .= "<article class=\"okis-assoc-card" . ($active ? " okis-assoc-isactive" : "") . "\"" . ($active ? " data-active=\"1\"" : "") . ">";
                $html .= "<div class=\"okis-assoc-logo" . (strpos($logo, "<img") === 0 ? "" : " okis-assoc-nologo") . "\">$logo</div>";
                $html .= "<div class=\"okis-assoc-body\">";
                if (($realcode && $sigla != "") || $active)
                {
                    $html .= "<div class=\"okis-assoc-badges\">";
                    if ($realcode && $sigla != "")
                        $html .= "<span class=\"okis-assoc-code\">{$h($sigla)}</span>";
                    if ($active)
                        $html .= "<span class=\"okis-assoc-active\">{$h($t['active'])}</span>";
                    $html .= "</div>";
                }
                $html .= "<h3 class=\"okis-assoc-name\"><a href=\"{$h($url)}\">{$h($name)}</a></h3>";
                if ($where != "")
                    $html .= "<div class=\"okis-assoc-where\">{$h($where)}</div>";
                if (!empty($item['journal']))
                    $html .= "<div class=\"okis-assoc-journal\" title=\"{$h($t['journal'])}\"><span class=\"okis-assoc-sr\">{$h($t['journal'])}: </span>{$h(trim($item['journal']))}</div>";
                $links = "";
                $website = isset($item['website']) ? trim($item['website']) : "";
                if ($website != "")
                {
                    if (!preg_match('#^https?://#i', $website))
                        $website = "http://$website";
                    $host = preg_replace('#^https?://(www\.)?#i', "", rtrim($website, "/"));
                    $links .= "<a class=\"okis-assoc-web\" href=\"{$h($website)}\" rel=\"noopener\" title=\"{$h($t['website'])}\">{$h($host)}</a>";
                }
                if ($canedit)
                {
                    $urledit = FN_RewriteLink("index.php?mod={$_FN['mod']}&op___xdb_ctl_associations=insnew&pk___xdb_ctl_associations=" . urlencode($key));
                    $links .= "<a class=\"okis-assoc-edit\" href=\"{$h($urledit)}\">{$h($t['edit'])}</a>";
                }
                if ($links != "")
                    $html .= "<div class=\"okis-assoc-links\">$links</div>";
                $html .= "</div></article>\n";
            }
            $html .= "</div></section>\n";
        }
        $html .= "<p class=\"okis-assoc-none\"" . ($total ? " hidden" : "") . ">{$h($t['none'])}</p>\n";
        $html .= "</div>\n";
        $html .= <<<'JS'
    <script>
    (function () {
        var root = document.querySelector("[data-okis-assoc]");
        if (!root) return;
        var input = root.querySelector(".okis-assoc-search");
        var only = root.querySelector(".okis-assoc-onlyactive input");
        var count = root.querySelector(".okis-assoc-count");
        var none = root.querySelector(".okis-assoc-none");
        var cards = Array.prototype.slice.call(root.querySelectorAll(".okis-assoc-card"));
        var plain = function (s) {
            return (s || "").toLowerCase().normalize("NFD").replace(/[̀-ͯ]/g, "");
        };
        cards.forEach(function (c) { c._okisText = plain(c.textContent); });
        function apply() {
            var words = plain(input.value).split(/\s+/).filter(Boolean);
            var onlyActive = only && only.checked;
            var shown = 0;
            cards.forEach(function (c) {
                var ok = (!onlyActive || c.hasAttribute("data-active")) && words.every(function (w) { return c._okisText.indexOf(w) >= 0; });
                c.hidden = !ok;
                if (ok) shown++;
            });
            root.querySelectorAll(".okis-assoc-prov").forEach(function (s) {
                s.hidden = !s.querySelector(".okis-assoc-card:not([hidden])");
            });
            var total = count.getAttribute("data-total");
            count.textContent = (shown == total ? total : shown + " " + count.getAttribute("data-of") + " " + total) + " " + count.getAttribute("data-clubs");
            none.hidden = shown > 0;
        }
        input.addEventListener("input", apply);
        if (only) only.addEventListener("change", apply);
        if (input.value || (only && only.checked)) apply();
    })();
    </script>
JS;
        return $html;
    }
}

// everyone can read the list, only the section editors (group_edit) can change it
$canedit = FN_UserCanEditSection();
$editorparams = array(
    "enableview" => true,
    "enableedit" => $canedit,
    "enablenew" => $canedit,
    "enabledelete" => $canedit,
    "fields" => "name|logo|provincia|active|journal",
    "imagesize" => 48,
    "recordsperpage" => 0,
    "defaultorder" => "provincia,name"
);
// the list is shown as cards (openkis_AssociationsCards); the editor of finis
// shows a sheet, the forms and, for the editors, the old table (&table=1,
// kept in its own links: sorting, delete)
$tablemode = $canedit && FN_GetParam("table", $_GET, "flat") != "";
$editing = FN_GetParam("op___xdb_ctl_associations", $_GET, "flat") != "" || FN_GetParam("pk___xdb_ctl_associations", $_GET, "flat") != "" || !empty($_POST);
// the links of the editor (back, list, edit, sorting, the action of the form)
// are its parameters added to this address: a full address of the list,
// because the sheet is also opened as .../associations/view/CODE/ (cards,
// "groups" of the surveys) and a link like "?mod=..." stayed on that sheet
$listurl = FN_RewriteLink("index.php?mod={$_FN['mod']}" . ($tablemode ? "&table=1" : ""), "&", true);
if (preg_match('#^https?://#i', $listurl))
{
    $editorparams["link"] = $listurl;
}
elseif ($tablemode)
{
    $editorparams["link"] = "mod={$_FN['mod']}&amp;opt=&amp;table=1";
}
if (!$tablemode && $editing)
{
    // after saving stay on the form (with the message): its "back" leads to the cards
    $editorparams["list_onsave"] = 0;
}
if ($tablemode || $editing)
{
    FN_XMETATableEditor("ctl_associations", $editorparams);
}
else
{
    echo openkis_AssociationsCards($canedit);
}
