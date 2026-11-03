<?php

/**
 * Grid/list switch for the results of a dbview section that uses the list
 * template of the module: #pageresults with the record cards of finis
 * (.dbview-cards, .dbview-card, .dbview-card-media, .card-body/.card-title,
 * .dbview-card-values), or a site/theme override of the older template
 * (.card-group, .card with .card-body/.card-title).
 *
 * The caves section has the same switch in its own list.tp.html; the other
 * sections get it from their top.php, without a copy of the template:
 * the switch buttons are added above the results by a script, and the list
 * mode is a class on #pageresults, which call_ajax keeps (it replaces only
 * the content) when the page or the filters change.
 */
defined('_FNEXEC') or die('Restricted access');

/**
 * Html (style and script) of the grid/list switch of the section results.
 * The choice of the visitor is remembered by the browser per section
 * (localStorage "openkis_view_<section>"); without a choice $default is used.
 *
 * @param string $section section id, used for the remembered choice
 * @param string $default "list" or "grid"
 * @return string html, to print before or after the results
 */
function openkis_ListViewSwitch($section, $default = "list")
{
    $key = json_encode("openkis_view_" . preg_replace('/[^A-Za-z0-9_]/', '', $section));
    $default = json_encode($default === "grid" ? "grid" : "list");
    $grid = htmlspecialchars(FN_Translate("grid view"), ENT_QUOTES, "UTF-8");
    $list = htmlspecialchars(FN_Translate("list view"), ENT_QUOTES, "UTF-8");
    $buttons = json_encode("<button type=\"button\" class=\"btn btn-outline-secondary\" data-view=\"grid\" title=\"$grid\" aria-label=\"$grid\"><svg viewBox=\"0 0 16 16\" fill=\"currentColor\" aria-hidden=\"true\"><rect x=\"1\" y=\"1\" width=\"6\" height=\"6\" rx=\"1\"/><rect x=\"9\" y=\"1\" width=\"6\" height=\"6\" rx=\"1\"/><rect x=\"1\" y=\"9\" width=\"6\" height=\"6\" rx=\"1\"/><rect x=\"9\" y=\"9\" width=\"6\" height=\"6\" rx=\"1\"/></svg></button>"
        . "<button type=\"button\" class=\"btn btn-outline-secondary\" data-view=\"list\" title=\"$list\" aria-label=\"$list\"><svg viewBox=\"0 0 16 16\" fill=\"currentColor\" aria-hidden=\"true\"><rect x=\"1\" y=\"2\" width=\"14\" height=\"2.2\" rx=\"1\"/><rect x=\"1\" y=\"6.9\" width=\"14\" height=\"2.2\" rx=\"1\"/><rect x=\"1\" y=\"11.8\" width=\"14\" height=\"2.2\" rx=\"1\"/></svg></button>");
    return <<<HTML
<style>
    .openkis-viewswitch { float: right; margin-left: .5rem; }
    .openkis-viewswitch .btn { line-height: 1; padding: .35rem .55rem; }
    .openkis-viewswitch svg { width: 16px; height: 16px; vertical-align: -2px; }
    #pageresults:not(.openkis-view-list) .openkis-viewswitch [data-view="grid"],
    #pageresults.openkis-view-list .openkis-viewswitch [data-view="list"] { color: #fff; background-color: #6c757d; border-color: #6c757d; }
    #pageresults.openkis-view-list .dbview-cards { display: block; margin-bottom: 1rem; }
    #pageresults.openkis-view-list .dbview-card { flex-direction: row; flex-wrap: wrap; align-items: center; height: auto; border-style: solid; border-color: rgba(0, 0, 0, .125); border-width: 0 0 1px 0; border-radius: 0; box-shadow: none; transform: none; }
    #pageresults.openkis-view-list .dbview-cards > div:first-child .dbview-card { border-top-width: 1px; }
    #pageresults.openkis-view-list .dbview-card-media { display: none; }
    #pageresults.openkis-view-list .dbview-card .card-body { display: flex; flex-wrap: wrap; align-items: baseline; column-gap: 1rem; text-align: left; }
    #pageresults.openkis-view-list .dbview-card .card-title { flex: 0 1 24rem; min-width: 0; }
    #pageresults.openkis-view-list .dbview-card-values { display: flex; flex-wrap: wrap; column-gap: 1rem; }
    #pageresults.openkis-view-list .dbview-card-values > div { padding: 0; gap: .3rem; border: 0; }
    #pageresults.openkis-view-list .dbview-card-values dd { text-align: left; }
    #pageresults.openkis-view-list .dbview-card .card-footer .dbview-card-view { flex: none; }
    #pageresults.openkis-view-list .card-group { display: block; }
    #pageresults.openkis-view-list .card-group > div { width: 100%; max-width: 100%; padding: 0; }
    #pageresults.openkis-view-list .card { flex-direction: row; flex-wrap: wrap; align-items: center; height: auto !important; margin-bottom: 0 !important; border-style: solid; border-color: rgba(0, 0, 0, .125); border-width: 0 0 1px 0; border-radius: 0; box-shadow: none; }
    #pageresults.openkis-view-list .card-group > div:first-child .card { border-top-width: 1px; }
    #pageresults.openkis-view-list .card-img-top { display: none; }
    #pageresults.openkis-view-list .card-body { flex: 1 1 20rem; min-width: 0; padding: .3rem .5rem; font-size: .875rem; }
    #pageresults.openkis-view-list .card-title { display: inline-block; min-width: 18rem; margin: 0 .5rem 0 0; font-size: 1rem; font-weight: 600; }
    #pageresults.openkis-view-list .card-body br { display: none; }
    #pageresults.openkis-view-list .card-body > b { margin-left: .9rem; font-weight: 600; }
    #pageresults.openkis-view-list .card-body > .card-title + b { margin-left: 0; }
    #pageresults.openkis-view-list .card-footer { padding: .2rem .5rem; background: none; border: 0; }
    #pageresults.openkis-view-list .card-footer .btn { padding: .15rem .5rem; font-size: .875rem; }
    #pageresults.openkis-view-list .card-footer img { height: 14px; }
</style>
<script>
    (function () {
        var key = $key, fallback = $default, buttons = $buttons;
        // the mode of the results: the saved choice, else the default of the section
        var apply = function (mode) {
            var el = document.getElementById('pageresults');
            if (!el) {
                return;
            }
            if (mode) {
                try {
                    localStorage.setItem(key, mode);
                } catch (e) {
                }
            } else {
                try {
                    mode = localStorage.getItem(key);
                } catch (e) {
                }
                if (mode !== 'list' && mode !== 'grid') {
                    mode = fallback;
                }
            }
            el.classList.toggle('openkis-view-list', mode === 'list');
            // the buttons, added again after every refresh of the results
            var bar = el.querySelector('.pageresults');
            if (bar && !bar.querySelector('.openkis-viewswitch')) {
                var group = document.createElement('div');
                group.className = 'openkis-viewswitch btn-group btn-group-sm';
                group.setAttribute('role', 'group');
                group.innerHTML = buttons;
                group.addEventListener('click', function (event) {
                    var button = event.target.closest('button[data-view]');
                    if (button) {
                        apply(button.getAttribute('data-view'));
                    }
                });
                bar.insertBefore(group, bar.firstChild);
            }
            el.querySelectorAll('.openkis-viewswitch button').forEach(function (b) {
                b.setAttribute('aria-pressed', b.getAttribute('data-view') === mode ? 'true' : 'false');
            });
        };
        var start = function () {
            var el = document.getElementById('pageresults');
            if (!el) {
                return;
            }
            apply();
            // call_ajax does not run the scripts of the new content
            if (window.MutationObserver && !el.openkisViewObserver) {
                el.openkisViewObserver = new MutationObserver(function () {
                    apply();
                });
                el.openkisViewObserver.observe(el, {childList: true});
            }
        };
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', start);
        } else {
            start();
        }
    })();
