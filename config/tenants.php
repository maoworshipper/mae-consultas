<?php

/**
 * Registro de tenants (hosts públicos de consulta). Sin secretos.
 * DB_* vive en mae-v8 config/tenants.secrets.php (MAE_SECRETS_PATH) o en
 * config/tenants.secrets.php local.
 *
 * URL pública del demo: democonsultas.quenube.com
 * Destino CNAME compartido (CEAs): consultas.quenube.com
 */

return [
    'hosts' => [
        'democonsultas.quenube.com' => 'demo',
        'consultas.quenube.com' => 'demo',
        'cursos.autolider.com.co' => 'autolider',
        'consultas.grupoeducativodelmeta.com' => 'gem',
        'consultas.missionzero.example' => 'mission-zero',
    ],
    'tenants' => [
        'demo' => ['active' => true],
        'autolider' => ['active' => true],
        'gem' => ['active' => true],
        'mission-zero' => ['active' => true],
    ],
];
