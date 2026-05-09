<?php

if (!defined('MAE_CONSULTAS_APP_LOADED') || MAE_CONSULTAS_APP_LOADED !== true) {
	http_response_code(403);
	header('Content-Type: text/plain; charset=UTF-8');
	exit('Forbidden');
}

function mae_rh_options()
{
    return ['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'];
}

function mae_rh_legacy_id_map()
{
    return [
        1 => 'O+',
        2 => 'O-',
        3 => 'A+',
        4 => 'A-',
        5 => 'B+',
        6 => 'B-',
        7 => 'AB+',
        8 => 'AB-',
    ];
}

function mae_rh_normalize($value)
{
    if ($value === null) {
        return null;
    }

    $raw = strtoupper(trim((string)$value));
    if ($raw === '') {
        return null;
    }

    if (ctype_digit($raw)) {
        $legacyId = intval($raw);
        $legacyMap = mae_rh_legacy_id_map();
        return $legacyMap[$legacyId] ?? null;
    }

    $raw = str_replace(' ', '', $raw);
    $valid = mae_rh_options();
    return in_array($raw, $valid, true) ? $raw : null;
}

function mae_rh_is_valid($value)
{
    return mae_rh_normalize($value) !== null;
}
