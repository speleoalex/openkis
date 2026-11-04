/**
 * Modal window with the essential data of a record (openkis_popup.php),
 * opened from the markers of the map instead of the popover of the marker and
 * of the record page in a new window. Loaded by bs_map.tp.html; it does
 * nothing until a region enables it for a section in its
 * bs_map/conf/layers_<region>.js:
 *
 *     OPENKIS_MapPopup.enable("caves");
 *
 * The record is found from the url of the marker (the "open" link of its KML
 * description): .../<mod>/view/<id>/, <mod>-view-<id>.html or
 * index.php?mod=<mod>&op=view&id=<id>. Markers of other sections keep the
 * standard popover.
 */
var OPENKIS_MapPopup = {
    /** enabled sections: mod => true */
    mods: {},
    installed: false,
    modalId: "openkisPopupModal",
    xhr: null,
    /**
     * Enables the modal for the markers of a section
     * @param {String} mod section name
     * @returns {undefined}
     */
    enable: function (mod)
    {
        this.mods[mod] = true;
        if (this.installed || typeof OPS_Map === "undefined")
        {
            return;
        }
        this.installed = true;
        var self = this;
        // single marker under the pointer, "details" icon of the list of
        // markers, marker chosen in the search
        var showHitPopup = OPS_Map.showHitPopup;
        OPS_Map.showHitPopup = function (hit)
        {
            var rec = self.recordFromUrl(hit.url);
            if (rec)
            {
                self.open(rec.mod, rec.id, hit.name ? OPS_Map.decodeEntities(hit.name) : "");
                return;
            }
            showHitPopup.call(OPS_Map, hit);
        };
        // name of a marker in the list of the markers under the pointer
        var openRecord = OPS_Map.openRecord;
        OPS_Map.openRecord = function (url)
        {
            var rec = self.recordFromUrl(url);
            if (rec)
            {
                self.open(rec.mod, rec.id, "");
                return true;
            }
            return openRecord.call(OPS_Map, url);
        };
    },
    /**
     * Section and id of the record opened by a url, when the section is enabled
     * @param {String} url
     * @returns {Object|null} {mod, id}
     */
    recordFromUrl: function (url)
    {
        if (!url)
        {
            return null;
        }
        var mod = "", id = "", m;
        if ((m = url.match(/\/([a-z0-9_]+)\/view\/(\d+)\/?(?:[?#]|$)/i)))
        {
            mod = m[1];
            id = m[2];
        } else if ((m = url.match(/\/([a-z0-9_]+)-view-(\d+)\.html(?:[?#]|$)/i)))
        {
            mod = m[1];
            id = m[2];
        } else if (/[?&]op=view(?:&|$)/.test(url))
        {
            m = url.match(/[?&]mod=([a-z0-9_]+)/i);
            var mi = url.match(/[?&]id=(\d+)/);
            if (m && mi)
            {
                mod = m[1];
                id = mi[1];
            }
        }
        if (mod === "" || !this.mods[mod])
        {
            return null;
        }
        return {mod: mod, id: id};
    },
    /**
     * Opens the modal and loads the record
     * @param {String} mod section
     * @param {String} id record
     * @param {String} title shown while loading (the fragment carries its own)
     * @returns {undefined}
     */
    open: function (mod, id, title)
    {
        var self = this;
        var $modal = this.modal();
        var $body = $modal.find(".modal-body");
        $modal.find(".modal-title").text(title || "");
        $body.html("<div class=\"text-center text-muted openkis-popup-loading\"><span class=\"glyphicon glyphicon-refresh\"></span> Caricamento...</div>");
        $modal.modal("show");
        if (this.xhr)
        {
            try {
                this.xhr.abort();
            } catch (e) {
            }
        }
        this.xhr = $.ajax({
            url: "openkis_popup.php?mod=" + encodeURIComponent(mod) + "&id=" + encodeURIComponent(id),
            method: "GET",
            dataType: "html",
            success: function (html)
            {
                self.xhr = null;
                $body.html(html);
                var t = $body.find(".openkis-popup").attr("data-title");
                if (t)
                {
                    $modal.find(".modal-title").text(t);
                }
            },
            error: function (xhr, status)
            {
                if (status === "abort")
                {
                    return;
                }
                self.xhr = null;
                var html = xhr && xhr.responseText && /class="alert/.test(xhr.responseText) ? xhr.responseText
                        : "<div class=\"alert alert-danger\">Errore nel caricamento della scheda.</div>";
                $body.html(html);
            }
        });
    },
    /**
     * The modal element, created on the first use (Bootstrap 3 markup)
     * @returns {jQuery}
     */
    modal: function ()
    {
        var $modal = $("#" + this.modalId);
        if ($modal.length)
        {
            return $modal;
        }
        $("body").append(
                "<div class=\"modal fade openkis-popup-modal\" id=\"" + this.modalId + "\" tabindex=\"-1\" role=\"dialog\" aria-hidden=\"true\">"
                + "<div class=\"modal-dialog modal-lg\" role=\"document\"><div class=\"modal-content\">"
                + "<div class=\"modal-header\"><button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"Chiudi\">&times;</button>"
                + "<h4 class=\"modal-title\"></h4></div>"
                + "<div class=\"modal-body\"></div>"
                + "</div></div></div>");
        return $("#" + this.modalId);
    }
};
