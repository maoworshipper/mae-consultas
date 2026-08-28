<?php
/**
 * Utilidades de seguridad de aplicación.
 */

if (!function_exists('mae_app_key')) {
    function mae_app_key() {
        // Clave requerida para firmar enlaces públicos de certificado/carnet.
        return (string) mae_env('APP_KEY', '');
    }
}

if (!function_exists('mae_generate_certificate_token')) {
    function mae_generate_certificate_token($certificateId, $source = 'main') {
        $key = mae_app_key();
        if ($key === '') {
            return '';
        }
        $payload = (string) $source . '|' . (string) $certificateId;
        return hash_hmac('sha256', $payload, $key);
    }
}

if (!function_exists('mae_verify_certificate_token')) {
    function mae_verify_certificate_token($certificateId, $token, $source = 'main') {
        if (!is_string($token) || $token === '') {
            return false;
        }
        $expected = mae_generate_certificate_token($certificateId, $source);
        if ($expected === '') {
            return false;
        }
        return hash_equals($expected, $token);
    }
}

if (!function_exists('mae_get_client_ip')) {
    function mae_get_client_ip() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        return is_string($ip) && $ip !== '' ? $ip : 'unknown';
    }
}

if (!function_exists('mae_rate_limit_key')) {
    function mae_rate_limit_key($scope) {
        $ip = mae_get_client_ip();
        return sha1($scope . '|' . $ip);
    }
}

if (!function_exists('mae_enforce_rate_limit')) {
    function mae_enforce_rate_limit($scope, $maxRequests, $windowSeconds) {
        $maxRequests = (int) $maxRequests;
        $windowSeconds = (int) $windowSeconds;

        if ($maxRequests < 1 || $windowSeconds < 1) {
            return true;
        }

        $dir = sys_get_temp_dir() . '/mae-consultas-rate-limit';
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            // Si no se puede crear directorio, no bloqueamos tráfico legítimo.
            return true;
        }

        $path = $dir . '/' . mae_rate_limit_key($scope) . '.json';
        $now = time();
        $windowStart = $now - $windowSeconds;

        $handle = @fopen($path, 'c+');
        if ($handle === false) {
            return true;
        }

        $allowed = true;
        if (flock($handle, LOCK_EX)) {
            $raw = stream_get_contents($handle);
            $entries = json_decode($raw ?: '[]', true);
            if (!is_array($entries)) {
                $entries = [];
            }

            $entries = array_values(array_filter($entries, static function ($ts) use ($windowStart) {
                return is_int($ts) && $ts >= $windowStart;
            }));

            if (count($entries) >= $maxRequests) {
                $allowed = false;
            } else {
                $entries[] = $now;
                ftruncate($handle, 0);
                rewind($handle);
                fwrite($handle, json_encode($entries));
            }

            fflush($handle);
            flock($handle, LOCK_UN);
        }

        fclose($handle);
        return $allowed;
    }
}

if (!function_exists('mae_force_https')) {
    function mae_force_https(): void
    {
        if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
            $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
            $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
            if ($host === '') {
                return;
            }
            header('Location: https://' . $host . $uri, true, 301);
            exit;
        }
    }
}

if (!function_exists('mae_reject_request')) {
    function mae_reject_request($statusCode, $message) {
        http_response_code((int) $statusCode);
        echo htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8');
        exit;
    }
}

if (!function_exists('mae_sanitize_image_filename')) {
    function mae_sanitize_image_filename($filename) {
        if (!is_string($filename) || $filename === '') {
            return '';
        }

        $basename = basename($filename);
        if (!preg_match('/^[A-Za-z0-9._-]+\.(jpg|jpeg|png)$/i', $basename)) {
            return '';
        }

        return $basename;
    }
}
