<?php
/**
 * Results View
 * Displays search results
 */
?>

<?php if (!empty($error)): ?>
    <div class="row">
        <div class="col-12 text-center">
            <h3><span class='label label-danger'><?php echo htmlspecialchars($error); ?></span></h3>
            <br><br>
            <a href='<?php echo htmlspecialchars($basePath); ?>/index.php' class='btn-custom input-lg' style='text-decoration:none;'>Regresar e intentar de nuevo</a>
            <br><br>
        </div>
    </div>
<?php elseif (empty($results)): ?>
    <div class="row">
        <div class="col-12 text-center">
            <h3><span class='label label-danger'>No se encontraron resultados</span></h3>
            <br><br>
            <a href='<?php echo htmlspecialchars($basePath); ?>/index.php' class='btn-custom input-lg' style='text-decoration:none;'>Regresar e intentar de nuevo</a>
        </div>
    </div>
<?php else: ?>
    <?php
    // Use clientName and clientId from controller if available
    $displayName = isset($clientName) ? trim($clientName) : '';
    $displayId = isset($clientId) ? $clientId : '';
    $certificateEnabled = isCertificateFeatureEnabled();
    $cardEnabled = isCardFeatureEnabled();
    ?>

    <hr style='margin:5px;'>
    <div class='row text-center'>
        <div class='col-12 col-md-6'>
            <h4><i class='fa fa-user'></i> Nombre: <b><?php echo htmlspecialchars($displayName); ?></b></h4>
        </div>
        <div class='col-12 col-md-6'>
            <h4><i class='fa fa-address-card'></i> Identificación: <b><?php echo htmlspecialchars($displayId); ?></b></h4>
        </div>
    </div>

    <div class='row'>
        <div class='col-12'>
            <table class='table table-bordered table-striped'>
                <thead style='font-weight:bold;'>
                    <tr class='bg-custom'>
                        <td>Capacitación Aprobada</td>
                        <td class="date-header">Fecha</td>
                        <td>Horas</td>
                        <td class="date-header">Válido Hasta</td>
                        <?php if ($certificateEnabled): ?>
                            <td>Certificado</td>
                        <?php endif; ?>
                        <?php if ($cardEnabled): ?>
                            <td>Carnet</td>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $result): ?>
                        <tr>
                            <td width='50%'><?php echo htmlspecialchars($result['course_name']); ?></td>
                            <td><?php echo htmlspecialchars($result['course_date']); ?></td>
                            <td><?php echo htmlspecialchars($result['hours']); ?></td>
                            <td>
                                <?php if ($result['expires'] == 1): ?>
                                    <?php echo htmlspecialchars($result['valid_until']); ?>
                                <?php else: ?>
                                    &nbsp;
                                <?php endif; ?>
                            </td>
                            <?php
                            $source = normalizeDataSource($result['source'] ?? 'main');
                            $certToken = mae_generate_certificate_token($result['id'], $source);
                            ?>
                            <?php if ($certificateEnabled): ?>
                                <td style='margin:auto;'>
                                    <?php
                                    $certUrl = htmlspecialchars($basePath) . '/certificado.php?certi=' . urlencode((string) $result['id']) . '&src=' . urlencode($source);
                                    if ($certToken !== '') {
                                        $certUrl .= '&token=' . urlencode($certToken);
                                    }
                                    ?>
                                    <a href='<?php echo $certUrl; ?>'
                                        target='_blank' class='btn-custom input-lg text-center'
                                        style='text-decoration:none;display:block;'>
                                        Certificado
                                    </a>
                                </td>
                            <?php endif; ?>
                            <?php if ($cardEnabled): ?>
                                <td style='margin:auto'>
                                    <?php
                                    $cardUrl = htmlspecialchars($basePath) . '/carnet.php?certi=' . urlencode((string) $result['id']) . '&src=' . urlencode($source);
                                    if ($certToken !== '') {
                                        $cardUrl .= '&token=' . urlencode($certToken);
                                    }
                                    ?>
                                    <a href='<?php echo $cardUrl; ?>'
                                        target='_blank' class='btn-custom input-lg text-center'
                                        style='text-decoration:none;display:block;'>
                                        Carnet
                                    </a>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <hr>
    <div class='row'>
        <div class='col-12 text-center'>
            <a href='<?php echo htmlspecialchars($basePath); ?>/index.php' class='btn-custom input-lg'
                style='text-decoration:none;'>Consultar Otro</a>
            <?php if (isset($companyInfo['website']) && !empty($companyInfo['website'])): ?>
                <a href='https://<?php echo htmlspecialchars($companyInfo['website']); ?>' class='btn-custom input-lg'
                    style='text-decoration:none;'>
                    Regresar al Inicio
                </a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