</script>
HTML;
}

/**
 * Settings of the shared list template of the dbview sections
 * (extra/openkis/templates/list.tp.html, the layout of the caves list) for a
 * section, as $_FN values that the template reads:
 *  - openkis_list_map: "1" for the sections whose records are drawn on the map
 *    of the results (artificial and glacial cavities), "" otherwise;
 *  - openkis_list_class: classes of #pageresults, "openkis-view-list" for the
 *    sections shown as a list by default (tracings, temperatures) and
 *    "openkis-list-nophotos" for the sections without a photo field;
 *  - openkis_list_placeholder: text of the search box.
 * Called for the section of the request by openkis_list_settings.php.
 *
 * @param string $mod section id
 * @return void
 */
function openkis_ListSettings($mod)
{
    global $_FN;
    $_FN['openkis_list_map'] = "";
    $_FN['openkis_list_class'] = "";
    $_FN['openkis_list_placeholder'] = FN_Translate("search records");
    if ($mod == "" || !file_exists("sections/$mod") || !function_exists("openkis_IsRecordSection") || !openkis_IsRecordSection(FN_GetSectionValues($mod)))
        return;
    $map = in_array($mod, array("artificials", "glacial"));
    $classes = array();
    if (in_array($mod, array("tracings", "temperatures")))
        $classes[] = "openkis-view-list";
    $config = FN_LoadConfig("modules/dbview/config.php", $mod);
    if (empty($config['image_titlefield']))
        $classes[] = "openkis-list-nophotos";
    $_FN['openkis_list_map'] = $map ? "1" : "";
    $_FN['openkis_list_class'] = implode(" ", $classes);
    if ($map)
        $_FN['openkis_list_placeholder'] = FN_Translate("search caves");
}

/**
 * Collapsible legend of the map icons of a section (extra/openkis/icons/<dir>),
 * printed by the grid_header.php of the sections into {html_header} of the list,
 * right below the map.
 *
 * @param string $dir folder of the icons in extra/openkis/icons
 * @return string html, empty without icons
 */
function openkis_ListLegend($dir)
{
    global $_FN;
    $icons = glob("extra/openkis/icons/" . preg_replace('/[^A-Za-z0-9_]/', '', $dir) . "/*.png");
    if (!is_array($icons) || count($icons) == 0)
        return "";
    $html = "<details class=\"openkis-legend small mb-2\">";
    $html .= "<summary class=\"text-muted\">" . FN_Translate("legend") . "</summary>";
    $html .= "<div class=\"row no-gutters mt-1\">";
    foreach ($icons as $icon)
    {
        $name = FN_Translate(str_replace("_", " ", str_replace(".png", "", basename($icon))));
        $html .= "<div class=\"col-4 col-sm-3 col-md-2 mb-1\"><img style=\"height:24px;border:1px solid #ccc;border-radius:6px;margin-right:4px\" src=\"{$_FN['siteurl']}$icon\" alt=\"\" /> " . htmlspecialchars($name) . "</div>";
    }
    return $html . "</div></details>";
}
