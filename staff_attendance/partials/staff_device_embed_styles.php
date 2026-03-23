<?php
declare(strict_types=1);
/**
 * Layout + Tom Select tweaks for staff device pages (embedded app + standalone).
 */
if (!empty($GLOBALS['__staff_device_embed_styles_loaded'])) {
    return;
}
$GLOBALS['__staff_device_embed_styles_loaded'] = true;
?>
<style>
.staff-device-page { --staff-device-control-h: calc(1.5em + 0.75rem + 2px); }
.staff-device-page .staff-device-filter-form .form-label { margin-bottom: 0.25rem; }
.staff-device-page .staff-device-filter-form .form-control,
.staff-device-page .staff-device-filter-form .form-select {
    min-height: var(--staff-device-control-h);
}
.staff-device-page .staff-device-filter-form .btn:not(.btn-sm) {
    min-height: var(--staff-device-control-h);
    padding-top: 0.375rem;
    padding-bottom: 0.375rem;
}
.staff-device-page .staff-device-filter-form .btn-sm {
    min-height: 31px;
}
.staff-device-page .staff-device-filter-form .form-select-sm {
    min-height: 31px;
}
.staff-device-page .staff-device-ts-wrap {
    min-width: 0;
}
.staff-device-page .staff-device-ts-wrap .ts-wrapper {
    width: 100% !important;
    max-width: 100%;
    min-width: 0;
}
.staff-device-page .staff-device-card-header-actions {
    width: 100%;
}
@media (min-width: 768px) {
    .staff-device-page .staff-device-card-header-actions {
        width: auto;
        max-width: 100%;
    }
}
.staff-device-page .staff-device-card-header-actions .btn {
    white-space: nowrap;
}
@media (max-width: 575.98px) {
    .staff-device-page .staff-device-card-header-actions {
        flex-direction: column;
        align-items: stretch;
    }
    .staff-device-page .staff-device-card-header-actions .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>
