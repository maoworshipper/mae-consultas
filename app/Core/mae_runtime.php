<?php

/**
 * Requisitos de runtime: PHP 8.3.x (hosting Que Nube, mismo cPanel que MAE).
 */

if (!function_exists('mae_runtime_fail')) {
    function mae_runtime_fail(string $title, string $message): void
    {
        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, $title . PHP_EOL . $message . PHP_EOL);
            exit(1);
        }

        http_response_code(503);
        header('Content-Type: text/html; charset=utf-8');
        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        $phpVersion = htmlspecialchars(PHP_VERSION, ENT_QUOTES, 'UTF-8');

        echo <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{$safeTitle}</title>
  <style>
    body { font-family: Georgia, 'Times New Roman', serif; margin: 0; min-height: 100vh;
      background: linear-gradient(160deg, #1a2332 0%, #2c3e50 55%, #1f2a38 100%);
      color: #f4f1ea; display: flex; align-items: center; justify-content: center; padding: 2rem; }
    main { max-width: 36rem; }
    h1 { font-size: 1.75rem; margin: 0 0 1rem; font-weight: 600; }
    p { line-height: 1.55; margin: 0 0 1rem; color: #d8d2c8; }
    code { background: rgba(255,255,255,.08); padding: .15rem .4rem; border-radius: 3px; }
    ul { color: #d8d2c8; line-height: 1.6; }
  </style>
</head>
<body>
  <main>
    <h1>{$safeTitle}</h1>
    <p>{$safeMessage}</p>
    <p>PHP detectado: <code>{$phpVersion}</code></p>
    <p>Requisitos de MAE Consultas:</p>
    <ul>
      <li>PHP <strong>8.3.x</strong></li>
      <li>Extensiones MySQL (<code>pdo_mysql</code>)</li>
    </ul>
    <p>Contacte a Que Nube si necesita ayuda.</p>
  </main>
</body>
</html>
HTML;
        exit(1);
    }
}

if (!function_exists('mae_assert_php_version')) {
    function mae_assert_php_version(): void
    {
        if (function_exists('mae_env_bool') && mae_env_bool('MAE_SKIP_PHP_CHECK', false)) {
            return;
        }

        if (PHP_MAJOR_VERSION !== 8 || PHP_MINOR_VERSION !== 3) {
            mae_runtime_fail(
                'Versión de PHP no soportada',
                'MAE Consultas requiere PHP 8.3.x. Configure el dominio en cPanel (Select PHP Version) a 8.3 y actualice el handler del .htaccess.'
            );
        }
    }
}

if (!function_exists('mae_check_runtime_requirements')) {
    function mae_check_runtime_requirements(): void
    {
        mae_assert_php_version();
    }
}
