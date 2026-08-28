<?php
/**
 * Cargador liviano de .env para entornos cPanel sin variables de entorno.
 */
if (!function_exists('mae_load_env')) {
    function mae_load_env($envPath = null) {
        static $loaded = false;
        if ($loaded) {
            return;
        }

        $candidates = [];
        if ($envPath) {
            $candidates[] = $envPath;
        }

        $projectRoot = dirname(__DIR__, 2);
        // Buscar primero en el directorio padre (recomendado por seguridad en cPanel)
        $candidates[] = dirname($projectRoot) . '/.env';
        // Luego buscar en la raíz del proyecto local
        $candidates[] = $projectRoot . '/.env';

        $selectedPath = null;
        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '' && is_readable($candidate)) {
                $selectedPath = $candidate;
                break;
            }
        }

        if ($selectedPath === null) {
            $loaded = true;
            return;
        }

        $lines = file($selectedPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            $loaded = true;
            return;
        }

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || $trimmed[0] === '#') {
                continue;
            }

            $parts = explode('=', $trimmed, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $key = trim($parts[0]);
            $value = trim($parts[1]);
            if ($key === '') {
                continue;
            }

            $first = substr($value, 0, 1);
            $last = substr($value, -1);
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }

            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv($key . '=' . $value);
        }

        $loaded = true;
    }
}

if (!function_exists('mae_env')) {
    function mae_env($key, $default = null) {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false || $value === null || $value === '') {
            return $default;
        }
        return $value;
    }
}

if (!function_exists('mae_env_bool')) {
    function mae_env_bool($key, $default = false) {
        $value = mae_env($key, null);
        if ($value === null) {
            return (bool) $default;
        }

        $normalized = strtolower(trim((string) $value));
        if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
            return true;
        }
        if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
            return false;
        }
        return (bool) $default;
    }
}
