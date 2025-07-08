<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

$success = $error = '';

// Obtener clientes y sus estadísticas
$query = "
    SELECT 
        cl.cliente_id,
        cl.nombre AS cliente_nombre,
        cl.telefono AS cliente_telefono,
        cl.ubicacion AS cliente_ubicacion,
        COUNT(c.cotizacion_id) as total_cotizaciones,
        SUM(c.total) as total_ventas,
        MAX(c.fecha_cotizacion) as ultima_cotizacion,
        MIN(c.fecha_cotizacion) as primera_cotizacion
    FROM clientes cl
    LEFT JOIN cotizaciones c ON cl.cliente_id = c.cliente_id
    GROUP BY cl.cliente_id, cl.nombre, cl.telefono, cl.ubicacion
    ORDER BY ultima_cotizacion DESC
";
$clientes = $mysqli->query($query);

// Estadísticas generales
$stats_query = "
    SELECT 
        COUNT(*) as total_clientes,
        (SELECT COUNT(*) FROM cotizaciones) as total_cotizaciones,
        (SELECT SUM(total) FROM cotizaciones) as total_ventas,
        (SELECT AVG(total) FROM cotizaciones) as promedio_venta
    FROM clientes
";
$stats = $mysqli->query($stats_query)->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Clientes | Gestor de inventarios</title>
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
        .stats-header {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 24px rgba(18,24,102,0.10);
            border: 1.5px solid #e3e6f0;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .stat-card {
            background: linear-gradient(135deg, #121866, #232a7c);
            color: #fff;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            display: block;
            margin-bottom: 8px;
        }
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        .search-section {
            background: #fff;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 24px;
            box-shadow: 0 4px 24px rgba(18,24,102,0.10);
            border: 1.5px solid #e3e6f0;
        }
        .client-card {
            background: #fff;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: 0 4px 24px rgba(18,24,102,0.10);
            border: 1.5px solid #e3e6f0;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .client-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 32px rgba(18,24,102,0.15);
        }
        .client-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, #121866, #232a7c);
        }
        .client-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }
        .client-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: #121866;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .client-contact {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }
        .contact-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .contact-icon {
            color: #667eea;
            font-size: 1.1rem;
        }
        .contact-text {
            color: #666;
            font-size: 0.9rem;
        }
        .client-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }
        .stat-item {
            text-align: center;
            padding: 12px 8px;
            background: #f7f9fc;
            border-radius: 10px;
            border: 1px solid #e3e6f0;
        }
        .stat-number {
            font-size: 1.2rem;
            font-weight: 700;
            color: #121866;
            display: block;
            margin-bottom: 4px;
        }
        .stat-label {
            font-size: 0.8rem;
            color: #666;
            font-weight: 500;
        }
        .client-actions {
            display: flex;
            gap: 8px;
            margin-top: 16px;
        }
        .btn-action {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 4px;
            text-decoration: none;
        }
        .btn-view {
            background: #e3f2fd;
            color: #1565c0;
        }
        .btn-view:hover {
            background: #1565c0;
            color: #fff;
        }
        .btn-cotizar {
            background: #e8f5e8;
            color: #2e7d32;
        }
        .btn-cotizar:hover {
            background: #2e7d32;
            color: #fff;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        .empty-state i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 20px;
        }
        @media (max-width: 900px) {
            .main-content { 
                width: calc(100vw - 70px); 
                margin-left: 70px; 
                padding: 16px; 
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .client-contact {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-people"></i> Gestión de Clientes</h2>
            <div>
                <a href="add.php" class="btn btn-success me-2">
                    <i class="bi bi-person-plus"></i> Agregar Cliente
                </a>
                <a href="../cotizaciones/crear.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Nueva Cotización
                </a>
            </div>
        </div>

        <!-- Estadísticas generales -->
        <div class="stats-header">
            <h5><i class="bi bi-graph-up"></i> Resumen de Clientes</h5>
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-number"><?= $stats['total_clientes'] ?? 0 ?></span>
                    <span class="stat-label">Clientes Únicos</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= $stats['total_cotizaciones'] ?? 0 ?></span>
                    <span class="stat-label">Total Cotizaciones</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number">$<?= number_format($stats['total_ventas'] ?? 0, 0) ?></span>
                    <span class="stat-label">Total Ventas</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number">$<?= number_format($stats['promedio_venta'] ?? 0, 0) ?></span>
                    <span class="stat-label">Promedio por Cotización</span>
                </div>
            </div>
        </div>

        <!-- Búsqueda y filtros -->
        <div class="search-section">
            <div class="row">
                <div class="col-md-8">
                    <input type="text" id="searchClient" class="form-control" placeholder="Buscar cliente por nombre, teléfono o ubicación...">
                </div>
                <div class="col-md-4">
                    <select id="filterStatus" class="form-select">
                        <option value="">Todos los clientes</option>
                        <option value="reciente">Clientes recientes (últimos 30 días)</option>
                        <option value="activo">Clientes activos (más de 1 cotización)</option>
                        <option value="inactivo">Clientes inactivos (sin cotizaciones recientes)</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Lista de clientes -->
        <?php if ($clientes && $clientes->num_rows > 0): ?>
            <div id="clientsContainer">
                <?php while ($cliente = $clientes->fetch_assoc()): ?>
                    <div class="client-card" data-client-name="<?= strtolower($cliente['cliente_nombre']) ?>" data-client-phone="<?= strtolower($cliente['cliente_telefono']) ?>" data-client-location="<?= strtolower($cliente['cliente_ubicacion']) ?>">
                        <div class="client-header">
                            <h5 class="client-name">
                                <i class="bi bi-person-circle"></i>
                                <?= htmlspecialchars($cliente['cliente_nombre']) ?>
                            </h5>
                            <span class="badge bg-<?= $cliente['total_cotizaciones'] > 1 ? 'success' : 'info' ?>">
                                <?= $cliente['total_cotizaciones'] ?> cotización<?= $cliente['total_cotizaciones'] != 1 ? 'es' : '' ?>
                            </span>
                        </div>
                        
                        <div class="client-contact">
                            <?php if ($cliente['cliente_telefono']): ?>
                                <div class="contact-item">
                                    <i class="bi bi-telephone contact-icon"></i>
                                    <span class="contact-text"><?= htmlspecialchars($cliente['cliente_telefono']) ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($cliente['cliente_ubicacion']): ?>
                                <div class="contact-item">
                                    <i class="bi bi-geo-alt contact-icon"></i>
                                    <span class="contact-text"><?= htmlspecialchars($cliente['cliente_ubicacion']) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="client-stats">
                            <div class="stat-item">
                                <span class="stat-number"><?= $cliente['total_cotizaciones'] ?></span>
                                <span class="stat-label">Cotizaciones</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number">$<?= number_format($cliente['total_ventas'] ?? 0, 0) ?></span>
                                <span class="stat-label">Total Ventas</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?= $cliente['ultima_cotizacion'] ? date('d/m/Y', strtotime($cliente['ultima_cotizacion'])) : 'N/A' ?></span>
                                <span class="stat-label">Última Cotización</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?= $cliente['primera_cotizacion'] ? date('d/m/Y', strtotime($cliente['primera_cotizacion'])) : 'N/A' ?></span>
                                <span class="stat-label">Primera Cotización</span>
                            </div>
                        </div>
                        
                        <div class="client-actions">
                            <a href="../cotizaciones/index.php?cliente=<?= urlencode($cliente['cliente_nombre']) ?>" class="btn-action btn-view">
                                <i class="bi bi-eye"></i> Ver Cotizaciones
                            </a>
                            <a href="../cotizaciones/crear.php?cliente=<?= urlencode($cliente['cliente_nombre']) ?>&telefono=<?= urlencode($cliente['cliente_telefono']) ?>&ubicacion=<?= urlencode($cliente['cliente_ubicacion']) ?>" class="btn-action btn-cotizar">
                                <i class="bi bi-plus-circle"></i> Nueva Cotización
                            </a>
                            <a href="edit.php?id=<?= $cliente['cliente_id'] ?>" class="btn-action btn-warning">
                                <i class="bi bi-pencil"></i> Editar
                            </a>
                            <a href="delete.php?id=<?= $cliente['cliente_id'] ?>" class="btn-action btn-danger" onclick="return confirm('¿Seguro que deseas eliminar este cliente?');">
                                <i class="bi bi-trash"></i> Eliminar
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-people"></i>
                <h5>No hay clientes registrados</h5>
                <p>Los clientes se crean automáticamente cuando realizas cotizaciones.</p>
                <a href="../cotizaciones/crear.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Crear Primera Cotización
                </a>
            </div>
        <?php endif; ?>
    </main>

    <script src="../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelector('.sidebar-clientes').classList.add('active');
        
        // Filtro de búsqueda en tiempo real
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchClient');
            const filterSelect = document.getElementById('filterStatus');
            const clientCards = document.querySelectorAll('.client-card');
            
            function filterClients() {
                const searchTerm = searchInput.value.toLowerCase().trim();
                const filterValue = filterSelect.value;
                let visibleCount = 0;
                
                clientCards.forEach(card => {
                    const clientName = card.getAttribute('data-client-name') || '';
                    const clientPhone = card.getAttribute('data-client-phone') || '';
                    const clientLocation = card.getAttribute('data-client-location') || '';
                    
                    const matchesSearch = clientName.includes(searchTerm) || 
                                        clientPhone.includes(searchTerm) || 
                                        clientLocation.includes(searchTerm);
                    
                    let matchesFilter = true;
                    if (filterValue === 'reciente') {
                        // Filtrar por clientes con cotizaciones en los últimos 30 días
                        const lastDate = card.querySelector('.stat-number:last-child').textContent;
                        const thirtyDaysAgo = new Date();
                        thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
                        const lastDateObj = new Date(lastDate.split('/').reverse().join('-'));
                        matchesFilter = lastDateObj >= thirtyDaysAgo;
                    } else if (filterValue === 'activo') {
                        // Filtrar por clientes con más de 1 cotización
                        const cotizaciones = parseInt(card.querySelector('.stat-number').textContent);
                        matchesFilter = cotizaciones > 1;
                    } else if (filterValue === 'inactivo') {
                        // Filtrar por clientes sin cotizaciones recientes
                        const lastDate = card.querySelector('.stat-number:last-child').textContent;
                        const thirtyDaysAgo = new Date();
                        thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
                        const lastDateObj = new Date(lastDate.split('/').reverse().join('-'));
                        matchesFilter = lastDateObj < thirtyDaysAgo;
                    }
                    
                    if (matchesSearch && matchesFilter) {
                        card.style.display = 'block';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });
                
                // Mostrar mensaje si no hay resultados
                const noResultsMsg = document.getElementById('noResultsMessage');
                if (visibleCount === 0 && (searchTerm !== '' || filterValue !== '')) {
                    if (!noResultsMsg) {
                        const msg = document.createElement('div');
                        msg.id = 'noResultsMessage';
                        msg.className = 'empty-state';
                        msg.innerHTML = '<i class="bi bi-search"></i><h5>No se encontraron clientes</h5><p>Intenta ajustar los filtros de búsqueda.</p>';
                        document.getElementById('clientsContainer').appendChild(msg);
                    }
                } else if (noResultsMsg) {
                    noResultsMsg.remove();
                }
            }
            
            // Event listeners
            searchInput.addEventListener('input', filterClients);
            filterSelect.addEventListener('change', filterClients);
            
            // Inicializar filtro
            filterClients();
        });
    </script>
</body>
</html> 