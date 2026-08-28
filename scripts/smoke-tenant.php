#!/usr/bin/env php
<?php
/**
 * Smoke de resolución de tenant (sin MySQL).
 *   php scripts/smoke-tenant.php
 */

putenv('MAE_SKIP_PHP_CHECK=true');
$_ENV['MAE_SKIP_PHP_CHECK'] = 'true';
putenv('LICENSE_SKIP_CLI=false');
$_ENV['LICENSE_SKIP_CLI'] = 'false';

$root = dirname(__DIR__);
require_once $root . '/app/Core/env.php';
mae_load_env();
require_once $root . '/app/Core/mae_runtime.php';
require_once $root . '/app/Core/mae_tenant.php';

$fail = 0;
function expect($cond, $msg) {
    global $fail;
    if ($cond) {
        echo "OK  $msg\n";
        return;
    }
    echo "FAIL $msg\n";
    $fail++;
}

expect(mae_license_normalize_host('WWW.Cursos.Autolider.com.co:443') === 'cursos.autolider.com.co', 'normalize host');

$hosts = mae_tenant_registry()['hosts'];
expect(($hosts['cursos.autolider.com.co'] ?? '') === 'autolider', 'autolider host');
expect(($hosts['consultas.grupoeducativodelmeta.com'] ?? '') === 'gem', 'gem host');
expect(($hosts['democonsultas.quenube.com'] ?? '') === 'demo', 'demo host');
expect(($hosts['consultas.quenube.com'] ?? '') === 'demo', 'consultas.quenube.com host');
expect(!isset($hosts['mae.quenube.com']), 'mae.quenube.com no es de consultas');

$tenants = mae_tenant_registry()['tenants'];
expect(!empty($tenants['demo']['active']), 'demo active');
expect(!empty($tenants['gem']['active']), 'gem active');

expect(mae_tenant_slug_valid('mission-zero'), 'slug mission-zero');
expect(!mae_tenant_slug_valid('../etc'), 'slug reject traversal');

$features = mae_tenant_features_from_pack('gem');
expect(($features['QR_ENABLED'] ?? false) === true, 'gem QR on');
expect(($features['QR_REPLACE_PHOTO'] ?? false) === true, 'gem QR replace photo');

$al = mae_tenant_features_from_pack('autolider');
expect(($al['QR_ENABLED'] ?? true) === false, 'autolider QR off');

$path = mae_tenant_secrets_path();
expect(str_contains($path, 'tenants.secrets.php'), 'secrets path name');

echo $fail === 0 ? "\nAll checks passed\n" : "\n$fail failed\n";
exit($fail === 0 ? 0 : 1);
