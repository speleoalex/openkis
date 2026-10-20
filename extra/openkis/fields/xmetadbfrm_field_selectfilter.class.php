<?php

/**
 * selectfilter: a <select> (foreign key or static options) with a text box
 * above it that filters the options client side as the user types.
 * Useful for long lists (e.g. geological formations).
 *
 * In the table definition:
 *   <frm_type>selectfilter</frm_type>
 *   <foreignkey>ctl_geologicalformations</foreignkey>
 *   <fk_link_field>code</fk_link_field>
 *   <fk_show_field>code,name</fk_show_field>
 */
class xmetadbfrm_field_selectfilter
{

    function show($params)
    {
        static $counter = 0;
        $counter++;
        $name = $params['name'];
        $value = isset($params['value']) ? (string) $params['value'] : "";
        $attributes = isset($params["htmlattributes"]) ? $params["htmlattributes"] : "";
        $uid = "sf{$counter}_" . preg_replace("/[^a-zA-Z0-9_]/", "_", $name);

        // options: value => title, sorted by title like the standard select
        $options = array();
        if (isset($params['options']) && is_array($params['options'])) {
            foreach ($params['options'] as $opt) {
                if (!isset($opt['value']))
                    continue;
                $options[(string) $opt['value']] = isset($opt['title']) ? $opt['title'] : $opt['value'];
            }
        } elseif (!empty($params['frm_options'])) {
            foreach (explode(",", $params['frm_options']) as $opt)
                $options[$opt] = $opt;
        }
        natcasesort($options);

        $jsoptions = array();
        $htmloptions = "";
        foreach ($options as $k => $title) {
            if ($k === "")
                continue;
            $sel = ($k === $value) ? ' selected="selected"' : '';
            $htmloptions .= "\n\t<option value=\"" . htmlspecialchars($k) . "\"$sel>" . htmlspecialchars($title) . "</option>";
            $jsoptions[] = array($k, (string) $title);
        }
        $jsoptions = json_encode($jsoptions);
        $placeholder = htmlspecialchars(FN_Translate("search"));

        $html = "<input type=\"text\" class=\"form-control\" id=\"{$uid}_filter\" autocomplete=\"off\" placeholder=\"$placeholder...\" style=\"margin-bottom:4px\" "
                . "oninput=\"selectfilter_apply('{$uid}')\" onkeydown=\"if(event.key==='Enter'){event.preventDefault();return false;}\" />";
        $html .= "<select $attributes id=\"{$uid}\" name=\"" . htmlspecialchars($name) . "\" onchange=\"selectfilter_collapse('{$uid}')\">";
        $html .= "\n\t<option value=\"\">----</option>$htmloptions\n</select>";
        $html .= "\n<script type=\"text/javascript\">
var selectfilter_options = selectfilter_options || {};
    var selectfilter_auto = selectfilter_auto || {};
selectfilter_options['{$uid}'] = $jsoptions;
if (typeof selectfilter_apply !== 'function') {
    function selectfilter_apply(uid) {
        var sel = document.getElementById(uid);
        var txt = document.getElementById(uid + '_filter').value.toLowerCase();
        var words = txt.split(/\\s+/).filter(function (w) { return w !== ''; });
        var current = sel.value;
        var all = selectfilter_options[uid];
        // a value chosen automatically by a previous filter is dropped when it no longer matches
        if (current !== '' && selectfilter_auto[uid] === current) {
            var stillok = false;
            for (var k = 0; k < all.length; k++) {
                if (all[k][0] !== current) continue;
                var h2 = (all[k][0] + ' ' + all[k][1]).toLowerCase();
                stillok = true;
                for (var m = 0; m < words.length; m++) {
                    if (h2.indexOf(words[m]) < 0) { stillok = false; break; }
                }
            }
            if (!stillok) { current = ''; selectfilter_auto[uid] = ''; }
        }
        var html = '<option value=\"\">----</option>';
        var found = false;
        for (var i = 0; i < all.length; i++) {
            var v = all[i][0], t = all[i][1];
            var hay = (v + ' ' + t).toLowerCase();
            var ok = true;
            for (var j = 0; j < words.length; j++) {
                if (hay.indexOf(words[j]) < 0) { ok = false; break; }
            }
            // never hide the value currently selected
            if (!ok && v !== current) continue;
            found = true;
            var e = document.createElement('option');
            e.value = v; e.textContent = t;
            if (v === current) e.selected = true;
            html += e.outerHTML;
        }
        sel.innerHTML = html;
        sel.value = current;
        // one single match (besides the current value): select it
        if (words.length > 0 && sel.options.length === 2 && current === '') {
            sel.selectedIndex = 1;
            selectfilter_auto[uid] = sel.value;
        }
        // while a filter is typed show the matches as an open list,
        // otherwise go back to the normal dropdown
        if (words.length > 0 && sel.options.length > 2) {
            sel.size = Math.min(sel.options.length, 10);
        } else {
            sel.removeAttribute('size');
        }
    }
    // a value picked from the open list closes it again
    function selectfilter_collapse(uid) {
        var sel = document.getElementById(uid);
        if (sel.value !== '') {
            sel.removeAttribute('size');
            selectfilter_auto[uid] = '';
        }
    }
}
</script>";
        return $html;
    }

}
