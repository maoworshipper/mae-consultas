<?php

/**
 * Rutas de imágenes para reportes PDF (misma lógica que mae-v8, carpeta assets/images).
 */

if (!defined('MAE_CONSULTAS_APP_LOADED') || MAE_CONSULTAS_APP_LOADED !== true) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('Forbidden');
}

/**
 * Directorio de fondos para certificado/carnet.
 */
function mae_report_images_dir(): string
{
    return BASE_PATH . '/assets/images';
}

/**
 * Ruta absoluta en disco para FPDF: prueba extensiones en orden.
 *
 * @param  string[]  $extensions
 */
function mae_report_image_path(string $baseName, array $extensions = ['jpg', 'jpeg', 'png']): string
{
    $dir = mae_report_images_dir();
    foreach ($extensions as $ext) {
        $p = $dir . '/' . $baseName . '.' . $ext;
        if (is_file($p)) {
            return $p;
        }
    }

    return $dir . '/' . $baseName . '.' . $extensions[0];
}
