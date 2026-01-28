<?php
require_once 'config.php';
require_once 'database.php';
require_once FPDF_PATH . '/fpdf.php';

date_default_timezone_set('America/Bogota');

class PDF extends FPDF
{
    function Header() {}
    function Footer() {}
}

$idElemento = intval($_GET['certi'] ?? 0);
if (!$idElemento) {
    die('ID de certificado requerido');
}

try {
    $stmt_cursos = $pdo->prepare("SELECT * FROM cursos_inscritos WHERE id = :id AND estado <> 0 AND estado <> 7");
    $stmt_cursos->execute([':id' => $idElemento]);
    $row = $stmt_cursos->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        die('Curso no encontrado');
    }

    $stmt_servi = $pdo->prepare("SELECT vence, vigencia FROM servicios WHERE id = :id");
    $stmt_servi->execute([':id' => $row['id_servicio']]);
    $rowservi = $stmt_servi->fetch(PDO::FETCH_ASSOC);
    $vence = $rowservi['vence'] ?? 0;
    $vigencia = $rowservi['vigencia'] ?? 12;
} catch (PDOException $e) {
    error_log("Error obteniendo datos del curso: " . $e->getMessage());
    die('Error al obtener datos');
}

$codigofecha = $row['f_inscripcion'];

if ($vence == 1 || $vence == true) {
    $fvencimiento = strtotime("+{$vigencia} months", strtotime($codigofecha));
    $fvencimiento = date("d-m-Y", $fvencimiento);
} else {
    $fvencimiento = "";
}

$fechaok = date("d-m-Y", mktime(12, 0, 0, 1, $codigofecha - 1, 1900));

try {
    $stmt_matricula = $pdo->prepare("SELECT id_cliente FROM matriculas WHERE id_matricula = :id_matricula");
    $stmt_matricula->execute([':id_matricula' => $row['id_matricula']]);
    $matriculas = $stmt_matricula->fetch(PDO::FETCH_ASSOC);
    if (!$matriculas) {
        die('Matrícula no encontrada');
    }

    $stmt_cliente = $pdo->prepare("SELECT * FROM clientes WHERE Id_Cliente = :id_cliente");
    $stmt_cliente->execute([':id_cliente' => $matriculas['id_cliente']]);
    $cliente = $stmt_cliente->fetch(PDO::FETCH_ASSOC);
    if (!$cliente) {
        die('Cliente no encontrado');
    }

    $stmt_rh = $pdo->prepare("SELECT * FROM rh WHERE id = :id");
    $stmt_rh->execute([':id' => $cliente['Rh'] ?? 0]);
    $rrh = $stmt_rh->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error obteniendo datos del cliente: " . $e->getMessage());
    die('Error al obtener datos del cliente');
}

$pdf = new PDF('P', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->AddPage();

if (file_exists('assets/images/bgcarnet.jpg')) {
    $pdf->Image('assets/images/bgcarnet.jpg', 30, 8, 87, 55);
}

$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(19);

$pdf->Cell(22);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(50, 4, mb_convert_encoding(strtoupper($cliente['Nombres'] . " " . $cliente['Apellidos']), 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
$pdf->Ln(1);
$pdf->Cell(22);
$pdf->SetTextColor(253, 254, 254);
$pdf->Cell(50, 4, mb_convert_encoding(($cliente['Tipo_Identificacion'] ?? 'CC') . " " . $cliente['Identificacion'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');

$pdf->Ln(1);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(21);

$courseName = $row['nombre'] ?? 'Curso no especificado';

if (strlen($courseName) > 38) {
    $palabras = explode(" ", $courseName);
    $caract = 0;
    $linea1 = "";
    $linea2 = "";
    foreach ($palabras as $palabra) {
        $caract = $caract + strlen($palabra) + 1;
        if ($caract > 38 && $caract <= 76) {
            $linea2 .= $palabra . " ";
        } else {
            $linea1 .= $palabra . " ";
        }
    }
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->Cell(60, 3, mb_convert_encoding(strtoupper(strtolower($linea1)), 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
    $pdf->Cell(22);
    $pdf->Cell(60, 3, mb_convert_encoding(strtoupper(strtolower($linea2)), 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
} else {
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(60, 5, mb_convert_encoding($courseName, 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
    $pdf->Ln(1);
}

// Photo
$photoPath = "fotos/" . $cliente['Identificacion'];
if (file_exists($photoPath . ".jpg")) {
    $pdf->Image($photoPath . ".jpg", 90, 32, 25, 31);
} elseif (file_exists($photoPath . ".png")) {
    $pdf->Image($photoPath . ".png", 90, 32, 25, 31);
} elseif (file_exists($photoPath . ".jpeg")) {
    $pdf->Image($photoPath . ".jpeg", 90, 32, 25, 31);
}

$pdf->SetFont('Arial', '', 10);
$pdf->Cell(43);
$pdf->Cell(15, 4, mb_convert_encoding($cliente['Ciudad'] ?? '', 'ISO-8859-1', 'UTF-8'), 0, 0, 'C');
$pdf->Ln(5);
$pdf->Cell(39);
$pdf->Cell(37, 2, mb_convert_encoding(($row['horas'] ?? 0) . ' horas', 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
$pdf->Cell(10, 3, $rrh['rh'] ?? '', 0, 1, 'L');
$pdf->Ln(1);

$pdf->Cell(44);
$pdf->Cell(40, 2, date('d-m-Y', strtotime($codigofecha)), 0, 0, 'L');
if ($vence == 1 || $vence == true) {
    $pdf->Cell(15, 2, $fvencimiento, 0, 1, 'L');
} else {
    $pdf->Cell(20, 2, "", 0, 1, 'C');
}

$nom_arc = "Carnet - " . $cliente['Nombres'] . " " . $cliente['Apellidos'] . " - " . $idElemento . ".pdf";
ob_end_clean();
$pdf->Output($nom_arc, 'I');
?>