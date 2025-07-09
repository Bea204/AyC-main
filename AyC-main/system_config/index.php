<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

$success = $error = '';

// Simular datos de configuración del sistema
$system_config = [
    'company_name' => 'Alarmas y Cámaras de seguridad del sureste',
    'company_address' => 'Av. Insurgentes Sur 123, Col. Del Valle, CDMX',
    'company_phone' => '55-1234-5678',
    'company_email' => 'info@alarmasycamaras.com',
    'currency' => 'MXN',
    'timezone' => 'America/Mexico_City',
    'date_format' => 'd/m/Y',
    'time_format' => 'H:i:s',
    'low_stock_threshold' => 10,
    'critical_stock_threshold' => 5,
    'auto_backup' => true,
    'backup_frequency' => 'daily',
    'email_notifications' => true,
    'session_timeout' => 30,
    'max_login_attempts' => 3,
    'password_expiry_days' => 90
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configuración del Sistema | Gestor de inventarios</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f4f6fb;
        }
        .main-content {
            margin-top: 40px;
            margin-left: 250px;
            padding: 24px;
            width: calc(100vw - 250px);
            box-sizing: border-box;
        }
        .sidebar.collapsed ~ .main-content {
            margin-left: 70px !important;
            width: calc(100vw - 70px) !important;
            transition: margin-left 0.25s cubic-bezier(.4,2,.6,1), width 0.25s;
        }
        .titulo-lista {
            font-size: 2rem;
            color: #121866;
            font-weight: 700;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .config-section {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 24px rgba(18,24,102,0.10);
            border: 1.5px solid #e3e6f0;
        }
        .config-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 2px solid #f0f0f0;
        }
        .config-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #121866;
            margin: 0;
        }
        .config-icon {
            font-size: 1.5rem;
            color: #121866;
        }
        .form-label {
            font-weight: 600;
            color: #121866;
            margin-bottom: 8px;
        }
        .form-control, .form-select {
            border-radius: 8px;
            border: 1.5px solid #cfd8dc;
            background: #f7f9fc;
            font-size: 1rem;
            padding: 12px 16px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #121866;
            box-shadow: 0 0 0 2px #e3e6fa;
        }
        .btn-primary {
            background: linear-gradient(135deg, #121866, #232a7c);
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(18,24,102,0.3);
        }
        .btn-secondary {
            background: #6c757d;
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .btn-danger {
            background: #dc3545;
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .form-check-input:checked {
            background-color: #121866;
            border-color: #121866;
        }
        .backup-info {
            background: #e3f2fd;
            border: 1px solid #90caf9;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 16px;
        }
        .backup-info h6 {
            color: #1565c0;
            margin-bottom: 8px;
        }
        .backup-info p {
            color: #1976d2;
            margin-bottom: 0;
            font-size: 0.9rem;
        }
        .danger-zone {
            background: #ffebee;
            border: 1px solid #ffcdd2;
            border-radius: 8px;
            padding: 16px;
            margin-top: 16px;
        }
        .danger-zone h6 {
            color: #c62828;
            margin-bottom: 8px;
        }
        .danger-zone p {
            color: #d32f2f;
            margin-bottom: 0;
            font-size: 0.9rem;
        }
        .system-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 16px;
        }
        .system-info h6 {
            color: #495057;
            margin-bottom: 8px;
        }
        .system-info p {
            color: #6c757d;
            margin-bottom: 0;
            font-size: 0.9rem;
        }
        @media (max-width: 900px) {
            .main-content { 
                width: calc(100vw - 70px); 
                margin-left: 70px; 
                padding: 16px; 
            }
            .titulo-lista { font-size: 1.4rem; }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="titulo-lista">
            <i class="bi bi-gear"></i> 
            Configuración del Sistema
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i>
                <?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i>
                <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Información de la empresa -->
        <div class="config-section">
            <div class="config-header">
                <i class="bi bi-building config-icon"></i>
                <h5 class="config-title">Información de la Empresa</h5>
            </div>
            <form method="POST" id="formEmpresa">
                <input type="hidden" name="action" value="company">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="company_name" class="form-label">Nombre de la empresa</label>
                            <input type="text" class="form-control" name="company_name" id="company_name" 
                                   value="<?= htmlspecialchars($system_config['company_name']) ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="company_email" class="form-label">Email de contacto</label>
                            <input type="email" class="form-control" name="company_email" id="company_email" 
                                   value="<?= htmlspecialchars($system_config['company_email']) ?>">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="company_phone" class="form-label">Teléfono</label>
                            <input type="tel" class="form-control" name="company_phone" id="company_phone" 
                                   value="<?= htmlspecialchars($system_config['company_phone']) ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="company_address" class="form-label">Dirección</label>
                            <textarea class="form-control" name="company_address" id="company_address" rows="2"><?= htmlspecialchars($system_config['company_address']) ?></textarea>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Guardar Cambios
                </button>
            </form>
        </div>

        <!-- Configuración regional -->
        <div class="config-section">
            <div class="config-header">
                <i class="bi bi-globe config-icon"></i>
                <h5 class="config-title">Configuración Regional</h5>
            </div>
            <form method="POST" id="formRegional">
                <input type="hidden" name="action" value="regional">
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="currency" class="form-label">Moneda</label>
                            <select class="form-select" name="currency" id="currency">
                                <option value="MXN" <?= $system_config['currency'] === 'MXN' ? 'selected' : '' ?>>Peso Mexicano (MXN)</option>
                                <option value="USD" <?= $system_config['currency'] === 'USD' ? 'selected' : '' ?>>Dólar Estadounidense (USD)</option>
                                <option value="EUR" <?= $system_config['currency'] === 'EUR' ? 'selected' : '' ?>>Euro (EUR)</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="timezone" class="form-label">Zona horaria</label>
                            <select class="form-select" name="timezone" id="timezone">
                                <option value="America/Mexico_City" <?= $system_config['timezone'] === 'America/Mexico_City' ? 'selected' : '' ?>>Ciudad de México</option>
                                <option value="America/Monterrey" <?= $system_config['timezone'] === 'America/Monterrey' ? 'selected' : '' ?>>Monterrey</option>
                                <option value="America/Tijuana" <?= $system_config['timezone'] === 'America/Tijuana' ? 'selected' : '' ?>>Tijuana</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="date_format" class="form-label">Formato de fecha</label>
                            <select class="form-select" name="date_format" id="date_format">
                                <option value="d/m/Y" <?= $system_config['date_format'] === 'd/m/Y' ? 'selected' : '' ?>>DD/MM/YYYY</option>
                                <option value="m/d/Y" <?= $system_config['date_format'] === 'm/d/Y' ? 'selected' : '' ?>>MM/DD/YYYY</option>
                                <option value="Y-m-d" <?= $system_config['date_format'] === 'Y-m-d' ? 'selected' : '' ?>>YYYY-MM-DD</option>
                            </select>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Guardar Cambios
                </button>
            </form>
        </div>

        <!-- Configuración de inventario -->
        <div class="config-section">
            <div class="config-header">
                <i class="bi bi-box-seam config-icon"></i>
                <h5 class="config-title">Configuración de Inventario</h5>
            </div>
            <form method="POST" id="formInventario">
                <input type="hidden" name="action" value="inventory">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="low_stock_threshold" class="form-label">Umbral de stock bajo</label>
                            <input type="number" class="form-control" name="low_stock_threshold" id="low_stock_threshold" 
                                   value="<?= $system_config['low_stock_threshold'] ?>" min="1">
                            <small class="text-muted">Cantidad mínima antes de mostrar alerta de stock bajo</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="critical_stock_threshold" class="form-label">Umbral de stock crítico</label>
                            <input type="number" class="form-control" name="critical_stock_threshold" id="critical_stock_threshold" 
                                   value="<?= $system_config['critical_stock_threshold'] ?>" min="0">
                            <small class="text-muted">Cantidad mínima antes de mostrar alerta crítica</small>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="email_notifications" id="email_notifications" 
                                       <?= $system_config['email_notifications'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="email_notifications">
                                    Notificaciones por email
                                </label>
                            </div>
                            <small class="text-muted">Enviar alertas por email cuando el stock esté bajo</small>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Guardar Cambios
                </button>
            </form>
        </div>

        <!-- Configuración de seguridad -->
        <div class="config-section">
            <div class="config-header">
                <i class="bi bi-shield-lock config-icon"></i>
                <h5 class="config-title">Configuración de Seguridad</h5>
            </div>
            <form method="POST" id="formSeguridad">
                <input type="hidden" name="action" value="security">
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="session_timeout" class="form-label">Tiempo de sesión (minutos)</label>
                            <input type="number" class="form-control" name="session_timeout" id="session_timeout" 
                                   value="<?= $system_config['session_timeout'] ?>" min="5" max="480">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="max_login_attempts" class="form-label">Intentos máximos de login</label>
                            <input type="number" class="form-control" name="max_login_attempts" id="max_login_attempts" 
                                   value="<?= $system_config['max_login_attempts'] ?>" min="1" max="10">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="password_expiry_days" class="form-label">Expiración de contraseña (días)</label>
                            <input type="number" class="form-control" name="password_expiry_days" id="password_expiry_days" 
                                   value="<?= $system_config['password_expiry_days'] ?>" min="0" max="365">
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Guardar Cambios
                </button>
            </form>
        </div>

        <!-- Configuración de respaldos -->
        <div class="config-section">
            <div class="config-header">
                <i class="bi bi-cloud-arrow-up config-icon"></i>
                <h5 class="config-title">Configuración de Respaldos</h5>
            </div>
            <form method="POST" id="formRespaldos">
                <input type="hidden" name="action" value="backup">
                <div class="backup-info">
                    <h6><i class="bi bi-info-circle"></i> Información de respaldos</h6>
                    <p>Los respaldos automáticos incluyen la base de datos y archivos del sistema. Se recomienda configurar respaldos diarios para mantener la seguridad de los datos.</p>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="auto_backup" id="auto_backup" 
                                       <?= $system_config['auto_backup'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="auto_backup">
                                    Respaldos automáticos
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="backup_frequency" class="form-label">Frecuencia de respaldos</label>
                            <select class="form-select" name="backup_frequency" id="backup_frequency">
                                <option value="daily" <?= $system_config['backup_frequency'] === 'daily' ? 'selected' : '' ?>>Diario</option>
                                <option value="weekly" <?= $system_config['backup_frequency'] === 'weekly' ? 'selected' : '' ?>>Semanal</option>
                                <option value="monthly" <?= $system_config['backup_frequency'] === 'monthly' ? 'selected' : '' ?>>Mensual</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Guardar Configuración
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="crearRespaldoManual()">
                        <i class="bi bi-cloud-arrow-up"></i> Crear Respaldo Manual
                    </button>
                </div>
            </form>
        </div>

        <!-- Información del sistema -->
        <div class="config-section">
            <div class="config-header">
                <i class="bi bi-info-circle config-icon"></i>
                <h5 class="config-title">Información del Sistema</h5>
            </div>
            <div class="system-info">
                <h6><i class="bi bi-server"></i> Información técnica</h6>
                <p><strong>Versión del sistema:</strong> 2.1.0</p>
                <p><strong>PHP:</strong> 8.2.12</p>
                <p><strong>Base de datos:</strong> MySQL 10.4.32-MariaDB</p>
                <p><strong>Servidor web:</strong> Apache/2.4.54</p>
                <p><strong>Última actualización:</strong> 15/01/2025</p>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-secondary" onclick="verificarActualizaciones()">
                    <i class="bi bi-arrow-clockwise"></i> Verificar Actualizaciones
                </button>
                <button type="button" class="btn btn-secondary" onclick="verLogs()">
                    <i class="bi bi-file-text"></i> Ver Logs del Sistema
                </button>
            </div>
        </div>

        <!-- Zona de peligro -->
        <div class="config-section">
            <div class="config-header">
                <i class="bi bi-exclamation-triangle config-icon" style="color: #dc3545;"></i>
                <h5 class="config-title" style="color: #dc3545;">Zona de Peligro</h5>
            </div>
            <div class="danger-zone">
                <h6><i class="bi bi-exclamation-triangle"></i> Acciones destructivas</h6>
                <p>Estas acciones son irreversibles y pueden afectar el funcionamiento del sistema.</p>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-danger" onclick="limpiarCache()">
                    <i class="bi bi-trash"></i> Limpiar Cache
                </button>
                <button type="button" class="btn btn-danger" onclick="resetearConfiguracion()">
                    <i class="bi bi-arrow-clockwise"></i> Resetear Configuración
                </button>
                <button type="button" class="btn btn-danger" onclick="eliminarDatos()">
                    <i class="bi bi-exclamation-triangle"></i> Eliminar Todos los Datos
                </button>
            </div>
        </div>

        <div class="mt-4">
            <a href="../dashboard/index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver al Dashboard
            </a>
        </div>
    </main>

    <script src="../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelector('.sidebar-sistema').classList.add('active');
    </script>
    <script>
        // Función para crear respaldo manual
        function crearRespaldoManual() {
            if (confirm('¿Estás seguro de que quieres crear un respaldo manual ahora?')) {
                // Aquí iría la lógica para crear el respaldo
                console.log('Creando respaldo manual...');
                alert('Respaldo iniciado. Se notificará cuando esté completo.');
            }
        }

        // Función para verificar actualizaciones
        function verificarActualizaciones() {
            // Aquí iría la lógica para verificar actualizaciones
            console.log('Verificando actualizaciones...');
            alert('No hay actualizaciones disponibles.');
        }

        // Función para ver logs
        function verLogs() {
            // Aquí iría la lógica para mostrar logs
            console.log('Mostrando logs del sistema...');
            alert('Funcionalidad de logs en desarrollo.');
        }

        // Función para limpiar cache
        function limpiarCache() {
            if (confirm('¿Estás seguro de que quieres limpiar el cache del sistema?')) {
                // Aquí iría la lógica para limpiar cache
                console.log('Limpiando cache...');
                alert('Cache limpiado correctamente.');
            }
        }

        // Función para resetear configuración
        function resetearConfiguracion() {
            if (confirm('¿Estás seguro de que quieres resetear toda la configuración a valores por defecto?')) {
                if (confirm('Esta acción no se puede deshacer. ¿Continuar?')) {
                    // Aquí iría la lógica para resetear configuración
                    console.log('Reseteando configuración...');
                    alert('Configuración reseteada. El sistema se reiniciará.');
                }
            }
        }

        // Función para eliminar datos
        function eliminarDatos() {
            if (confirm('¿Estás seguro de que quieres eliminar TODOS los datos del sistema?')) {
                if (confirm('Esta acción es IRREVERSIBLE. Todos los datos se perderán permanentemente.')) {
                    if (confirm('ÚLTIMA ADVERTENCIA: ¿Realmente quieres eliminar todos los datos?')) {
                        // Aquí iría la lógica para eliminar datos
                        console.log('Eliminando todos los datos...');
                        alert('Acción cancelada por seguridad.');
                    }
                }
            }
        }
    </script>
</body>
</html> 