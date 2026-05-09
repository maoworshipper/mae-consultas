<?php
/**
 * Carnet PDF — mismo contenido y disposición que mae-v8/src/reports/imprimir_carnet.php.
 * Entrypoint define MAE_CONSULTAS_INTERNAL, config/database/security ya cargados.
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
	$stmt_cursos = $pdo->prepare("SELECT * FROM cursos_inscritos WHERE id = :id AND estado <> 0 AND estado <> 4 AND estado <> 7");
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

$tsEmit = strtotime($codigofecha) ?: time();

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

$fotosDir = BASE_PATH . '/fotos';

$pdf = new PDF('P', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->AddPage();

$pdf->AddFont('DejaVuSansCondensed', '', 'DejaVuSansCondensed.php');
$pdf->AddFont('DejaVuSansCondensed-Bold', '', 'DejaVuSansCondensed-Bold.php');
$pdf->AddFont('DejaVuSansCondensed-Oblique', '', 'DejaVuSansCondensed-Oblique.php');

$pdf->Image(mae_report_image_path('bgcarnet'), 30, 8, 87, 55);
$pdf->AddFont('Lucida Sans Book', 'B', 'LUZRO.php');

$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(19);

$pdf->Cell(22);
$pdf->SetFont('DejaVuSansCondensed-Bold', '', 11);
$pdf->Cell(50, 4, strtoupper(mb_convert_encoding($cliente[4] . " " . $cliente[3], 'ISO-8859-1', 'UTF-8')), 0, 1, 'L');
$pdf->Ln(1);
$pdf->Cell(21);
$pdf->SetTextColor(253, 254, 254);
$pdf->Cell(50, 4, mb_convert_encoding($cliente[15] . " " . $cliente[1], 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
$pdf->Ln(1);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(22);

if (strlen(mb_convert_encoding($row[3], 'ISO-8859-1', 'UTF-8')) > 38) {
	$palabras = explode(" ", $row[3]);
	$caract = 0;
	$linea1 = "";
	$linea2 = "";
	$linea3 = "";
	foreach ($palabras as $palabra) {
		$caract = $caract + strlen($palabra) + 1;
		if ($caract > 38 && $caract <= 76) {
			$linea2 .= $palabra . " ";
		} elseif ($caract > 76) {
			$linea3 .= $palabra . " ";
		} else {
			$linea1 .= $palabra . " ";
		}
	}
	$pdf->SetFont('DejaVuSansCondensed-Bold', '', 7);
	$pdf->Cell(60, 3, strtoupper(strtolower(mb_convert_encoding($linea1, 'ISO-8859-1', 'UTF-8'))), 0, 1, 'L');
	$pdf->Cell(22);
	$pdf->Cell(60, 3, strtoupper(strtolower(mb_convert_encoding($linea2, 'ISO-8859-1', 'UTF-8'))), 0, 1, 'L');
} else {
	$pdf->SetFont('DejaVuSansCondensed-Bold', '', 10);
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

$pdf->SetFont('DejaVuSansCondensed', '', 10);
$pdf->Cell(43);
$pdf->Cell(15, 4, $cliente[13], 0, 0, 'C');
$pdf->Ln(5);
$pdf->Cell(39);
$pdf->Cell(37, 2, $row[4], 0, 0, 'L');
$pdf->Cell(10, 3, $rhCliente, 0, 1, 'L');
$pdf->Ln(1);

$pdf->Cell(44);
$pdf->Cell(40, 2, date('d-m-Y', $tsEmit), 0, 0, 'L');
if ($vence == 1 || $vence == true) {
	$pdf->Cell(15, 2, date('d-m-Y', strtotime(mae_fecha_validez_dmy($codigofecha, (int) $vigencia, 'Y-m-d'))), 0, 1, 'L');
} else {
	$pdf->Cell(20, 2, "", 0, 1, 'C');
}

$nom_arc = "Carnet - " . $cliente[4] . " " . $cliente[3] . " - " . $_GET['certi'] . ".pdf";
ob_end_clean();
$pdf->Output($nom_arc, 'I');
ob_end_flush();
echo "<script>window.open('" . $nom_arc . "','_self','');</script>";
exit;
