<?php
/**
 * Configuración de MAE Consultas.
 * Tenant por HTTP_HOST (config/tenants.php). Features: branding/{id}/features.json.
 * Local: LICENSE_SKIP=true + CLIENT_ID y flags en .env.
 */

if (ob_get_level() === 0) {
    ob_start();
}

define('BASE_PATH', dirname(__DIR__, 2));
define('PUBLIC_PATH', BASE_PATH . '/public');

if (!defined('MAE_CONSULTAS_APP_LOADED')) {
    define('MAE_CONSULTAS_APP_LOADED', true);
}

require_once __DIR__ . '/env.php';
mae_load_env();
require_once __DIR__ . '/mae_runtime.php';
require_once __DIR__ . '/mae_tenant.php';
require_once __DIR__ . '/mae_features.php';
require_once __DIR__ . '/security.php';

mae_check_runtime_requirements();
mae_assert_tenant();

$maeClientId = mae_tenant_resolve_client_id();
define('CLIENT_ID', $maeClientId);
define('BRANDING_PATH', BASE_PATH . '/branding/' . CLIENT_ID);
define('BRANDING_IMAGES_PATH', BRANDING_PATH . '/images');

$forceHttps = mae_env_bool('MAE_FORCE_HTTPS', false);
$hosted = !mae_tenant_is_dev_skip();
if (($forceHttps || $hosted) && PHP_SAPI !== 'cli') {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
    if (!$isHttps && mae_env_bool('MAE_TRUST_PROXY', false)
        && isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
        && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
        $isHttps = true;
    }
    if (!$isHttps && !empty($_SERVER['HTTP_HOST']) && function_exists('mae_force_https')) {
        mae_force_https();
    }
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

$documentRoot = rtrim(str_replace('\\', '/', (string) ($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
$publicDir = str_replace('\\', '/', PUBLIC_PATH);
$urlPath = '';
if ($documentRoot !== '') {
    if ($documentRoot === $publicDir) {
        $urlPath = '';
    } elseif (str_starts_with($publicDir, $documentRoot . '/')) {
        $urlPath = substr($publicDir, strlen($documentRoot));
    }
}
$urlPath = rtrim(str_replace('\\', '/', $urlPath), '/');

define('BASE_URL', $protocol . '://' . $host . $urlPath);
define('FPDF_PATH', BASE_PATH . '/fpdf');
define('ASSETS_PATH', PUBLIC_PATH . '/assets');
define('ASSETS_URL', BASE_URL . '/assets');
define('FOTOS_PATH', mae_tenant_upload_dir('fotos'));
define('CONVENIOS_PATH', mae_tenant_upload_dir('convenios'));
define('PRODUCT_IMAGES_PATH', PUBLIC_PATH . '/assets/images');

define('QR_ENABLED', mae_tenant_feature_bool('QR_ENABLED', false));
define('QR_REPLACE_PHOTO', mae_tenant_feature_bool('QR_REPLACE_PHOTO', false));
define('QR_VERIFICATION_URL', mae_tenant_feature_string(
    'QR_VERIFICATION_URL',
    rtrim(BASE_URL, '/') . '/index.php?certi={id}'
));
define('FEATURE_CERTIFICATE_ENABLED', mae_tenant_feature_bool('FEATURE_CERTIFICATE_ENABLED', true));
define('FEATURE_CARD_ENABLED', mae_tenant_feature_bool('FEATURE_CARD_ENABLED', true));

function require_path($relativePath) {
    $fullPath = BASE_PATH . '/' . ltrim($relativePath, '/');
    if (file_exists($fullPath)) {
        require_once $fullPath;
    } else {
        trigger_error("Archivo no encontrado: {$fullPath}", E_USER_WARNING);
    }
}

function url($path = '') {
    return BASE_URL . '/' . ltrim($path, '/');
}
