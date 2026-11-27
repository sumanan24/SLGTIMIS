<?php
$path = dirname(__DIR__) . '/.env';
$text = file_get_contents($path);
if (!is_string($text)) {
    fwrite(STDERR, "env missing\n");
    exit(1);
}
if (!str_contains($text, 'LMS_AUTH_TOKEN=')) {
    $text = rtrim($text) . PHP_EOL . 'LMS_AUTH_TOKEN=' . bin2hex(random_bytes(32)) . PHP_EOL;
    file_put_contents($path, $text);
}
echo "token_ready\n";
