<?php
/**
 * Helper functions for QR Code generation (mae-v8 compatible).
 */

if (!defined('MAE_CONSULTAS_APP_LOADED') || MAE_CONSULTAS_APP_LOADED !== true) {
	http_response_code(403);
	header('Content-Type: text/plain; charset=UTF-8');
	exit('Forbidden');
}

require_once __DIR__ . '/qrcode.php';

/**
 * Generate QR code image and return temporary file path
 *
 * @param string $id The ID to include in the verification URL
 * @param string $type Type of document ('certificado' or 'carnet')
 * @return string|null Path to temporary file or null if QR disabled
 */
function generateQRCode($id, $type = 'certificado') {
    if (!defined('QR_ENABLED') || !QR_ENABLED) {
        return null;
    }

    $url = str_replace('{id}', $id, QR_VERIFICATION_URL);

    $generator = new QRCode($url);

    $imagenData = $generator->output_image();

    $tempFile = tempnam(sys_get_temp_dir(), 'qr_');
    file_put_contents($tempFile, $imagenData);

    return $tempFile;
}

/**
 * Add QR code to PDF
 *
 * @param FPDF $pdf PDF instance
 * @param string $id Document ID
 * @param float $x X position
 * @param float $y Y position
 * @param float $width QR width
 * @param float $height QR height
 * @param string $type Document type
 * @return bool True if QR was added, false otherwise
 */
function addQRToPDF($pdf, $id, $x, $y, $width, $height, $type = 'certificado') {
    $tempFile = generateQRCode($id, $type);

    if ($tempFile === null) {
        return false;
    }

    $pdf->Image($tempFile, $x, $y, $width, $height, 'PNG');

    unlink($tempFile);

    return true;
}
