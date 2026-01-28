<?php
/**
 * Main Application Entry Point
 * Handles routing and delegates to business logic
 */

// Load configuration and dependencies
require_once 'app/Core/config.php';
require_once 'app/Core/database.php';
require_once 'app/Core/functions.php';

// Set timezone
date_default_timezone_set('America/Bogota');

// Initialize variables for view
$error = null;
$found = null;
$results = [];
$clientName = null;
$clientId = null;
$searchType = null;

// Get company information
$companyInfo = getCompanyInfo();

// Process search if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identification = $_POST['cedula'] ?? null;
    $certificateCode = $_POST['codigoc'] ?? null;
    
    // Search by identification
    if ($identification !== null && $identification !== '') {
        $searchResult = searchByIdentification($identification);
        
        $error = $searchResult['error'];
        $found = $searchResult['found'];
        $results = $searchResult['results'];
        $clientName = $searchResult['client_name'] ?? null;
        $clientId = $searchResult['client_id'] ?? null;
        $searchType = $searchResult['search_type'] ?? null;
    }
    // Search by certificate code
    elseif ($certificateCode !== null && $certificateCode !== '') {
        $searchResult = searchByCertificateCode($certificateCode);
        
        $error = $searchResult['error'];
        $found = $searchResult['found'];
        $results = $searchResult['results'];
        $clientName = $searchResult['client_name'] ?? null;
        $clientId = $searchResult['client_id'] ?? null;
        $searchType = $searchResult['search_type'] ?? null;
    }
    else {
        $error = 'Por favor ingrese un número de identificación o código de certificado';
    }
}

// Process GET request for certificate ID
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

// Load view
require_once 'app/Views/view.php';

