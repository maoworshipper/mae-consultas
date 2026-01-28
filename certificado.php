<?php
require_once 'config.php';
require_once 'database.php';
require_once FPDF_PATH . '/fpdf.php';

date_default_timezone_set('America/Bogota');

$idElemento = intval($_GET['certi'] ?? 0);
if (!$idElemento) {
    die('ID de certificado requerido');
}

try {
    $stmt_cursos = $pdo->prepare("SELECT * FROM cursos_inscritos WHERE id = :id AND estado <> 0 AND estado <> 4 AND estado <> 7");
    $stmt_cursos->execute([':id' => $idElemento]);
    $row = $stmt_cursos->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        die('Curso no encontrado');
    }

    $stmt_servicios = $pdo->prepare("SELECT categoria_servicio, vence, vigencia FROM servicios WHERE id = :id");
    $stmt_servicios->execute([':id' => $row['id_servicio']]);
    $rowservi = $stmt_servicios->fetch(PDO::FETCH_ASSOC);
    $categoria = $rowservi['categoria_servicio'] ?? 0;
    $vence = $rowservi['vence'] ?? 0;
    $vigencia = $rowservi['vigencia'] ?? 12;
} catch (PDOException $e) {
    error_log("Error obteniendo datos del curso: " . $e->getMessage());
    die('Error al obtener datos del curso');
}

$codigofecha = $row['f_inscripcion'];

$codigofecha_num = is_numeric($codigofecha) ? (int)$codigofecha : 0;

if ($vence == 1 || $vence == true) {
    $fvencimiento = strtotime("+{$vigencia} months", strtotime($codigofecha));
    $fvencimiento = date("d-m-Y", $fvencimiento);
} else {
    $fvencimiento = "";
}

$fechaok = date("d-m-Y", mktime(12, 0, 0, 1, $codigofecha_num - 1, 1900));

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

    $usrregistro = $row['usrregistro'] ?? 0;
    $stmt_usr2 = $pdo->prepare("SELECT password, usuario, perfil, foto FROM usuarios WHERE id = :id");
    $stmt_usr2->execute([':id' => $usrregistro]);
    $rowusr2 = $stmt_usr2->fetch(PDO::FETCH_ASSOC);
    if (!$rowusr2) {
        $rowusr2 = ['foto' => ''];
    }
} catch (PDOException $e) {
    error_log("Error obteniendo datos del cliente: " . $e->getMessage());
    die('Error al obtener datos');
}

class PDF extends FPDF
{
    function Header() {
        $this->SetFont('Helvetica', 'B', 10);
    }

    function Footer() {
        $this->SetY(-20);
        $this->SetFont('Arial', 'I', 10);
    }
}

$pdf = new PDF('L', 'mm', 'Letter');
$pdf->AddPage();

if (file_exists('assets/images/bgcerti.jpg')) {
    $pdf->Image('assets/images/bgcerti.jpg', 2, 3, 275, 210);
}

if ($rowusr2['perfil'] == "convenio" && $rowusr2['foto'] <> "") {
    if (file_exists('convenios/' . $rowusr2['foto'])) {
        $pdf->Image('convenios/' . $rowusr2['foto'], 85, 27, 50, 24);
    }
}

$pdf->Ln(83);
$pdf->SetFont('Arial', 'B', 20);
$pdf->Cell(260, 5, mb_convert_encoding(strtoupper($cliente['Nombres'] . " " . $cliente['Apellidos']), 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
$pdf->Ln(3);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', 'B', 12);

$cedula = $cliente['Identificacion'];
$tipo_id = $cliente['Tipo_Identificacion'] ?? 'CC';

if ($tipo_id == "CC" || $tipo_id == "") {
    $pdf->Cell(260, 6, mb_convert_encoding("Identificado con cédula de ciudadanía", 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
} elseif ($tipo_id == "CE") {
    $pdf->Cell(260, 6, mb_convert_encoding("Identificado con cédula de extranjería", 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
} elseif ($tipo_id == "PASAPORTE") {
    $pdf->Cell(260, 6, mb_convert_encoding("Identificado con pasaporte", 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
} elseif ($tipo_id == "TI") {
    $pdf->Cell(260, 6, mb_convert_encoding("Identificado con tarjeta de identidad", 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
} elseif ($tipo_id == "PPT") {
    $pdf->Cell(260, 6, mb_convert_encoding("Identificado con permiso de protección temporal", 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
}
$pdf->Cell(260, 6, mb_convert_encoding("No. " . $cedula, 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

$courseName = $row['nombre'] ?? 'Curso no especificado';

if (strlen($courseName) > 44) {
    $palabras = explode(" ", $courseName);
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
    $pdf->Ln(9);
    $pdf->Cell(260, 6, mb_convert_encoding(strtoupper(strtolower($linea1)), 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
    $pdf->Cell(260, 5, mb_convert_encoding(strtoupper(strtolower($linea2)), 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
    $pdf->Cell(260, 5, mb_convert_encoding(strtoupper(strtolower($linea3)), 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
    $pdf->Ln(1);
} else {
    $pdf->Ln(12);
    $pdf->SetFont('Arial', 'B', 20);
    $pdf->Cell(260, 8, mb_convert_encoding($courseName, 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
    $pdf->Ln(6);
}

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(260, 6, mb_convert_encoding("Con una intensidad horaria de " . ($row['horas'] ?? 0) . " horas", 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 12);

if ($fvencimiento !== "") {
    $pdf->Cell(260, 6, mb_convert_encoding("Se expide el día " . date('d-m-Y', strtotime($codigofecha)) . " - Válido hasta " . $fvencimiento, 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
} else {
    $pdf->Cell(260, 6, mb_convert_encoding("Se expide el día " . date('d-m-Y', strtotime($codigofecha)), 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
}
$pdf->Ln(6);

$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(260, 6, $idElemento, 0, 1, 'C');

$nom_arc = "Certificado - " . $cliente['Nombres'] . " " . $cliente['Apellidos'] . " - " . $idElemento . ".pdf";
ob_end_clean();
$pdf->Output($nom_arc, 'I');
?>