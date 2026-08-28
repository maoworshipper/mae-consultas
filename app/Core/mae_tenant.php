<?php

/**
 * Tenant por HTTP_HOST (cPanel Que Nube, un código, N BDs de mae-v8).
 * Dev local: LICENSE_SKIP=true + CLIENT_ID en .env.
 */

if (!function_exists('mae_license_normalize_host')) {
    function mae_license_normalize_host(string $host): string
    {
        $host = strtolower(trim($host));
        $host = preg_replace('/:\d+$/', '', $host) ?? $host;
        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        return $host;
    }
}

if (!function_exists('mae_tenant_project_root')) {
    function mae_tenant_project_root(): string
    {
        return defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);
    }
}

if (!function_exists('mae_tenant_registry_path')) {
    function mae_tenant_registry_path(): string
    {
        return mae_tenant_project_root() . '/config/tenants.php';
    }
}

if (!function_exists('mae_tenant_secrets_path')) {
    function mae_tenant_secrets_path(): string
    {
        $fromEnv = function_exists('mae_env') ? trim((string) mae_env('MAE_SECRETS_PATH', '')) : '';
        if ($fromEnv !== '' && is_readable($fromEnv)) {
            return $fromEnv;
        }

        return mae_tenant_project_root() . '/config/tenants.secrets.php';
    }
}

if (!function_exists('mae_tenant_slug_valid')) {
    function mae_tenant_slug_valid(string $id): bool
    {
        return (bool) preg_match('/^[a-z0-9][a-z0-9_-]{0,63}$/', $id);
    }
}

if (!function_exists('mae_tenant_is_dev_skip')) {
    function mae_tenant_is_dev_skip(): bool
    {
        if (!function_exists('mae_env_bool')) {
            return false;
        }
        if (mae_env_bool('LICENSE_SKIP', false)) {
            return true;
        }

        return PHP_SAPI === 'cli' && mae_env_bool('LICENSE_SKIP_CLI', true);
    }
}

if (!function_exists('mae_tenant_registry')) {
    /**
     * @return array{hosts: array<string, string>, tenants: array<string, array<string, mixed>>}
     */
    function mae_tenant_registry(): array
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        $path = mae_tenant_registry_path();
        $loaded = is_file($path) ? require $path : [];
        $cached = [
            'hosts' => is_array($loaded['hosts'] ?? null) ? $loaded['hosts'] : [],
            'tenants' => is_array($loaded['tenants'] ?? null) ? $loaded['tenants'] : [],
        ];

        return $cached;
    }
}

if (!function_exists('mae_tenant_secrets')) {
    /**
     * @return array<string, array<string, mixed>>
     */
    function mae_tenant_secrets(): array
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        $path = mae_tenant_secrets_path();
        $loaded = is_file($path) ? require $path : [];
        $cached = is_array($loaded) ? $loaded : [];

        return $cached;
    }
}

if (!function_exists('mae_tenant_features_from_pack')) {
    /**
     * @return array<string, mixed>
     */
    function mae_tenant_features_from_pack(string $clientId): array
    {
        $path = mae_tenant_project_root() . '/branding/' . $clientId . '/features.json';
        if (!is_readable($path)) {
            return [];
        }
        $data = json_decode((string) file_get_contents($path), true);

        return is_array($data) ? $data : [];
    }
}

if (!function_exists('mae_tenant_resolve_client_id')) {
    function mae_tenant_resolve_client_id(): string
    {
        static $resolved = null;
        if ($resolved !== null) {
            return $resolved;
        }

        if (mae_tenant_is_dev_skip()) {
            $fromEnv = strtolower(trim((string) (function_exists('mae_env') ? mae_env('CLIENT_ID', 'demo') : 'demo')));
            if (!mae_tenant_slug_valid($fromEnv)) {
                $fromEnv = 'demo';
            }
            $resolved = $fromEnv;

            return $resolved;
        }

        if (PHP_SAPI === 'cli') {
            $cliId = strtolower(trim((string) (getenv('MAE_CLIENT_ID') ?: getenv('CLIENT_ID') ?: '')));
            if ($cliId === '' || !mae_tenant_slug_valid($cliId)) {
                mae_runtime_fail(
                    'Tenant CLI no indicado',
                    'En CLI use MAE_CLIENT_ID. En local, LICENSE_SKIP=true.'
                );
            }
            $meta = mae_tenant_registry()['tenants'][$cliId] ?? null;
            if (!is_array($meta) || empty($meta['active'])) {
                mae_runtime_fail(
                    'Tenant inactivo',
                    'El cliente ' . $cliId . ' no está activo en config/tenants.php.'
                );
            }
            $resolved = $cliId;

            return $resolved;
        }

        $host = mae_license_normalize_host((string) ($_SERVER['HTTP_HOST'] ?? ''));
        if ($host === '') {
            mae_runtime_fail(
                'Sitio no configurado',
                'No se pudo determinar el dominio de esta instalación.'
            );
        }

        $clientId = mae_tenant_registry()['hosts'][$host] ?? '';
        $clientId = is_string($clientId) ? strtolower(trim($clientId)) : '';
        if ($clientId === '' || !mae_tenant_slug_valid($clientId)) {
            mae_runtime_fail(
                'Dominio no autorizado',
                'Este hostname no está asignado a un cliente de consulta. Contacte a Que Nube.'
            );
        }

        $meta = mae_tenant_registry()['tenants'][$clientId] ?? null;
        if (!is_array($meta) || empty($meta['active'])) {
            mae_runtime_fail(
                'Servicio no disponible',
                'Este sitio de consulta está desactivado. Contacte a Que Nube.'
            );
        }

        $resolved = $clientId;

        return $resolved;
    }
}

