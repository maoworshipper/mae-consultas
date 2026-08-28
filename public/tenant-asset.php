<?php
/**
 * Sirve imágenes del pack de branding del tenant resuelto por HTTP_HOST.
 * Whitelist: logo / fondos PDF (no path traversal, no cross-tenant).
 */

$appRoot = dirname(__DIR__);
require_once $appRoot . '/app/Core/config.php';
require_once $appRoot . '/app/Core/mae_report_images.php';

$name = basename((string) ($_GET['n'] ?? ''));
if (!preg_match('/^(logo|bgcerti|bgcarnet)\.(png|jpe?g)$/i', $name)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('Not found');
}

$base = pathinfo($name, PATHINFO_FILENAME);
$resolved = mae_report_image_resolved_path($base);
if ($resolved === null || !is_file($resolved)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('Not found');
}

$ext = strtolower(pathinfo($resolved, PATHINFO_EXTENSION));
$types = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
];
header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
header('Cache-Control: public, max-age=86400');
header('X-Content-Type-Options: nosniff');
readfile($resolved);
exit;
