<?php
declare(strict_types=1);
/**
 * Searchable employee <select> (Tom Select, Bootstrap 5 theme).
 * Include once per page that contains select.js-employee-select-search.
 */
if (!empty($GLOBALS['__staff_employee_select_assets_loaded'])) {
    return;
}
$GLOBALS['__staff_employee_select_assets_loaded'] = true;
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js" crossorigin="anonymous"></script>
<script>
(function () {
    function initEmployeeSelects() {
        if (typeof TomSelect === 'undefined') {
            return;
        }
        document.querySelectorAll('select.js-employee-select-search:not([data-ts-applied])').forEach(function (el) {
            el.setAttribute('data-ts-applied', '1');
            new TomSelect(el, {
                allowEmptyOption: true,
                create: false,
                placeholder: 'Search or select employee…',
                maxOptions: null,
                sortField: { field: 'text', direction: 'asc' },
                plugins: ['dropdown_input']
            });
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEmployeeSelects);
    } else {
        initEmployeeSelects();
    }
})();
</script>
