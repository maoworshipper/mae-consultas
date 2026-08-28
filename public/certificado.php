<?php
$appRoot = dirname(__DIR__);
require_once $appRoot . '/app/Core/config.php';
require_once $appRoot . '/app/Core/database.php';
require_once $appRoot . '/app/Core/functions.php';
require_once $appRoot . '/app/Core/security.php';

date_default_timezone_set('America/Bogota');

if (!isCertificateFeatureEnabled()) {
    mae_reject_request(403, 'La generación de certificados está deshabilitada.');
}

$idElemento = intval($_GET['certi'] ?? 0);
if (!$idElemento) {
    mae_reject_request(400, 'ID de certificado requerido');
}

if (!mae_enforce_rate_limit('pdf_certificado', 30, 60)) {
    mae_reject_request(429, 'Demasiadas solicitudes. Intente nuevamente en un minuto.');
}

$source = normalizeDataSource($_GET['src'] ?? 'main');
$token = (string)($_GET['token'] ?? '');
if (!mae_verify_certificate_token($idElemento, $token, $source)) {
    mae_reject_request(403, 'Token de acceso inválido');
}

$selectedPdo = $source === 'legacy' ? ($pdoLegacy ?? null) : $pdo;
if (!$selectedPdo instanceof PDO) {
    mae_reject_request(400, 'Fuente de datos no disponible.');
}

$pdo = $selectedPdo;
define('MAE_CONSULTAS_INTERNAL', true);
require_once $appRoot . '/app/reports/certificado_pdf.php';
