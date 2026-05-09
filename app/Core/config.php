<?php
/**
 * Archivo de Configuración para Consulta de Certificados
 * Versión simplificada sin dependencias
 */

require_once __DIR__ . '/env.php';
mae_load_env();

// Iniciar output buffering
if (ob_get_level() === 0) {
    ob_start();
}

// Definir la ruta base del proyecto
define('BASE_PATH', dirname(dirname(__DIR__)));

// Marca carga legítima vía entrypoint (include bajo /app/ sin esto → 403 en helpers)
if (!defined('MAE_CONSULTAS_APP_LOADED')) {
    define('MAE_CONSULTAS_APP_LOADED', true);
}

// Definir la URL base (evita usar HTTP_HOST no validado en producción)
$configuredAppUrl = rtrim((string) mae_env('APP_URL', ''), '/');
if ($configuredAppUrl !== '') {
    define('BASE_URL', $configuredAppUrl);
} else {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['SERVER_NAME'] ?? 'localhost';
    $documentRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
    $currentDir = str_replace('\\', '/', BASE_PATH);
    $basePath = $documentRoot ? str_replace($documentRoot, '', $currentDir) : '';
    $basePath = rtrim($basePath, '/');
    define('BASE_URL', $protocol . '://' . $host . $basePath);
}

// Rutas
define('FPDF_PATH', BASE_PATH . '/fpdf');
define('ASSETS_PATH', BASE_PATH . '/assets');

// La configuración de base de datos se obtiene desde .env en app/Core/database.php

// Función helper para incluir archivos
function require_path($relativePath) {
    $fullPath = BASE_PATH . '/' . ltrim($relativePath, '/');
    if (file_exists($fullPath)) {
        require_once $fullPath;
    } else {
        trigger_error("Archivo no encontrado: {$fullPath}", E_USER_WARNING);
    }
}

// Función helper para URLs
function url($path = '') {
    return BASE_URL . '/' . ltrim($path, '/');
}

// Códigos QR en certificado/carnet (opcional; control solo por .env, como mae-v8 config.php)
// Desactivado por defecto si no defines QR_ENABLED o si es false/0/off.
if (!defined('QR_ENABLED')) {
    define('QR_ENABLED', mae_env_bool('QR_ENABLED', false));
}
if (!defined('QR_REPLACE_PHOTO')) {
    define('QR_REPLACE_PHOTO', mae_env_bool('QR_REPLACE_PHOTO', false));
}
if (!defined('QR_VERIFICATION_URL')) {
    define(
        'QR_VERIFICATION_URL',
        mae_env(
            'QR_VERIFICATION_URL',
            rtrim(BASE_URL, '/') . '/index.php?certi={id}'
        )
    );
}
