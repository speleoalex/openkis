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

/* light/dark mode: the script at the top of template.tp.html sets data-theme on
   <html> before the styles; the button of the app bar switches it. A choice equal
   to the setting of the system is not kept, so the page follows the system again */
(function () {
    var root = document.documentElement;
    var btn = document.getElementById("gcThemeToggle");
    var KEY = "gc_theme";
    var mq = window.matchMedia ? window.matchMedia("(prefers-color-scheme: dark)") : null;

    function systemTheme() {
        return mq && mq.matches ? "dark" : "light";
    }

    function saved() {
        try {
            var t = window.localStorage.getItem(KEY);
            return (t === "light" || t === "dark") ? t : null;
        } catch (e) {
            return null;
        }
    }

    function apply(t) {
        root.setAttribute("data-theme", t);
        if (btn) {
            btn.setAttribute("aria-pressed", t === "dark" ? "true" : "false");
        }
    }

    apply(root.getAttribute("data-theme") === "dark" ? "dark" : "light");
    if (btn) {
        btn.addEventListener("click", function () {
            var next = root.getAttribute("data-theme") === "dark" ? "light" : "dark";
            try {
                if (next === systemTheme()) {
                    window.localStorage.removeItem(KEY);
                } else {
                    window.localStorage.setItem(KEY, next);
                }
            } catch (e) {
            }
            apply(next);
        });
    }
    if (mq) {
        var onSystem = function () {
            if (!saved()) {
                apply(systemTheme());
            }
        };
        if (mq.addEventListener) {
            mq.addEventListener("change", onSystem);
        } else if (mq.addListener) {
            mq.addListener(onSystem);
        }
    }
    // another tab changed the mode
    window.addEventListener("storage", function (e) {
        if (e.key === KEY) {
            apply(saved() || systemTheme());
        }
    });
})();

/* close buttons of Bootstrap 5 (data-bs-dismiss="alert") in pages written for a
   Bootstrap 5 theme: this theme loads Bootstrap 4. A native "close.bs.alert" event
   is sent first, as Bootstrap 5 does (the Liguria home keeps its notice closed with it) */
document.addEventListener("click", function (e) {
    var b = e.target && e.target.closest ? e.target.closest('[data-bs-dismiss="alert"]') : null;
    var a = b ? b.closest(".alert") : null;
    if (!a) {
        return;
    }
    var ev = null;
    try {
        ev = new CustomEvent("close.bs.alert", {bubbles: true, cancelable: true});
    } catch (x) {
    }
    if (ev && !a.dispatchEvent(ev)) {
        return;
    }
    a.parentNode.removeChild(a);
});

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
