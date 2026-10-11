(function () {
    function showPanel(root, name) {
        root.querySelectorAll('[data-fac-tab]').forEach(function (btn) {
            btn.classList.toggle('is-on', btn.getAttribute('data-fac-tab') === name);
        });
        root.querySelectorAll('[data-fac-panel]').forEach(function (panel) {
            panel.hidden = panel.getAttribute('data-fac-panel') !== name;
        });
    }

    document.querySelectorAll('[data-fac-tabs]').forEach(function (root) {
        var first = root.querySelector('[data-fac-tab]');
        if (first) {
            showPanel(root, first.getAttribute('data-fac-tab'));
        }
        if (window.location.hash === '#progress') {
            showPanel(root, 'work');
        }
        root.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-fac-tab]');
            if (!btn || !root.contains(btn)) {
                return;
            }
            e.preventDefault();
            showPanel(root, btn.getAttribute('data-fac-tab'));
        });
    });

    var typeSel = document.getElementById('facType');
    var vendorWrap = document.getElementById('facVendorWrap');
    function toggleVendor() {
        if (!typeSel || !vendorWrap) {
            return;
        }
        vendorWrap.hidden = typeSel.value !== 'vendor';
    }
    if (typeSel) {
        typeSel.addEventListener('change', toggleVendor);
        toggleVendor();
    }

    var more = document.getElementById('facMoreFilters');
    var moreBtn = document.getElementById('facMoreBtn');
    if (more && moreBtn) {
        moreBtn.addEventListener('click', function () {
            more.hidden = !more.hidden;
            moreBtn.textContent = more.hidden ? 'More filters' : 'Hide filters';
        });
    }
})();
