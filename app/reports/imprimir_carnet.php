<?php
/**
 * Generación de carnet PDF (layout y lógica alineados con mae-v8/src/reports/imprimir_carnet.php).
 * Requiere: config, database ($pdo apunta a la fuente elegida), funciones de seguridad ya aplicadas en el entrypoint.
 */

if (!defined('MAE_CONSULTAS_INTERNAL') || MAE_CONSULTAS_INTERNAL !== true) {
	http_response_code(403);
	header('Content-Type: text/plain; charset=UTF-8');
	exit('Forbidden');
}

require_once dirname(__DIR__) . '/Core/mae_report_images.php';
require_once dirname(__DIR__) . '/Core/rh_catalog.php';
require_once dirname(__DIR__) . '/Core/mae_vigencia_fechas.php';
if (QR_ENABLED) {
	require_once dirname(__DIR__) . '/utils/qr_helper.php';
}

ob_start();
require_once FPDF_PATH . '/fpdf.php';
date_default_timezone_set('America/Bogota');

class PDF extends FPDF
{
	function Header() {}

	function Footer() {}
}

$idElemento = intval($_GET['certi'] ?? 0);

try {
	$stmt_cursos = $pdo->prepare("SELECT * FROM cursos_inscritos WHERE id = :id AND estado <> 0 AND estado <> 7");
	$stmt_cursos->execute([':id' => $idElemento]);
	$row = $stmt_cursos->fetch(PDO::FETCH_NUM);
	if (!$row) {
		die('Curso no encontrado');
	}

	$stmt_servi = $pdo->prepare("SELECT vence, vigencia FROM servicios WHERE id = :id AND activo = 1");
	$stmt_servi->execute([':id' => $row[2]]);
	$rowservi = $stmt_servi->fetch(PDO::FETCH_NUM);
	if (!$rowservi) {
		$rowservi = [0, 12];
	}
	$vence = $rowservi[0] ?? 0;
	$vigencia = $rowservi[1] ?? 12;
} catch (PDOException $e) {
	error_log("Error en imprimir_carnet.php: " . $e->getMessage());
	die('Error al obtener datos');
}

$codigofecha = $row[7];

$mesesEspanol = [
	1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
	5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
	9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
];

if ($vence == 1 || $vence == true) {
	$tsVence = strtotime(mae_fecha_validez_dmy($codigofecha, (int) $vigencia, 'Y-m-d'));
	$fvencimiento = date('j', $tsVence) . " de " . $mesesEspanol[(int)date('n', $tsVence)] . " de " . date('Y', $tsVence);
} else {
	$fvencimiento = "";
}

$tsEmit = strtotime($codigofecha) ?: time();
$fechaEmitCert = date('j', $tsEmit) . " de " . $mesesEspanol[(int)date('n', $tsEmit)] . " de " . date('Y', $tsEmit);

try {
	$stmt_matricula = $pdo->prepare("SELECT id_cliente FROM matriculas WHERE id_matricula = :id_matricula");
	$stmt_matricula->execute([':id_matricula' => $row[1]]);
	$matriculas = $stmt_matricula->fetch(PDO::FETCH_NUM);
	if (!$matriculas) {
		die('Matrícula no encontrada');
	}

	$stmt_cliente = $pdo->prepare("SELECT * FROM clientes WHERE Id_Cliente = :id_cliente");
	$stmt_cliente->execute([':id_cliente' => $matriculas[0]]);
	$cliente = $stmt_cliente->fetch(PDO::FETCH_NUM);
	if (!$cliente) {
		die('Cliente no encontrado');
	}

	$rhCliente = mae_rh_normalize($cliente[6] ?? null) ?? '';
} catch (PDOException $e) {
	error_log("Error en imprimir_carnet.php (cliente): " . $e->getMessage());
	die('Error al obtener datos del cliente');
}

$fotosDir = __DIR__ . '/../../fotos';

