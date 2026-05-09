<?php
// Conexión a base de datos para consulta de certificados

require_once __DIR__ . '/env.php';

mae_load_env();

$dbHost = mae_env('DB_HOST', 'localhost');
$dbName = mae_env('DB_NAME', 'maewebdb');
$dbUser = mae_env('DB_USER', 'root');
$dbPass = mae_env('DB_PASS', '');
$dbCharset = mae_env('DB_CHARSET', 'utf8mb4');
$legacyEnabled = mae_env_bool('LEGACY_DB_ENABLED', false);

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset={$dbCharset}",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_BOTH,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$dbCharset}"
        ]
    );
} catch (PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
    http_response_code(503);
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Servicio temporalmente no disponible</title>
        <style>
            :root {
                color-scheme: light;
            }
            * {
                box-sizing: border-box;
            }
            body {
                margin: 0;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                background: linear-gradient(135deg, #f8fbff 0%, #edf3ff 100%);
                font-family: Arial, Helvetica, sans-serif;
                color: #1d2a44;
                padding: 24px;
            }
            .notice {
                width: 100%;
                max-width: 640px;
                text-align: center;
                background: #ffffff;
                border: 1px solid #dbe6ff;
                border-radius: 16px;
                padding: 28px 24px;
                box-shadow: 0 12px 28px rgba(19, 47, 122, 0.12);
            }
            .badge {
                display: inline-block;
                margin-bottom: 12px;
                font-weight: 700;
                font-size: 13px;
                letter-spacing: 0.4px;
                text-transform: uppercase;
                color: #0f56d8;
                background: #e7f0ff;
                border-radius: 999px;
                padding: 6px 12px;
            }
            h1 {
                margin: 0 0 10px;
                font-size: 26px;
                color: #12336e;
            }
            p {
                margin: 0;
                font-size: 17px;
                line-height: 1.5;
                color: #2a3b63;
            }
        </style>
    </head>
    <body>
        <main class="notice">
            <div class="badge">Servicio temporal</div>
            <h1>No es posible conectarse en este momento</h1>
            <p>Por favor, intente m&aacute;s tarde.</p>
        </main>
    </body>
    </html>
    <?php
    exit;
}

$pdoLegacy = null;
if ($legacyEnabled) {
    $legacyHost = mae_env('LEGACY_DB_HOST', '');
    $legacyName = mae_env('LEGACY_DB_NAME', '');
    $legacyUser = mae_env('LEGACY_DB_USER', '');
    $legacyPass = mae_env('LEGACY_DB_PASS', '');
    $legacyCharset = mae_env('LEGACY_DB_CHARSET', 'utf8mb4');

    if ($legacyHost !== '' && $legacyName !== '' && $legacyUser !== '') {
        try {
            $pdoLegacy = new PDO(
                "mysql:host={$legacyHost};dbname={$legacyName};charset={$legacyCharset}",
                $legacyUser,
                $legacyPass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_BOTH,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$legacyCharset}"
                ]
            );
        } catch (PDOException $e) {
            error_log("Legacy DB connection error: " . $e->getMessage());
            $pdoLegacy = null;
        }
    }
}
