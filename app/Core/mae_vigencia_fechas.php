<?php

if (!defined('MAE_CONSULTAS_APP_LOADED') || MAE_CONSULTAS_APP_LOADED !== true) {
	http_response_code(403);
	header('Content-Type: text/plain; charset=UTF-8');
	exit('Forbidden');
}

/**
 * Fechas de validez de certificado/carnet según servicios.vigencia (igual que mae-v8).
 *
 * La columna `vigencia` se almacena en MESES (p. ej. 12 = un año, 6 = seis meses).
 * Valor por defecto cuando es 0 o inválido: 12 meses.
 */

/** @var int Meses de validez por defecto */
const MAE_VIGENCIA_MESES_DEFAULT = 12;

/** Timestamp de inicio desde f_inscripcion (DATE MySQL o cadena Y-m-d). */
function mae_f_inscripcion_to_ts($fInscripcion): ?int {
	if ($fInscripcion === null || $fInscripcion === '') {
		return null;
	}
	if ($fInscripcion instanceof DateTimeInterface) {
		return $fInscripcion->getTimestamp();
	}
	$s = trim((string) $fInscripcion);
	if ($s === '' || strncmp($s, '0000-00', 7) === 0) {
		return null;
	}
	$dt = date_create_immutable($s, new DateTimeZone('America/Bogota'));
	if ($dt === false) {
		return null;
	}
	return $dt->getTimestamp();
}

/**
 * Fin de validez: f_inscripcion + N meses (vigencia; mínimo 1, por defecto 12).
 */
function mae_ts_fin_validez_por_meses($fInscripcion, $vigenciaMeses): ?int {
	$base = mae_f_inscripcion_to_ts($fInscripcion);
	if ($base === null) {
		$base = strtotime('today');
	}
	$meses = (int) $vigenciaMeses;
	if ($meses < 1) {
		$meses = MAE_VIGENCIA_MESES_DEFAULT;
	}
	$fin = strtotime('+' . $meses . ' months', $base);
	return $fin === false ? null : (int) $fin;
}

function mae_fecha_validez_dmy($fInscripcion, $vigenciaMeses, string $formato = 'd/m/Y'): string {
	$ts = mae_ts_fin_validez_por_meses($fInscripcion, (int) $vigenciaMeses);
	if ($ts === null) {
		return '';
	}
	return date($formato, $ts);
}
