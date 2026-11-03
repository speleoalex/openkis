/* openkis_grottocenter theme: sidebar behaviour */
(function () {
    var body = document.body;
    var toggle = document.getElementById("gcMenuToggle");
    var backdrop = document.getElementById("gcBackdrop");
    var mq = window.matchMedia("(max-width: 991.98px)");
    var KEY = "gc_sidebar_collapsed";

    function isMobile() {
        return mq.matches;
    }

    function setExpanded(expanded) {
        if (toggle) {
            toggle.setAttribute("aria-expanded", expanded ? "true" : "false");
        }
    }

    function applyDesktopState() {
        var collapsed = false;
        try {
            collapsed = window.localStorage.getItem(KEY) === "1";
        } catch (e) {
        }
        body.classList.toggle("gc-collapsed", collapsed);
        body.classList.remove("gc-sidebar-open");
        setExpanded(!collapsed);
    }

    function applyMobileState() {
        body.classList.remove("gc-collapsed");
        body.classList.remove("gc-sidebar-open");
        setExpanded(false);
    }

    function closeMobile() {
        body.classList.remove("gc-sidebar-open");
        setExpanded(false);
    }

    if (toggle) {
        toggle.addEventListener("click", function () {
            if (isMobile()) {
                var open = body.classList.toggle("gc-sidebar-open");
                setExpanded(open);
            } else {
                var collapsed = body.classList.toggle("gc-collapsed");
                try {
                    window.localStorage.setItem(KEY, collapsed ? "1" : "0");
                } catch (e) {
                }
                setExpanded(!collapsed);
                // let leaflet maps and similar widgets know the width changed
                window.setTimeout(function () {
                    window.dispatchEvent(new Event("resize"));
                }, 250);
            }
        });
    }
    if (backdrop) {
        backdrop.addEventListener("click", closeMobile);
    }
    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
            closeMobile();
        }
    });

    if (isMobile()) {
        applyMobileState();
    } else {
        applyDesktopState();
    }
    var onChange = function () {
        if (isMobile()) {
            applyMobileState();
        } else {
            applyDesktopState();
        }
    };
    if (mq.addEventListener) {
        mq.addEventListener("change", onChange);
    } else if (mq.addListener) {
        mq.addListener(onChange);
    }
    body.classList.add("gc-ready");
})();

/* keep the original behaviour of nested dropdown items */
if (window.jQuery) {
    jQuery(".dropdown-item a").click(function () {
        if (jQuery(this).next().html()) {
            jQuery(this).next().toggle();
            return false;
        }
        return true;
    });
}