$pdf = new PDF('P', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->AddPage();

$pdf->AddFont('Montserrat-ExtraBold', '', 'Montserrat-ExtraBold.php');
$pdf->AddFont('Roboto-Regular', '', 'Roboto-Regular.php');
$pdf->AddFont('Roboto-Bold', '', 'Roboto-Bold.php');
$pdf->AddFont('BarlowCondensed-Regular', '', 'BarlowCondensed-Regular.php');

$pdf->Image(mae_report_image_path('bgcarnet'), 30, 8, 87, 55);

$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(24);

$pdf->Cell(44);
$pdf->SetFont('Montserrat-ExtraBold', '', 9);
$pdf->Cell(50, 5, strtoupper(mb_convert_encoding($cliente[4] . " " . $cliente[3], 'ISO-8859-1', 'UTF-8')), 0, 1, 'L');

$pdf->Cell(48);
$pdf->Cell(50, 5, mb_convert_encoding($cliente[14] . " " . $cliente[1], 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');

$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(42);

if (strlen(mb_convert_encoding($row[3], 'ISO-8859-1', 'UTF-8')) > 30) {
	$palabras = explode(" ", $row[3]);
	$caract = 0;
	$linea1 = "";
	$linea2 = "";
	$linea3 = "";
	foreach ($palabras as $palabra) {
		$caract = $caract + strlen($palabra) + 1;
		if ($caract > 30 && $caract <= 60) {
			$linea2 .= $palabra . " ";
		} elseif ($caract > 60) {
			$linea3 .= $palabra . " ";
		} else {
			$linea1 .= $palabra . " ";
		}
	}
	$pdf->SetFont('Montserrat-ExtraBold', '', 7);
	$pdf->Cell(60, 3, strtoupper(strtolower(mb_convert_encoding($linea1, 'ISO-8859-1', 'UTF-8'))), 0, 1, 'L');
	$pdf->Cell(42);
	$pdf->Cell(60, 2, strtoupper(strtolower(mb_convert_encoding($linea2, 'ISO-8859-1', 'UTF-8'))), 0, 1, 'L');
} else {
	$pdf->SetFont('Montserrat-ExtraBold', '', 7);
	$pdf->Cell(60, 5, mb_convert_encoding($row[3], 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
	$pdf->Ln(1);
}

if (QR_ENABLED && QR_REPLACE_PHOTO) {
	addQRToPDF($pdf, $idElemento, 93, 32, 20, 20, 'carnet');
} else {
	if (is_file($fotosDir . '/' . $cliente[1] . '.jpg')) {
		$pdf->Image($fotosDir . '/' . $cliente[1] . '.jpg', 90, 32, 25, 31);
	} elseif (is_file($fotosDir . '/' . $cliente[1] . '.png')) {
		$pdf->Image($fotosDir . '/' . $cliente[1] . '.png', 90, 32, 25, 31);
	} elseif (is_file($fotosDir . '/' . $cliente[1] . '.jpeg')) {
		$pdf->Image($fotosDir . '/' . $cliente[1] . '.jpeg', 90, 32, 25, 31);
	}

	if (QR_ENABLED) {
		addQRToPDF($pdf, $idElemento, 90, 10, 20, 20, 'carnet');
	}
}

$pdf->SetTextColor(11, 77, 161);
$pdf->SetFont('BarlowCondensed-Regular', '', 10);
$pdf->Ln(1);
$pdf->Cell(30);
$pdf->Cell(66, 4, mb_convert_encoding("Fecha de Expedición: " . $fechaEmitCert, 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

$pdf->SetTextColor(0,0,0);
$pdf->SetFont('Montserrat-ExtraBold', '', 9);
$pdf->Ln(2);
$pdf->Cell(30);
$pdf->Cell(66, 5, mb_convert_encoding("Código de validación: " . $row[0], 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

$nom_arc = "Carnet - " . $cliente[4] . " " . $cliente[3] . " - " . $_GET['certi'] . ".pdf";
ob_end_clean();
$pdf->Output($nom_arc, 'I');
ob_end_flush();
echo "<script>window.open('" . $nom_arc . "','_self','');</script>";
exit;
