<?php

/**
 * Flags de producto por tenant (branding/<id>/features.json).
 */

function mae_consultas_certificate_enabled(): bool
{
    return defined('FEATURE_CERTIFICATE_ENABLED')
        ? FEATURE_CERTIFICATE_ENABLED === true
        : mae_tenant_feature_bool('FEATURE_CERTIFICATE_ENABLED', true);
}

function mae_consultas_card_enabled(): bool
{
    return defined('FEATURE_CARD_ENABLED')
        ? FEATURE_CARD_ENABLED === true
        : mae_tenant_feature_bool('FEATURE_CARD_ENABLED', true);
}

function mae_consultas_qr_enabled(): bool
{
    return defined('QR_ENABLED') && QR_ENABLED === true;
}
