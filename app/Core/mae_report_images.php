<?php

/**
 * Rutas de imágenes de reportes PDF y uploads del tenant mae-v8.
 * Orden fondos: branding/{CLIENT_ID}/images → public/assets/images.
 */

if (!defined('MAE_CONSULTAS_APP_LOADED') || MAE_CONSULTAS_APP_LOADED !== true) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('Forbidden');
}

/**
 * @return string[] directorios a explorar
 */
function mae_report_image_dirs(): array
{
    $dirs = [];
    if (defined('BRANDING_IMAGES_PATH') && is_dir(BRANDING_IMAGES_PATH)) {
        $dirs[] = BRANDING_IMAGES_PATH;
    } elseif (defined('CLIENT_ID')) {
        $brandDir = BASE_PATH . '/branding/' . CLIENT_ID . '/images';
        if (is_dir($brandDir)) {
            $dirs[] = $brandDir;
        }
    }
    $product = defined('PRODUCT_IMAGES_PATH') ? PRODUCT_IMAGES_PATH : (PUBLIC_PATH . '/assets/images');
    if (is_dir($product) && !in_array($product, $dirs, true)) {
        $dirs[] = $product;
    }

    return $dirs;
}

/**
 * Directorio de fondos para certificado/carnet (compat).
 */
function mae_report_images_dir(): string
{
    $dirs = mae_report_image_dirs();

    return $dirs[0] ?? (defined('PRODUCT_IMAGES_PATH') ? PRODUCT_IMAGES_PATH : BASE_PATH . '/public/assets/images');
}

/**
 * Ruta absoluta en disco para FPDF: prueba extensiones en orden.
 *
 * @param  string[]  $extensions
 */
function mae_report_image_path(string $baseName, array $extensions = ['jpg', 'jpeg', 'png']): string
{
    $resolved = mae_report_image_resolved_path($baseName, $extensions);
    if ($resolved !== null) {
        return $resolved;
    }

    $dir = mae_report_images_dir();

    return $dir . '/' . $baseName . '.' . $extensions[0];
}

/**
 * @param  string[]  $extensions
 */
function mae_report_image_resolved_path(string $baseName, array $extensions = ['jpg', 'jpeg', 'png']): ?string
{
    foreach (mae_report_image_dirs() as $dir) {
        foreach ($extensions as $ext) {
            $p = $dir . '/' . $baseName . '.' . $ext;
            if (is_file($p)) {
                return $p;
            }
        }
    }

    return null;
}

function mae_tenant_logo_url(): string
{
    $resolved = mae_report_image_resolved_path('logo');
    if ($resolved === null) {
        return BASE_URL . '/assets/images/logo.png';
    }

    return BASE_URL . '/tenant-asset.php?n=' . rawurlencode(basename($resolved));
}

/**
 * Foto de persona en mae-v8 public/tenants/<id>/fotos.
 */
function mae_cliente_foto_absolute_path(string $identificacion): ?string
{
    $docId = preg_replace('/[^A-Za-z0-9_-]/', '', trim($identificacion)) ?? '';
    if ($docId === '') {
        return null;
    }
    $fotosRoot = defined('FOTOS_PATH') ? FOTOS_PATH : mae_tenant_upload_dir('fotos');
    if (!is_dir($fotosRoot)) {
        return null;
    }
    $dir = realpath($fotosRoot);
    if ($dir === false) {
        return null;
    }
    $dirPrefix = rtrim(str_replace('\\', '/', $dir), '/') . '/';
    foreach (['.jpg', '.jpeg', '.png'] as $ext) {
        $candidate = $fotosRoot . '/' . $docId . $ext;
        if (!is_file($candidate)) {
            continue;
        }
        $full = realpath($candidate);
        if ($full === false) {
            continue;
        }
        $fullNorm = str_replace('\\', '/', $full);
        if (str_starts_with($fullNorm, $dirPrefix)) {
            return $fullNorm;
        }
    }

    return null;
}

/**
 * Logo de convenio en mae-v8 public/tenants/<id>/convenios.
 */
function mae_convenio_logo_absolute_path(string $foto): ?string
{
    $base = basename(str_replace('\\', '/', trim($foto)));
    if ($base === '' || !preg_match('/^[a-zA-Z0-9._-]+\.(jpe?g|png|gif)$/i', $base)) {
        return null;
    }
    $conveniosRoot = defined('CONVENIOS_PATH') ? CONVENIOS_PATH : mae_tenant_upload_dir('convenios');
    if (!is_dir($conveniosRoot)) {
        return null;
    }
    $dir = realpath($conveniosRoot);
    $full = realpath($conveniosRoot . '/' . $base);
    if ($dir === false || $full === false) {
        return null;
    }
    $dirPrefix = rtrim(str_replace('\\', '/', $dir), '/') . '/';
    $fullNorm = str_replace('\\', '/', $full);
    if (!str_starts_with($fullNorm, $dirPrefix)) {
        return null;
    }

    return $fullNorm;
}
