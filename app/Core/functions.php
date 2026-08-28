<?php
/**
 * Business Logic Functions
 * All database queries and data processing
 */

/**
 * Clean and validate identification number
 */
function cleanIdentification($id) {
    $id = preg_replace('/[^0-9]/', '', $id);
    return strlen($id) >= 5 && strlen($id) <= 15 ? $id : null;
}

/**
 * Clean and validate certificate code
 */
function cleanCertificateCode($code) {
    $code = preg_replace('/[^0-9]/', '', $code);
    return strlen($code) >= 1 && strlen($code) <= 10 ? $code : null;
}

/**
 * Normalize data source parameter.
 */
function normalizeDataSource($source) {
    return $source === 'legacy' ? 'legacy' : 'main';
}

/**
 * Feature flags: branding pack (hosted) o .env (LICENSE_SKIP).
 */
function isLegacySearchEnabled() {
    return mae_tenant_is_dev_skip() && mae_env_bool('LEGACY_DB_ENABLED', false);
}

function isCertificateFeatureEnabled() {
    return mae_consultas_certificate_enabled();
}

function isCardFeatureEnabled() {
    return mae_consultas_card_enabled();
}

/**
 * Return available database connections.
 */
function getAvailableDataSources() {
    global $pdo, $pdoLegacy;

    $sources = [
        'main' => $pdo,
    ];

    if (isLegacySearchEnabled() && isset($pdoLegacy) && $pdoLegacy instanceof PDO) {
        $sources['legacy'] = $pdoLegacy;
    }

    return $sources;
}

/**
 * Format legacy date to dd-mm-yyyy
 */
function formatLegacyDate($date) {
    if (empty($date)) return '';
    $timestamp = strtotime($date);
    return date('d-m-Y', $timestamp);
}

/**
 * Calculate expiration date based on service
 */
function calculateExpirationDate($courseDate, $vigenciaMonths = null) {
    if (empty($courseDate)) return '';
    
    $timestamp = strtotime($courseDate);
    
    // Default: 12 months if not specified
    $months = $vigenciaMonths ?? 12;
    
    $expiration = strtotime("+$months months", $timestamp);
    return date('d-m-Y', $expiration);
}

/**
 * Get company information
 */
function getCompanyInfo() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM empresa LIMIT 1");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return ['nombre' => 'MAE Consultas', 'website' => ''];
    }
}

/**
 * Find client by identification number
 */
