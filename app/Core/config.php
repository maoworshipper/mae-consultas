<?php
/**
 * Archivo de Configuración para Consulta de Certificados
 * Versión simplificada sin dependencias
 */

// Iniciar output buffering
if (ob_get_level() === 0) {
    ob_start();
}

// Definir la ruta base del proyecto
define('BASE_PATH', dirname(dirname(__DIR__)));

// Definir la URL base
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$documentRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
$currentDir = str_replace('\\', '/', BASE_PATH);
$basePath = $documentRoot ? str_replace($documentRoot, '', $currentDir) : '';
$basePath = rtrim($basePath, '/');

define('BASE_URL', $protocol . '://' . $host . $basePath);

// Rutas
define('FPDF_PATH', BASE_PATH . '/fpdf');
define('ASSETS_PATH', BASE_PATH . '/assets');

// Configuración de base de datos (misma que mae-v8)
define('DB_HOST', 'localhost');
define('DB_NAME', 'geducativometa_mwb8');
define('DB_USER', 'geducativometa_con_musr');
define('DB_PASS', 'uAheER,c57IpODY7');

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
