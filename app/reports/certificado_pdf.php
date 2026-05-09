<?php
/**
 * Certificado PDF — mismo contenido y disposición que mae-v8/src/reports/certificado_pdf.php.
 * Entrypoint define MAE_CONSULTAS_INTERNAL, config/database/security ya cargados.
 */

if (!defined('MAE_CONSULTAS_INTERNAL') || MAE_CONSULTAS_INTERNAL !== true) {
	http_response_code(403);
	header('Content-Type: text/plain; charset=UTF-8');
	exit('Forbidden');
}

require_once dirname(__DIR__) . '/Core/mae_report_images.php';
require_once dirname(__DIR__) . '/Core/mae_vigencia_fechas.php';
if (QR_ENABLED) {
	require_once dirname(__DIR__) . '/utils/qr_helper.php';
}

ob_start();
require_once FPDF_PATH . '/fpdf.php';
date_default_timezone_set('America/Bogota');

class PDF extends FPDF
{
	function Header()
	{
		$this->SetFont('Helvetica', 'B', 10);
	}

	function Footer()
	{
		$this->SetY(-20);
		$this->SetFont('Arial', 'I', 10);
	}
}

$idElemento = intval($_GET['certi'] ?? 0);

try {
	$stmt_cursos = $pdo->prepare("SELECT * FROM cursos_inscritos WHERE id = :id AND estado <> 0 AND estado <> 4 AND estado <> 7");
	$stmt_cursos->execute([':id' => $idElemento]);
	$row = $stmt_cursos->fetch(PDO::FETCH_NUM);
	if (!$row) {
		die('Curso no encontrado');
	}

	$stmt_servicios = $pdo->prepare("SELECT categoria_servicio, vence, vigencia FROM servicios WHERE id = :id");
	$stmt_servicios->execute([':id' => $row[2]]);
	$rowservi = $stmt_servicios->fetch(PDO::FETCH_NUM);
	if (!$rowservi) {
		$rowservi = [0, 0, 12];
	}
	$vence = $rowservi[1] ?? 0;
	$vigencia = $rowservi[2] ?? 12;
} catch (PDOException $e) {
	error_log("Error en certificado_pdf.php (curso): " . $e->getMessage());
	die('Error al obtener datos del curso');
}

$codigofecha = $row[7];

$fvencimiento = '';
if ($vence == 1 || $vence == true) {
	$tsVence = strtotime(mae_fecha_validez_dmy($codigofecha, (int) $vigencia, 'Y-m-d'));
	if ($tsVence !== false) {
		$mesesEspanol = [1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril', 5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto', 9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'];
		$fvencimiento = date('j', $tsVence) . ' de ' . $mesesEspanol[(int) date('n', $tsVence)] . ' de ' . date('Y', $tsVence);
	}
}

$tsEmit = mae_f_inscripcion_to_ts($codigofecha) ?: time();

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

	$usrregistro = $row[9] ?? 0;
	$stmt_usr2 = $pdo->prepare("SELECT password, usuario, perfil, foto FROM usuarios WHERE id = :id");
	$stmt_usr2->execute([':id' => $usrregistro]);
	$rowusr2 = $stmt_usr2->fetch(PDO::FETCH_NUM);
	if (!$rowusr2) {
		$rowusr2 = [0, '', '', ''];
	}
} catch (PDOException $e) {
	error_log("Error en certificado_pdf.php (cliente/usuario): " . $e->getMessage());
	die('Error al obtener datos');
}

$pdf = new PDF('L', 'mm', 'Letter');
$pdf->AddPage();

$pdf->Image(mae_report_image_path('bgcerti'), 2, 3, 275, 210);

$pdf->AddFont('DejaVuSansCondensed', '', 'DejaVuSansCondensed.php');
$pdf->AddFont('DejaVuSansCondensed-Bold', '', 'DejaVuSansCondensed-Bold.php');
$pdf->AddFont('DejaVuSansCondensed-Oblique', '', 'DejaVuSansCondensed-Oblique.php');