function findClientByIdentification($id, PDO $db) {
    try {
        $stmt = $db->prepare("SELECT Id_Cliente, Nombres, Apellidos, Identificacion
                              FROM clientes
                              WHERE Identificacion = ?
                              LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error finding client: " . $e->getMessage());
        return null;
    }
}

/**
 * Get active enrollments for a client
 */
function getActiveEnrollments($clientId, PDO $db) {
    try {
        $stmt = $db->prepare("SELECT id_matricula
                               FROM matriculas
                               WHERE id_cliente = ?
                               AND estado <> 0
                               AND estado <> 4");
        $stmt->execute([$clientId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        error_log("Error getting enrollments: " . $e->getMessage());
        return [];
    }
}

/**
 * Get courses by enrollment IDs
 */
function getCoursesByEnrollments($enrollmentIds, PDO $db) {
    if (empty($enrollmentIds)) return [];

    $placeholders = str_repeat('?,', count($enrollmentIds) - 1) . '?';

    try {
        $sql = "SELECT ci.*, s.vigencia, s.vence
                FROM cursos_inscritos ci
                LEFT JOIN servicios s ON ci.id_servicio = s.id
                WHERE ci.id_matricula IN ($placeholders)
                ORDER BY ci.f_inscripcion DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($enrollmentIds);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting courses: " . $e->getMessage());
        return [];
    }
}

/**
 * Get course by certificate ID
 */
function getCourseByCertificateId($id, PDO $db) {
    try {
        $sql = "SELECT ci.*, s.vigencia, s.vence
                FROM cursos_inscritos ci
                LEFT JOIN servicios s ON ci.id_servicio = s.id
                WHERE ci.id = ?
                LIMIT 1";

        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting course by certificate ID: " . $e->getMessage());
        return null;
    }
}

/**
 * Get client by ID
 */
function getClientById($id, PDO $db) {
    try {
        $stmt = $db->prepare("SELECT Id_Cliente, Nombres, Apellidos, Identificacion
                               FROM clientes
                               WHERE Id_Cliente = ?
                               LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting client by ID: " . $e->getMessage());
        return null;
    }
}

/**
 * Get client by enrollment ID
 */
function getClientByEnrollmentId($enrollmentId, PDO $db) {
    try {
        $stmt = $db->prepare("SELECT id_cliente
                               FROM matriculas
                               WHERE id_matricula = ?
                               LIMIT 1");
        $stmt->execute([$enrollmentId]);
        $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($enrollment) {
            return getClientById($enrollment['id_cliente'], $db);
        }
        return null;
    } catch (Exception $e) {
        error_log("Error getting client by enrollment: " . $e->getMessage());
        return null;
    }
}

/**
 * Filter expired certificates from results
 */
function filterExpired($results) {
    $filtered = [];
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    
    foreach ($results as $result) {
        // If course doesn't expire (vence = 0), always include it
        if (!isset($result['expires']) || $result['expires'] == 0) {
            $filtered[] = $result;
            continue;
        }
        
        $validUntil = $result['valid_until'] ?? null;
        
        if ($validUntil) {
            $dateObj = DateTime::createFromFormat('d-m-Y', $validUntil);
            
            if ($dateObj) {
                $dateObj->setTime(0, 0, 0);
                // Keep if valid until is today or future
                if ($dateObj >= $today) {
                    $filtered[] = $result;
                }
            } else {
                // Keep if can't parse date (safety)
                $filtered[] = $result;
            }
        } else {
            // Keep if no expiration date
            $filtered[] = $result;
        }
    }
    
    return $filtered;
}

function mapCourseToResult($course, $source) {
    $vigencia = $course['vigencia'] ?? 12;
    $courseDate = $course['f_inscripcion'] ?? '';
    $courseName = $course['nombre'] ?? '';
    $hours = $course['horas'] ?? '';
    $courseId = $course['id'] ?? '';

    $expiration = calculateExpirationDate($courseDate, $vigencia);
    $formattedDate = formatLegacyDate($courseDate);

    return [
        'id' => $courseId,
        'source' => normalizeDataSource($source),
        'course_name' => $courseName,
        'course_date' => $formattedDate,
        'hours' => $hours,
        'valid_until' => $expiration,
        'expires' => $course['vence'] ?? 1,
    ];
}

/**
 * Search certificates by identification
 */
function searchByIdentification($identification) {
    $cleanId = cleanIdentification($identification);
    
    if ($cleanId === null) {
        return [
            'error' => 'Por favor digite un Número de cédula válido',
            'found' => false,
            'results' => []
        ];
    }

    $sources = getAvailableDataSources();
    $results = [];
    $clientName = '';
    $clientIdNumber = '';

    foreach ($sources as $sourceName => $db) {
        $client = findClientByIdentification($cleanId, $db);
        if (!$client) {
            continue;
        }

        if ($clientName === '') {
            $clientName = trim(($client['Nombres'] ?? '') . ' ' . ($client['Apellidos'] ?? ''));
            $clientIdNumber = $client['Identificacion'] ?? '';
        }

        $enrollmentIds = getActiveEnrollments($client['Id_Cliente'], $db);
        if (empty($enrollmentIds)) {
            continue;
        }

        $courses = getCoursesByEnrollments($enrollmentIds, $db);
        foreach ($courses as $course) {
            $results[] = mapCourseToResult($course, $sourceName);
        }
    }

    if (empty($results)) {
        return [
            'error' => 'No se encontraron registros para la identificación proporcionada',
            'found' => false,
            'results' => []
        ];
    }

    $results = filterExpired($results);

    return [
        'error' => null,
        'found' => !empty($results),
        'results' => $results,
        'client_name' => $clientName,
        'client_id' => $clientIdNumber,
        'search_type' => 'identification'
    ];
}

/**
 * Search certificate by code
 */
function searchByCertificateCode($certificateCode) {
    $cleanCode = cleanCertificateCode($certificateCode);
    
    if ($cleanCode === null) {
        return [
            'error' => 'Por favor digite un número de certificado válido',
            'found' => false,
            'results' => []
        ];
    }

    $sources = getAvailableDataSources();
    $results = [];
    $clientName = '';
    $clientIdNumber = '';

    foreach ($sources as $sourceName => $db) {
        $course = getCourseByCertificateId($cleanCode, $db);
        if (!$course) {
            continue;
        }

        $results[] = mapCourseToResult($course, $sourceName);
        if ($clientName === '') {
            $enrollmentId = $course['id_matricula'] ?? null;
            if ($enrollmentId) {
                $client = getClientByEnrollmentId($enrollmentId, $db);
                if ($client) {
                    $clientName = trim(($client['Nombres'] ?? '') . ' ' . ($client['Apellidos'] ?? ''));
                    $clientIdNumber = $client['Identificacion'] ?? '';
                }
            }
        }
    }

    if (empty($results)) {
        return [
            'error' => 'No se encontró el certificado con el código proporcionado',
            'found' => false,
            'results' => []
        ];
    }

    $results = filterExpired($results);

    return [
        'error' => null,
        'found' => !empty($results),
        'results' => $results,
        'client_name' => $clientName,
        'client_id' => $clientIdNumber,
        'search_type' => 'code'
    ];
}
