<?php
/**
 * Main Application Entry Point
 * Handles routing and delegates to business logic
 */

$appRoot = dirname(__DIR__);
require_once $appRoot . '/app/Core/config.php';
require_once $appRoot . '/app/Core/database.php';
require_once $appRoot . '/app/Core/functions.php';
require_once $appRoot . '/app/Core/security.php';

date_default_timezone_set('America/Bogota');

if (!mae_enforce_rate_limit('search', 60, 60)) {
    mae_reject_request(429, 'Demasiadas solicitudes. Intente nuevamente en un minuto.');
}

$error = null;
$found = null;
$results = [];
$clientName = null;
$clientId = null;
$searchType = null;

$companyInfo = getCompanyInfo();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identification = $_POST['cedula'] ?? null;
    $certificateCode = $_POST['codigoc'] ?? null;

    if ($identification !== null && $identification !== '') {
        $searchResult = searchByIdentification($identification);

        $error = $searchResult['error'];
        $found = $searchResult['found'];
        $results = $searchResult['results'];
        $clientName = $searchResult['client_name'] ?? null;
        $clientId = $searchResult['client_id'] ?? null;
        $searchType = $searchResult['search_type'] ?? null;
    } elseif ($certificateCode !== null && $certificateCode !== '') {
        $searchResult = searchByCertificateCode($certificateCode);

        $error = $searchResult['error'];
        $found = $searchResult['found'];
        $results = $searchResult['results'];
        $clientName = $searchResult['client_name'] ?? null;
        $clientId = $searchResult['client_id'] ?? null;
        $searchType = $searchResult['search_type'] ?? null;
    } else {
        $error = 'Por favor ingrese un número de identificación o código de certificado';
    }
}

if (isset($_GET['certi']) && !empty($_GET['certi'])) {
    $certificateCode = $_GET['certi'];
    $searchResult = searchByCertificateCode($certificateCode);

    $error = $searchResult['error'];
    $found = $searchResult['found'];
    $results = $searchResult['results'];
    $clientName = $searchResult['client_name'] ?? null;
    $clientId = $searchResult['client_id'] ?? null;
    $searchType = $searchResult['search_type'] ?? null;
}

require_once $appRoot . '/app/Views/view.php';
