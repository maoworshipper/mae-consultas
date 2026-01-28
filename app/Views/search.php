<?php

/**
 * Search Form View
 * Displays the certificate search form with toggle switch
 */
?>

<!-- Search Panel -->
<div class="row">
    <div class="col-12 col-md-8 col-md-offset-2">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h1>Consulta de Certificados</h1>
            </div>
            <div class="panel-body">
                <!-- Toggle Switch -->
                <div class="search-toggle-container">
                    <label class="toggle-label active" id="label-identification" onclick="switchToIdentification()">
                        <i class="fa fa-id-card search-icon"></i>
                        Por Identificación
                    </label>

                    <label class="toggle-switch">
                        <input type="checkbox" id="search-toggle" onchange="toggleSearch()">
                        <span class="toggle-slider"></span>
                    </label>

                    <label class="toggle-label" id="label-certificate" onclick="switchToCertificate()">
                        <i class="fa fa-certificate search-icon"></i>
                        Por Certificado
                    </label>
                </div>

                <!-- Search Form -->
                <form method="POST" action="" id="search-form">
                    <!-- Identification Search -->
                    <div id="identification-form" class="fade-in-up">
                        <div class="form-title">
                            <h4>Ingrese el Número de Identificación</h4>
                            <span class="label-custom">sin puntos ni comas</span>
                        </div>
                        <div class="input-container">
                            <input type="text"
                                class="form-control input-lg"
                                name="cedula"
                                placeholder="Ej: 1234567890"
                                id="input-identification"
                                pattern="[0-9]*"
                                inputmode="numeric">
                            <br><br>
                            <button type="submit" class="btn-custom input-lg" style="width: 100%;">
                                <i class="fa fa-search"></i> Consultar por identificación
                            </button>
                        </div>
                    </div>

                    <!-- Certificate Code Search -->
                    <div id="certificate-form" style="display: none;">
                        <div class="form-title">
                            <h4>Ingrese el Número de Certificado</h4>
                            <span class="label-custom">solo números</span>
                        </div>
                        <div class="input-container">
                            <input type="text"
                                class="form-control input-lg"
                                name="codigoc"
                                placeholder="Ej: 12345"
                                id="input-certificate"
                                pattern="[0-9]*"
                                inputmode="numeric">
                            <br><br>
                            <button type="submit" class="btn-custom input-lg" style="width: 100%;">
                                <i class="fa fa-search"></i> Consultar por certificado
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Error Message -->
                <?php if (isset($error) && $error): ?>
                    <div style="margin-top: 20px;">
                        <div style="padding: 15px; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 8px; color: #721c24;">
                            <i class="fa fa-exclamation-triangle"></i>
                            <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleSearch() {
        const checkbox = document.getElementById('search-toggle');
        const identificationForm = document.getElementById('identification-form');
        const certificateForm = document.getElementById('certificate-form');
        const labelId = document.getElementById('label-identification');
        const labelCert = document.getElementById('label-certificate');

        if (checkbox.checked) {
            // Switch to certificate
            identificationForm.style.display = 'none';
            certificateForm.style.display = 'block';
            certificateForm.classList.add('fade-in-up');
            labelId.classList.remove('active');
            labelCert.classList.add('active');

            // Clear identification input
            document.getElementById('input-identification').value = '';
        } else {
            // Switch to identification
            certificateForm.style.display = 'none';
            identificationForm.style.display = 'block';
            identificationForm.classList.add('fade-in-up');
            labelCert.classList.remove('active');
            labelId.classList.add('active');

            // Clear certificate input
            document.getElementById('input-certificate').value = '';
        }
    }

    function switchToIdentification() {
        const checkbox = document.getElementById('search-toggle');
        if (checkbox.checked) {
            checkbox.checked = false;
            toggleSearch();
        }
    }

    function switchToCertificate() {
        const checkbox = document.getElementById('search-toggle');
        if (!checkbox.checked) {
            checkbox.checked = true;
            toggleSearch();
        }
    }
</script>