$conveniosDir = BASE_PATH . '/convenios/';
if ($rowusr2[2] == "convenio" && $rowusr2[3] <> "") {
	$safeConvenioLogo = mae_sanitize_image_filename((string) $rowusr2[3]);
	if ($safeConvenioLogo !== '' && is_file($conveniosDir . $safeConvenioLogo)) {
		$pdf->Image($conveniosDir . $safeConvenioLogo, 85, 27, 50, 24);
	}
}
$pdf->Ln(73);
$pdf->SetFont('DejaVuSansCondensed-Bold', '', 20);
$pdf->Cell(260, 5, strtoupper(mb_convert_encoding($cliente[4] . " " . $cliente[3], 'ISO-8859-1', 'UTF-8')), 0, 1, 'C');
$pdf->Ln(3);

$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', 'B', 12);
$cedula = $cliente[1] . " de " . $cliente[2];
if ($cliente[15] == "CC" || $cliente[15] == "") {
	$pdf->Cell(260, 6, mb_convert_encoding("Identificado con cédula de ciudadanía", 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
} elseif ($cliente[15] == "CE") {
	$pdf->Cell(260, 6, mb_convert_encoding("Identificado con cédula de extranjería", 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
} elseif ($cliente[15] == "PASAPORTE") {
	$pdf->Cell(260, 6, mb_convert_encoding("Identificado con pasaporte", 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
} elseif ($cliente[15] == "TI") {
	$pdf->Cell(260, 6, mb_convert_encoding("Identificado con tarjeta de identidad", 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
} elseif ($cliente[15] == "PPT") {
	$pdf->Cell(260, 6, mb_convert_encoding("Identificado con permiso de protección temporal", 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
}
$pdf->Cell(260, 6, mb_convert_encoding("No. " . $cedula, 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

if (strlen(mb_convert_encoding($row[3], 'ISO-8859-1', 'UTF-8')) > 44) {
	$palabras = explode(" ", $row[3]);
	$caract = 0;
	$linea1 = "";
	$linea2 = "";
	$linea3 = "";
	foreach ($palabras as $palabra) {
		$caract = $caract + strlen($palabra) + 1;
		if ($caract > 44 && $caract <= 88) {
			$linea2 .= $palabra . " ";
		} elseif ($caract > 88) {
			$linea3 .= $palabra . " ";
		} else {
			$linea1 .= $palabra . " ";
		}
	}
	$pdf->SetFont('DejaVuSansCondensed-Bold', '', 18);
	$pdf->Ln(9);
	$pdf->Cell(260, 6, strtoupper(strtolower(mb_convert_encoding($linea1, 'ISO-8859-1', 'UTF-8'))), 0, 1, 'C');
	$pdf->Cell(260, 5, strtoupper(strtolower(mb_convert_encoding($linea2, 'ISO-8859-1', 'UTF-8'))), 0, 1, 'C');
	$pdf->Cell(260, 5, strtoupper(strtolower(mb_convert_encoding($linea3, 'ISO-8859-1', 'UTF-8'))), 0, 1, 'C');
	$pdf->Ln(1);
} else {
	$pdf->Ln(12);
	$pdf->SetFont('DejaVuSansCondensed-Bold', '', 20);
	$pdf->Cell(260, 8, mb_convert_encoding($row[3], 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
	$pdf->Ln(6);
}

$fechaEmitCorta = date('d-m-Y', $tsEmit);
$fvencimientoCorto = ($fvencimiento !== '') ? date('d-m-Y', strtotime(mae_fecha_validez_dmy($codigofecha, (int) $vigencia, 'Y-m-d'))) : '';

$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(260, 6, mb_convert_encoding("Con una intensidad horaria de " . $row[4] . " horas", 'ISO-8859-1'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 12);
if ($fvencimientoCorto !== '') {
	$pdf->Cell(260, 6, mb_convert_encoding("Se expide el día " . $fechaEmitCorta . " - Válido hasta " . $fvencimientoCorto, 'ISO-8859-1'), 0, 1, 'C');
} else {
	$pdf->Cell(260, 6, mb_convert_encoding("Se expide el día " . $fechaEmitCorta, 'ISO-8859-1'), 0, 1, 'C');
}
$pdf->Ln(30);
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(260, 6, mb_convert_encoding($row[0], 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

if (QR_ENABLED) {
	addQRToPDF($pdf, $idElemento, 230, 42, 40, 40, 'certificado');
}

$nom_arc = "Certificado - " . $cliente[4] . " " . $cliente[3] . " - " . $_GET['certi'] . ".pdf";
ob_end_clean();
$pdf->Output($nom_arc, 'I');
ob_end_flush();
echo "<script>window.open('" . $nom_arc . "','_self','');</script>";
exit;
