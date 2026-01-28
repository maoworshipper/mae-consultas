<?php
/**
 * Main View - Layout and Content Routing
 * Presentation layer only - routes to search or results view
 */

// Get base path for assets
$basePath = BASE_URL;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de Certificados</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href='https://fonts.googleapis.com/css?family=Roboto' rel='stylesheet' type='text/css'>
    <link rel="stylesheet" href="<?php echo $basePath; ?>/assets/css/themes/default.css">
</head>
<body>
    <div class="container">
        <!-- Logo -->
        <div class="row">
            <div class="col-12 text-center">
                <header>
                    <?php if (isset($companyInfo['website']) && !empty($companyInfo['website'])): ?>
                        <a href="http://<?php echo htmlspecialchars($companyInfo['website']); ?>">
                            <img src="<?php echo $basePath; ?>/assets/images/logo.png" 
                                 alt="Logo" 
                                 class="logo">
                        </a>
                    <?php else: ?>
                        <img src="<?php echo $basePath; ?>/assets/images/logo.png" 
                             alt="Logo" 
                             class="logo">
                    <?php endif; ?>
                </header>
            </div>
        </div>

        <?php
        // Decide which view to show
        // Show results if we have found results OR have an error (from POST or GET)
        $showResults = isset($found) || isset($error);
        
        if ($showResults):
            // Show results (or error message)
            require_once __DIR__ . '/results.php';
        else:
            // Show search form
            require_once __DIR__ . '/search.php';
        endif;
        ?>

        <!-- Footer -->
        <footer class="app-footer">
            <div class="footer-line">© <?php echo date('Y'); ?> Licenciado a: <?php echo htmlspecialchars($companyInfo['nombre'] ?? 'Test Company'); ?></div>
            <div class="footer-line">
                <a href="https://servicios.quenube.com" target="_blank" rel="noopener noreferrer">
                    Un producto de Que Nube
                </a>
            </div>
        </footer>
    </div>
</body>
</html>