if (!function_exists('mae_assert_tenant')) {
    function mae_assert_tenant(): void
    {
        mae_tenant_resolve_client_id();
        if (mae_tenant_is_dev_skip()) {
            return;
        }
        if (PHP_SAPI === 'cli') {
            return;
        }
        $id = mae_tenant_resolve_client_id();
        $db = mae_tenant_secrets()[$id] ?? null;
        if (!is_array($db) || trim((string) ($db['name'] ?? '')) === '' || trim((string) ($db['user'] ?? '')) === '') {
            mae_runtime_fail(
                'Base de datos no configurada',
                'Faltan credenciales de BD para este cliente. Configure MAE_SECRETS_PATH o config/tenants.secrets.php.'
            );
        }
    }
}

if (!function_exists('mae_tenant_db_credentials')) {
    /**
     * @return array{host: string, name: string, user: string, pass: string, charset: string}
     */
    function mae_tenant_db_credentials(): array
    {
        if (mae_tenant_is_dev_skip()) {
            return [
                'host' => (string) mae_env('DB_HOST', 'localhost'),
                'name' => (string) mae_env('DB_NAME', ''),
                'user' => (string) mae_env('DB_USER', ''),
                'pass' => (string) mae_env('DB_PASS', ''),
                'charset' => (string) mae_env('DB_CHARSET', 'utf8mb4'),
            ];
        }
        $id = defined('CLIENT_ID') ? CLIENT_ID : mae_tenant_resolve_client_id();
        $row = mae_tenant_secrets()[$id] ?? [];
        if (!is_array($row)) {
            $row = [];
        }

        return [
            'host' => (string) ($row['host'] ?? 'localhost'),
            'name' => (string) ($row['name'] ?? ''),
            'user' => (string) ($row['user'] ?? ''),
            'pass' => (string) ($row['pass'] ?? ''),
            'charset' => (string) ($row['charset'] ?? 'utf8mb4'),
        ];
    }
}

if (!function_exists('mae_tenant_uploads_root')) {
    function mae_tenant_uploads_root(): string
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        $fromEnv = function_exists('mae_env') ? trim((string) mae_env('MAE_TENANTS_ROOT', '')) : '';
        if ($fromEnv !== '' && is_dir($fromEnv)) {
            $cached = rtrim(str_replace('\\', '/', $fromEnv), '/');

            return $cached;
        }

        $sibling = dirname(mae_tenant_project_root()) . '/mae/public/tenants';
        if (is_dir($sibling)) {
            $cached = str_replace('\\', '/', $sibling);

            return $cached;
        }

        $cached = str_replace('\\', '/', mae_tenant_project_root() . '/public/tenants');

        return $cached;
    }
}

if (!function_exists('mae_tenant_upload_dir')) {
    function mae_tenant_upload_dir(string $kind): string
    {
        $id = defined('CLIENT_ID') ? (string) CLIENT_ID : mae_tenant_resolve_client_id();

        return mae_tenant_uploads_root() . '/' . $id . '/' . $kind;
    }
}

if (!function_exists('mae_tenant_features')) {
    /**
     * @return array<string, mixed>
     */
    function mae_tenant_features(): array
    {
        $id = defined('CLIENT_ID') ? (string) CLIENT_ID : mae_tenant_resolve_client_id();

        return mae_tenant_features_from_pack($id);
    }
}

if (!function_exists('mae_tenant_feature')) {
    function mae_tenant_feature(string $name, mixed $default = null): mixed
    {
        $features = mae_tenant_features();
        if (array_key_exists($name, $features)) {
            return $features[$name];
        }

        if (mae_tenant_is_dev_skip() && function_exists('mae_env')) {
            $fromEnv = mae_env($name, null);
            if ($fromEnv !== null && $fromEnv !== '') {
                return $fromEnv;
            }
        }

        return $default;
    }
}

if (!function_exists('mae_tenant_feature_bool')) {
    function mae_tenant_feature_bool(string $name, bool $default = false): bool
    {
        $value = mae_tenant_feature($name, null);
        if ($value === null) {
            return $default;
        }
        if (is_bool($value)) {
            return $value;
        }
        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }
}

if (!function_exists('mae_tenant_feature_string')) {
    function mae_tenant_feature_string(string $name, string $default = ''): string
    {
        $value = mae_tenant_feature($name, $default);

        return (string) ($value ?? $default);
    }
}
