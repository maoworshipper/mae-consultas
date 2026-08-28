<?php

/**
 * Copiar a config/tenants.secrets.php en el cPanel, o apuntar
 * MAE_SECRETS_PATH al archivo de mae-v8 (recomendado: un solo origen).
 * Un bloque por CLIENT_ID. Los IDs deben coincidir con mae-v8.
 */

return [
    'demo' => [
        'host' => 'localhost',
        'name' => 'change_me_demo',
        'user' => 'change_me',
        'pass' => 'change_me',
        'charset' => 'utf8mb4',
    ],
    'autolider' => [
        'host' => 'localhost',
        'name' => 'change_me_autolider',
        'user' => 'change_me',
        'pass' => 'change_me',
        'charset' => 'utf8mb4',
    ],
    'gem' => [
        'host' => 'localhost',
        'name' => 'change_me_gem',
        'user' => 'change_me',
        'pass' => 'change_me',
        'charset' => 'utf8mb4',
    ],
    'mission-zero' => [
        'host' => 'localhost',
        'name' => 'change_me_mission_zero',
        'user' => 'change_me',
        'pass' => 'change_me',
        'charset' => 'utf8mb4',
    ],
];
