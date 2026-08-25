<?php
$src = dirname(__DIR__) . '/public/images/principal-signature-source.png';
$dest = dirname(__DIR__) . '/public/images/principal-signature.png';
if (!is_file($src)) {
    fwrite(STDERR, "Source missing: $src\n");
    exit(1);
}
if (!function_exists('imagecreatefrompng')) {
    copy($src, $dest);
    echo "copied (no GD)\n";
    exit(0);
}
$raw = file_get_contents($src);
if ($raw === false || $raw === '') {
    fwrite(STDERR, "Could not read source\n");
    exit(1);
}
$im = @imagecreatefromstring($raw);
if (!$im) {
    file_put_contents($dest, $raw);
    echo "copied raw (decode failed)\n";
    exit(0);
}
imagesavealpha($im, true);
imagealphablending($im, false);
$w = imagesx($im);
$h = imagesy($im);
for ($y = 0; $y < $h; $y++) {
    for ($x = 0; $x < $w; $x++) {
        $rgba = imagecolorat($im, $x, $y);
        $r = ($rgba >> 16) & 0xFF;
        $g = ($rgba >> 8) & 0xFF;
        $b = $rgba & 0xFF;
        if ($r < 48 && $g < 48 && $b < 48) {
            imagesetpixel($im, $x, $y, imagecolorallocatealpha($im, 0, 0, 0, 127));
        }
    }
}
imagepng($im, $dest);
imagedestroy($im);
echo "ok\n";
