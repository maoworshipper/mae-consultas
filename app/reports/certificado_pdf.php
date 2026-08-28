<?php
/**
 * Generación de certificado PDF (layout y lógica alineados con mae-v8/src/reports/certificado_pdf.php).
 * Requiere: config, database ($pdo apunta a la fuente elegida), funciones de seguridad ya aplicadas en el entrypoint.
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

try {
	$stmt_empresa = $pdo->query("SELECT * FROM empresa");
	$empresa = $stmt_empresa->fetch(PDO::FETCH_NUM);
	if (!$empresa) {
		$empresa = ['', '', '', '', '', '', '', ''];
	}
} catch (PDOException $e) {
	$empresa = ['', '', '', '', '', '', '', ''];
}

class PDF extends FPDF
{
	function Header()
	{
		$this->SetFont('Helvetica', 'B', 10);
	}

	function Footer()
	{
		global $empresa;
		$this->SetY(-20);
		$this->SetFont('Roboto-Regular', '', 10);
		$this->SetTextColor(11, 77, 161);

		$linea1 = mb_convert_encoding($empresa[3] . ' - ' . $empresa[4] . ' - ' . $empresa[5], 'ISO-8859-1', 'UTF-8');
		$linea2 = mb_convert_encoding('Email: ' . $empresa[6] . ' - ' . $empresa[7], 'ISO-8859-1', 'UTF-8');

		$this->Cell(260, 5, $linea1, 0, 1, 'C');
		$this->Cell(260, 5, $linea2, 0, 1, 'C');
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
	$categoria = $rowservi[0] ?? 0;
	$vence = $rowservi[1] ?? 0;
	$vigencia = $rowservi[2] ?? 12;
} catch (PDOException $e) {
	error_log("Error en certificado_pdf.php (curso): " . $e->getMessage());
	die('Error al obtener datos del curso');
}

$codigofecha = $row[7];

$mesesEspanol = [
	1 => 'enero',
	2 => 'febrero',
	3 => 'marzo',
	4 => 'abril',
	5 => 'mayo',
	6 => 'junio',
	7 => 'julio',
	8 => 'agosto',
	9 => 'septiembre',
	10 => 'octubre',
	11 => 'noviembre',
	12 => 'diciembre'
];

if ($vence == 1 || $vence == true) {
	$tsVence = strtotime(mae_fecha_validez_dmy($codigofecha, (int) $vigencia, 'Y-m-d'));
	$fvencimiento = date('j', $tsVence) . " de " . $mesesEspanol[(int)date('n', $tsVence)] . " de " . date('Y', $tsVence);
} else {
	$fvencimiento = "";
}

$tsEmit = mae_f_inscripcion_to_ts($codigofecha) ?: time();
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

$pdf->AddFont('Montserrat-ExtraBold', '', 'Montserrat-ExtraBold.php');
$pdf->AddFont('Roboto-Regular', '', 'Roboto-Regular.php');
$pdf->AddFont('Roboto-Bold', '', 'Roboto-Bold.php');

if ($rowusr2[2] == "convenio" && $rowusr2[3] <> "") {
	$safeConvenioLogo = mae_sanitize_image_filename((string) $rowusr2[3]);
	if ($safeConvenioLogo !== '') {
		$convenioPath = mae_convenio_logo_absolute_path($safeConvenioLogo);
		if ($convenioPath !== null) {
			$pdf->Image($convenioPath, 85, 27, 50, 24);
		}
	}
}
$pdf->Ln(95);
$pdf->SetFont('Montserrat-ExtraBold', '', 20);
$pdf->SetTextColor(11, 77, 161);
$pdf->Cell(260, 5, strtoupper(mb_convert_encoding($cliente[4] . " " . $cliente[3], 'ISO-8859-1', 'UTF-8')), 0, 1, 'C');
$pdf->Ln(5);

$documentoFormateado = is_numeric($cliente[1]) ? number_format((float)$cliente[1], 0, ',', '.') : $cliente[1];
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Roboto-Regular', '', 12);
$cedula = $documentoFormateado . " de " . $cliente[2];
if ($cliente[15] == "CC" || $cliente[15] == "") {
	$pdf->Cell(260, 6, mb_convert_encoding("Con cédula de ciudadanía No. " . $cedula . ", realizó el curso de", 'ISO-8859-1', 'UTF-8'), 0, 0, 'C');
} elseif ($cliente[15] == "CE") {
	$pdf->Cell(260, 6, mb_convert_encoding("Con cédula de extranjería No. " . $cedula . ", realizó el curso de", 'ISO-8859-1', 'UTF-8'), 0, 0, 'C');
} elseif ($cliente[15] == "PASAPORTE") {
	$pdf->Cell(260, 6, mb_convert_encoding("Con pasaporte No. " . $cedula . ", realizó el curso de", 'ISO-8859-1', 'UTF-8'), 0, 0, 'C');
} elseif ($cliente[15] == "TI") {
	$pdf->Cell(260, 6, mb_convert_encoding("Con tarjeta de identidad No. " . $cedula . ", realizó el curso de", 'ISO-8859-1', 'UTF-8'), 0, 0, 'C');
} elseif ($cliente[15] == "PPT") {
	$pdf->Cell(260, 6, mb_convert_encoding("Con permiso de protección temporal No. " . $cedula . ", realizó el curso de", 'ISO-8859-1', 'UTF-8'), 0, 0, 'C');
}

$pdf->SetTextColor(253, 184, 40);
$pdf->SetFont('Montserrat-ExtraBold', '', 20);
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
	$pdf->SetFont('Montserrat-ExtraBold', '', 16);
	$pdf->Ln(9);
	$pdf->Cell(260, 6, strtoupper(strtolower(mb_convert_encoding($linea1, 'ISO-8859-1', 'UTF-8'))), 0, 1, 'C');
	$pdf->Cell(260, 5, strtoupper(strtolower(mb_convert_encoding($linea2, 'ISO-8859-1', 'UTF-8'))), 0, 1, 'C');
	$pdf->Cell(260, 5, strtoupper(strtolower(mb_convert_encoding($linea3, 'ISO-8859-1', 'UTF-8'))), 0, 1, 'C');
	$pdf->Ln(1);
} else {
	$pdf->Ln(12);
	$pdf->SetFont('Montserrat-ExtraBold', '', 20);
	$pdf->Cell(260, 8, mb_convert_encoding($row[3], 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
	$pdf->Ln(6);
}

$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Roboto-Regular', '', 11);
$finalDate = "";
if ($fvencimiento !== "") {
	$finalDate = " - Válido hasta el " . $fvencimiento;
}

$pdf->Cell(260, 5, mb_convert_encoding("Se expide el certificado en la ciudad de Villavicencio,", 'ISO-8859-1'), 0, 1, 'C');
$pdf->Cell(260, 5, mb_convert_encoding("el día " . $fechaEmitCert . $finalDate, 'ISO-8859-1'), 0, 1, 'C');
$pdf->Cell(260, 5, mb_convert_encoding("Con una intensidad horaria de " . $row[4] . " horas", 'ISO-8859-1'), 0, 1, 'C');

$pdf->SetFont('Roboto-Bold', '', 11);
$pdf->Cell(260, 5, mb_convert_encoding("Código de validación: " . $row[0], 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

if (QR_ENABLED) {
	addQRToPDF($pdf, $idElemento, 230, 42, 40, 40, 'certificado');
}

$nom_arc = "Certificado - " . $cliente[4] . " " . $cliente[3] . " - " . $_GET['certi'] . ".pdf";
ob_end_clean();
$pdf->Output($nom_arc, 'I');
ob_end_flush();
echo "<script>window.open('" . $nom_arc . "','_self','');</script>";
exit;
