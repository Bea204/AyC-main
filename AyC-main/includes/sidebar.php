<?php
// Obtener total de productos para el badge
if (!isset($total_products)) {
    require_once __DIR__ . '/../connection.php';
    $total_query = "SELECT COUNT(*) as total FROM products";
    $total_result = $mysqli->query($total_query);
    $total_products = $total_result ? $total_result->fetch_assoc()['total'] : 0;
}
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header sidebar-header-compact">
        <div class="sidebar-toggle-container">
            <button id="toggleSidebar" aria-label="Mostrar/ocultar menú">
                <i class="bi bi-list"></i>
            </button>
        </div>
        <div class="sidebar-brand sidebar-brand-compact">
            <div class="brand-logo brand-logo-compact">
                <i class="bi bi-shield-check"></i>
            </div>
            <div class="brand-text brand-text-compact">
                <h1>Gestor de Inventarios</h1>
                <p>Alarmas y Cámaras de seguridad del sureste</p>
            </div>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <!-- Sección Principal -->
        <div class="nav-section">
            <div class="nav-section-header">
                <span class="section-title">Principal</span>
            </div>
            <div class="nav-links">
                <a href="../dashboard/index.php" class="sidebar-dashboard" title="Dashboard">
                    <i class="bi bi-grid-3x3-gap-fill"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
                <a href="../inventory/index.php" class="sidebar-inventario" title="Inventario">
                    <i class="bi bi-boxes"></i>
                    <span class="nav-text">Inventario</span>
                    <span class="badge bg-success"><?= $total_products ?></span>
                </a>
                <a href="../cotizaciones/index.php" class="sidebar-cotizaciones" title="Cotizaciones">
                    <i class="bi bi-file-earmark-text"></i>
                    <span class="nav-text">Cotizaciones</span>
                </a>
                <a href="../equipos/equipos.php" class="sidebar-equipos" title="Equipos">
                    <i class="bi bi-tools"></i>
                    <span class="nav-text">Equipos</span>
                </a>
                <a href="../insumos/insumos.php" class="sidebar-insumos" title="Insumos">
                    <i class="bi bi-box2"></i>
                    <span class="nav-text">Insumos</span>
                </a>
            </div>
        </div>

        <!-- Sección Gestión -->
        <div class="nav-section">
            <div class="nav-section-header">
                <span class="section-title">Gestión</span>
            </div>
            <div class="nav-links">
                <a href="../products/list.php" class="sidebar-productos" title="Productos">
                    <i class="bi bi-box-seam-fill"></i>
                    <span class="nav-text">Productos</span>
                </a>
                <a href="../movements/index.php" class="sidebar-movimientos" title="Movimientos">
                    <i class="bi bi-arrow-left-right"></i>
                    <span class="nav-text">Movimientos</span>
                </a>
                <a href="../products/categories.php" class="sidebar-categorias" title="Categorías">
                    <i class="bi bi-tags"></i>
                    <span class="nav-text">Categorías</span>
                </a>
            </div>
        </div>

        <!-- Sección Comercial -->
        <div class="nav-section">
            <div class="nav-section-header">
                <span class="section-title">Comercial</span>
            </div>
            <div class="nav-links">
                <a href="../proveedores/index.php" class="sidebar-proveedores" title="Proveedores">
                    <i class="bi bi-globe"></i>
                    <span class="nav-text">Proveedores</span>
                </a>
                <a href="../clients/index.php" class="sidebar-clientes" title="Clientes">
                    <i class="bi bi-people"></i>
                    <span class="nav-text">Clientes</span>
                </a>
            </div>
        </div>

        <!-- Sección Reportes -->
        <div class="nav-section">
            <div class="nav-section-header">
                <span class="section-title">Reportes</span>
            </div>
            <div class="nav-links">
                <a href="../reports/index.php" class="sidebar-reportes" title="Reportes">
                    <i class="bi bi-graph-up"></i>
                    <span class="nav-text">Reportes</span>
                </a>
            </div>
        </div>

        <!-- Sección Sistema -->
        <div class="nav-section">
            <div class="nav-section-header">
                <span class="section-title">Sistema</span>
            </div>
            <div class="nav-links">
                <a href="../usuarios/index.php" class="sidebar-configuracion" title="Usuarios">
                    <i class="bi bi-person-gear"></i>
                    <span class="nav-text">Usuarios</span>
                </a>
                <a href="../system_config/index.php" class="sidebar-sistema" title="Sistema">
                    <i class="bi bi-sliders"></i>
                    <span class="nav-text">Sistema</span>
                </a>
            </div>
        </div>
    </nav>

    <div class="sidebar-footer mid-compact-footer">
        <div class="mid-compact-user-row">
            <span class="user-avatar mid-compact-avatar"><i class="bi bi-person-circle"></i></span>
            <span class="user-name mid-compact-name">Usuario</span>
            <a href="../auth/logout.php" class="mid-compact-logout" title="Cerrar sesión">
                <i class="bi bi-box-arrow-right"></i>
                <span class="logout-text">Salir</span>
            </a>
        </div>
    </div>
</aside>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('toggleSidebar');
    
    if (!sidebar || !toggleBtn) return;
    
    // Cargar estado guardado
    if (localStorage.getItem('sidebar-collapsed') === 'true') {
        sidebar.classList.add('collapsed');
    }
    
    toggleBtn.addEventListener('click', function() {
        sidebar.classList.toggle('collapsed');
        localStorage.setItem('sidebar-collapsed', sidebar.classList.contains('collapsed'));
        
        // Forzar reflow para asegurar que las transiciones funcionen
        sidebar.offsetHeight;
    });
    
    // Debug: mostrar en consola el estado del sidebar
    console.log('Sidebar inicial:', sidebar.classList.contains('collapsed') ? 'colapsado' : 'expandido');

    // Guardar y restaurar la posición del scroll del sidebar
    const guardarScrollSidebar = () => {
        localStorage.setItem('sidebar-scroll', sidebar.scrollTop);
    };
    // Restaurar al cargar
    const scrollGuardado = localStorage.getItem('sidebar-scroll');
    if (scrollGuardado !== null) {
        sidebar.scrollTop = parseInt(scrollGuardado);
    }
    // Guardar al hacer scroll
    sidebar.addEventListener('scroll', guardarScrollSidebar);
});
</script> 