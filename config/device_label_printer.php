<?php
/**
 * SLGTI device QR sticker roll — physical dimensions in millimetres.
 *
 * Sticker content: QR code + Asset No. (small) + Serial Number (bold) only.
 */
return [
    'printer_model' => 'Zebra ZD230',
    'dpi' => 203,
    'labels_per_set' => 2,
    'default_sets' => 1,
    'max_sets' => 50,

    /** Primary dimensions (mm) — used for print/PDF at 100% actual size. */
    'label_width_mm' => 50.8,
    'label_height_mm' => 25.4,
    'horizontal_gap_mm' => 3.0,
    'inner_padding_mm' => 1.5,
    'qr_size_mm' => 17.0,
    'text_gap_mm' => 3.5,

    /** QR PNG generation (high resolution for sharp print). */
    'qr_png_px' => 320,
    'qr_margin_px' => 4,

    /** Layout: horizontal (QR left · Asset No. + Serial right). */
    'layout' => 'horizontal',

    /** Sticker text — only these two lines beside QR. */
    'show_slgti' => false,
    'show_asset_no' => true,
    'show_serial' => true,
    'show_device_type' => false,
    'show_department' => false,
    'show_status' => false,

    /** ZPL / print typography (dots at 203 dpi). */
    'qr_magnification' => 3,
    'qr_print_width_mm' => 17.0,
    'asset_no_font_h' => 26,
    'asset_no_font_w' => 22,
    'serial_font_h' => 26,
    'serial_font_w' => 22,
    'print_darkness' => 15,
    'print_speed' => 4,
    'horizontal_offset_dots' => 0,
    'vertical_offset_dots' => 0,

    /**
     * Public URL embedded in printed QR codes (always production SIS).
     * Labels scanned on phones must open the live site, not localhost/WAMP.
     */
    'qr_public_base_url' => 'https://sis.slgti.ac.lk',
];
