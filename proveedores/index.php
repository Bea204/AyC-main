<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

$success = $error = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $name = trim($_POST['name']);
            $contact_name = trim($_POST['contact_name']);
            $phone = trim($_POST['phone']);
            $email = trim($_POST['email']);
            $address = trim($_POST['address']);
            
            if (!empty($name)) {
                $stmt = $mysqli->prepare("INSERT INTO suppliers (name, contact_name, phone, email, address, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("sssss", $name, $contact_name, $phone, $email, $address);
                if ($stmt->execute()) {
                    $success = "Proveedor agregado correctamente.";
                } else {
                    $error = "Error al agregar proveedor: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = "El nombre del proveedor es obligatorio.";
            }
        } elseif ($_POST['action'] === 'edit' && isset($_POST['supplier_id'])) {
            $supplier_id = intval($_POST['supplier_id']);
            $name = trim($_POST['name']);
            $contact_name = trim($_POST['contact_name']);
            $phone = trim($_POST['phone']);
            $email = trim($_POST['email']);
            $address = trim($_POST['address']);
            
            if (!empty($name)) {
                $stmt = $mysqli->prepare("UPDATE suppliers SET name = ?, contact_name = ?, phone = ?, email = ?, address = ? WHERE supplier_id = ?");
                $stmt->bind_param("sssssi", $name, $contact_name, $phone, $email, $address, $supplier_id);
                if ($stmt->execute()) {
                    $success = "Proveedor actualizado correctamente.";
                } else {
                    $error = "Error al actualizar proveedor: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = "El nombre del proveedor es obligatorio.";
            }
        } elseif ($_POST['action'] === 'delete' && isset($_POST['supplier_id'])) {
            $supplier_id = intval($_POST['supplier_id']);
            $stmt = $mysqli->prepare("DELETE FROM suppliers WHERE supplier_id = ?");
            $stmt->bind_param("i", $supplier_id);
            if ($stmt->execute()) {
                $success = "Proveedor eliminado correctamente.";
            } else {
                $error = "Error al eliminar proveedor: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Obtener proveedores
$suppliers = $mysqli->query("SELECT * FROM suppliers ORDER BY name");

// Obtener productos del catálogo para búsqueda
$products = $mysqli->query("SELECT product_id, product_name, sku, tipo_gestion FROM products ORDER BY product_name LIMIT 20");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Proveedores Online | Gestor de inventarios</title>
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
        .search-section {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 24px rgba(18,24,102,0.10);
            border: 1.5px solid #e3e6f0;
        }
        .search-section h5 {
            color: #121866;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .gcse-search {
            margin-top: 16px;
        }
        .product-search-section {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 24px rgba(18,24,102,0.10);
            border: 1.5px solid #e3e6f0;
        }
        .product-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            border: 1px solid #e3e6f0;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .product-card:hover {
            background: #e3f2fd;
            border-color: #1565c0;
        }
        .product-name {
            font-weight: 600;
            color: #121866;
            margin-bottom: 4px;
        }
        .product-sku {
            font-size: 0.85rem;
            color: #666;
        }
        .search-filter {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 16px;
            border: 1px solid #e3e6f0;
        }
        .search-filter .input-group {
            box-shadow: 0 2px 8px rgba(18,24,102,0.05);
        }
        .search-filter .form-control {
            border: 1.5px solid #cfd8dc;
            border-radius: 0 8px 8px 0;
        }
        .search-filter .form-control:focus {
            border-color: #121866;
            box-shadow: 0 0 0 2px #e3e6fa;
        }
        .search-buttons {
            display: flex;
            gap: 8px;
            margin-top: 12px;
            flex-wrap: wrap;
        }
        .btn-search-provider {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-syscom {
            background: #e3f2fd;
            color: #1565c0;
        }
        .btn-syscom:hover {
            background: #1565c0;
            color: #fff;
        }
        .btn-pch {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        .btn-pch:hover {
            background: #7b1fa2;
            color: #fff;
        }
        .btn-amazon {
            background: #fff3e0;
            color: #f57c00;
        }
        .btn-amazon:hover {
            background: #f57c00;
            color: #fff;
        }
        .btn-google {
            background: #e8f5e8;
            color: #2e7d32;
        }
        .btn-google:hover {
            background: #2e7d32;
            color: #fff;
        }
        .supplier-card {
            background: #fff;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: 0 4px 24px rgba(18,24,102,0.10);
            border: 1.5px solid #e3e6f0;
            transition: all 0.3s ease;
        }
        .supplier-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 32px rgba(18,24,102,0.15);
        }
        .supplier-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        .supplier-name {
            font-size: 1.2rem;
            font-weight: 700;
            color: #121866;
            margin: 0;
        }
        .supplier-website {
            color: #1565c0;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .supplier-website:hover {
            text-decoration: underline;
        }
        .supplier-notes {
            color: #666;
            font-size: 0.9rem;
            margin-top: 8px;
        }
        .supplier-actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
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
        }
        .btn-edit {
            background: #e3f2fd;
            color: #1565c0;
        }
        .btn-edit:hover {
            background: #1565c0;
            color: #fff;
        }
        .btn-delete {
            background: #ffebee;
            color: #c62828;
        }
        .btn-delete:hover {
            background: #c62828;
            color: #fff;
        }
        .form-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(18,24,102,0.10);
            border: 1.5px solid #e3e6f0;
            margin-bottom: 24px;
        }
        .form-card .card-header {
            background: linear-gradient(135deg, #121866, #232a7c);
            color: #fff;
            border-radius: 16px 16px 0 0;
            padding: 20px 24px;
            border: none;
        }
        .form-card .card-body {
            padding: 24px;
        }
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #1565c0;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        @media (max-width: 900px) {
            .main-content { 
                width: calc(100vw - 70px); 
                margin-left: 70px; 
                padding: 16px; 
            }
            .titulo-lista { font-size: 1.4rem; }
            .supplier-actions { flex-direction: column; }
            .search-buttons { flex-direction: column; }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="titulo-lista">
            <i class="bi bi-globe"></i> 
            Proveedores Online
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

        <!-- Búsqueda de precios -->
        <div class="search-section">
            <h5><i class="bi bi-search"></i> Buscar Precios de Productos</h5>
            <p class="text-muted mb-0">Busca productos en las principales tiendas online para comparar precios.</p>
            
            <!-- Google Custom Search Engine -->
            <script async src="https://cse.google.com/cse.js?cx=00b3c028eed4f4a22"></script>
            <div class="gcse-search"></div>
        </div>

        <!-- Búsqueda rápida con productos del catálogo -->
        <div class="product-search-section">
            <h5><i class="bi bi-box-seam"></i> Búsqueda Rápida con Productos del Catálogo</h5>
            <p class="text-muted mb-0">Selecciona un producto de tu catálogo para buscar precios automáticamente.</p>
            
            <!-- Filtro de búsqueda en tiempo real -->
            <div class="search-filter mb-3">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" id="searchProduct" placeholder="Buscar producto por nombre, SKU o descripción...">
                    <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                        <i class="bi bi-x-circle"></i> Limpiar
                    </button>
                </div>
                <small class="text-muted">Escribe para filtrar productos en tiempo real</small>
            </div>
            
            <?php if ($products && $products->num_rows > 0): ?>
                <div class="mt-3" id="productsContainer">
                    <?php while ($product = $products->fetch_assoc()): ?>
                        <div class="product-card" data-product-name="<?= strtolower(htmlspecialchars($product['product_name'])) ?>" data-product-sku="<?= strtolower(htmlspecialchars($product['sku'])) ?>" onclick="buscarProducto('<?= htmlspecialchars($product['product_name']) ?>')">
                            <div class="product-name">
                                <i class="bi bi-box"></i> 
                                <?= htmlspecialchars($product['product_name']) ?>
                            </div>
                            <div class="product-sku">
                                SKU: <?= htmlspecialchars($product['sku']) ?>
                                <?php if ($product['tipo_gestion'] === 'bobina'): ?>
                                    <span class="badge bg-info">Bobina</span>
                                <?php endif; ?>
                            </div>
                            <div class="search-buttons">
                                <button class="btn-search-provider btn-syscom" 
                                        onclick="event.stopPropagation(); buscarEnProveedor('<?= htmlspecialchars($product['product_name']) ?>', 'syscom')">
                                    <i class="bi bi-search"></i> Syscom
                                </button>
                                <button class="btn-search-provider btn-pch" 
                                        onclick="event.stopPropagation(); buscarEnProveedor('<?= htmlspecialchars($product['product_name']) ?>', 'pch')">
                                    <i class="bi bi-search"></i> PCH
                                </button>
                                <button class="btn-search-provider btn-amazon" 
                                        onclick="event.stopPropagation(); buscarEnProveedor('<?= htmlspecialchars($product['product_name']) ?>', 'amazon')">
                                    <i class="bi bi-search"></i> Amazon
                                </button>
                                <button class="btn-search-provider btn-google" 
                                        onclick="event.stopPropagation(); buscarEnProveedor('<?= htmlspecialchars($product['product_name']) ?>', 'google')">
                                    <i class="bi bi-search"></i> Google
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info mt-3">
                    <i class="bi bi-info-circle"></i>
                    No hay productos en el catálogo. Agrega productos primero para usar la búsqueda rápida.
                </div>
            <?php endif; ?>
        </div>

        <!-- Información sobre el sistema -->
        <div class="info-box">
            <h6><i class="bi bi-info-circle"></i> ¿Cómo funciona?</h6>
            <ul class="mb-0">
                <li>Busca cualquier producto en el cuadro de arriba</li>
                <li>Usa la búsqueda rápida con productos de tu catálogo</li>
                <li>Los enlaces se abren en ventanas externas para no cerrar el programa</li>
                <li>Compara precios antes de hacer compras</li>
                <li>Guarda los proveedores que más uses en la lista de abajo</li>
            </ul>
        </div>

        <!-- Formulario para nuevo proveedor -->
        <div class="form-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Agregar Proveedor Favorito</h5>
            </div>
            <div class="card-body">
                <form method="POST" id="formNuevoProveedor">
                    <input type="hidden" name="action" value="add">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nombre del proveedor</label>
                                <input type="text" class="form-control" name="name" id="name" required 
                                       placeholder="Ej: Syscom, PCH, Amazon, etc.">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="contact_name" class="form-label">Nombre de Contacto</label>
                                <input type="text" class="form-control" name="contact_name" id="contact_name" 
                                       placeholder="Ej: Juan Pérez">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Teléfono</label>
                                <input type="tel" class="form-control" name="phone" id="phone" 
                                       placeholder="Ej: 55 1234 5678">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" id="email" 
                                       placeholder="Ej: info@proveedor.com">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Dirección</label>
                        <textarea class="form-control" name="address" id="address" rows="2" 
                                  placeholder="Ej: Calle Principal 123, Colonia Centro, Ciudad"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Agregar Proveedor
                    </button>
                </form>
            </div>
        </div>

        <!-- Lista de proveedores -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0" style="color: #121866; font-weight: 600;">
                <i class="bi bi-bookmark-star"></i> Proveedores Favoritos
            </h4>
            <small class="text-muted">
                <?= $suppliers ? $suppliers->num_rows : 0 ?> proveedores guardados
            </small>
        </div>

        <?php if ($suppliers && $suppliers->num_rows > 0): ?>
            <div class="row">
                <?php while ($supplier = $suppliers->fetch_assoc()): ?>
                    <div class="col-lg-6 col-xl-4 mb-3">
                        <div class="supplier-card">
                            <div class="supplier-header">
                                <h6 class="supplier-name">
                                    <i class="bi bi-building"></i> 
                                    <?= htmlspecialchars($supplier['name']) ?>
                                </h6>
                            </div>
                            
                            <?php if ($supplier['contact_name']): ?>
                                <p class="mb-1"><strong>Contacto:</strong> <?= htmlspecialchars($supplier['contact_name']) ?></p>
                            <?php endif; ?>
                            <?php if ($supplier['phone']): ?>
                                <p class="mb-1"><strong>Teléfono:</strong> <?= htmlspecialchars($supplier['phone']) ?></p>
                            <?php endif; ?>
                            <?php if ($supplier['email']): ?>
                                <p class="mb-1"><strong>Email:</strong> <a href="mailto:<?= htmlspecialchars($supplier['email']) ?>" target="_blank"><?= htmlspecialchars($supplier['email']) ?></a></p>
                            <?php endif; ?>
                            <?php if ($supplier['address']): ?>
                                <p class="mb-1"><strong>Dirección:</strong> <?= htmlspecialchars($supplier['address']) ?></p>
                            <?php endif; ?>
                            
                            <div class="supplier-actions">
                                <a href="edit.php?id=<?= $supplier['supplier_id'] ?>" class="btn-action btn-edit">
                                    <i class="bi bi-pencil"></i> Editar
                                </a>
                                <button type="button" class="btn-action btn-delete" 
                                        onclick="eliminarProveedor(<?= $supplier['supplier_id'] ?>, '<?= htmlspecialchars($supplier['name']) ?>')">
                                    <i class="bi bi-trash"></i> Eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-globe" style="font-size: 4rem; color: #ccc;"></i>
                <h5 class="mt-3">No hay proveedores guardados</h5>
                <p class="text-muted">Agrega tus proveedores favoritos para tenerlos a mano.</p>
            </div>
        <?php endif; ?>

        <div class="mt-4">
            <a href="../dashboard/index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver al Dashboard
            </a>
        </div>
    </main>

    <!-- Modal para confirmar eliminación -->
    <div class="modal fade" id="modalEliminarProveedor" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle"></i> Confirmar Eliminación</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formEliminarProveedor">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="supplier_id" id="delete_supplier_id">
                        <p>¿Estás seguro de que quieres eliminar al proveedor <strong id="delete_supplier_name"></strong>?</p>
                        <p class="text-muted mb-0">Esta acción no se puede deshacer.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash"></i> Eliminar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Función para eliminar proveedor
        function eliminarProveedor(id, nombre) {
            document.getElementById('delete_supplier_id').value = id;
            document.getElementById('delete_supplier_name').textContent = nombre;
            
            const modal = new bootstrap.Modal(document.getElementById('modalEliminarProveedor'));
            modal.show();
        }

        // Función para buscar producto en Google CSE
        function buscarProducto(producto) {
            // Enfocar el campo de búsqueda de Google CSE
            const searchInput = document.querySelector('.gsc-input-box input');
            if (searchInput) {
                searchInput.value = producto;
                searchInput.focus();
                // Simular búsqueda
                const searchButton = document.querySelector('.gsc-search-button');
                if (searchButton) {
                    searchButton.click();
                }
            }
        }

        // Función para buscar en proveedores específicos
        function buscarEnProveedor(producto, proveedor) {
            let url = '';
            const productoEncoded = encodeURIComponent(producto);
            
            switch (proveedor) {
                case 'syscom':
                    // Usar búsqueda directa en Google para Syscom
                    url = `https://www.google.com/search?q=${productoEncoded}+site:syscom.mx`;
                    break;
                case 'pch':
                    // Usar búsqueda directa en Google para PCH
                    url = `https://www.google.com/search?q=${productoEncoded}+site:pch.com.mx`;
                    break;
                case 'amazon':
                    url = `https://www.amazon.com.mx/s?k=${productoEncoded}`;
                    break;
                case 'google':
                    url = `https://www.google.com/search?q=${productoEncoded}+precio+comprar`;
                    break;
                case 'mercadolibre':
                    url = `https://listado.mercadolibre.com.mx/${productoEncoded}`;
                    break;
                case 'ebay':
                    url = `https://www.ebay.com/sch/i.html?_nkw=${productoEncoded}`;
                    break;
                default:
                    url = `https://www.google.com/search?q=${productoEncoded}`;
            }
            
            // Abrir en ventana externa
            window.open(url, '_blank', 'width=1200,height=800,scrollbars=yes,resizable=yes');
        }

        // Filtro de búsqueda en tiempo real
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchProduct');
            const clearButton = document.getElementById('clearSearch');
            const productCards = document.querySelectorAll('.product-card');
            
            function filterProducts() {
                const searchTerm = searchInput.value.toLowerCase().trim();
                let visibleCount = 0;
                
                productCards.forEach(card => {
                    const productName = card.getAttribute('data-product-name') || '';
                    const productSku = card.getAttribute('data-product-sku') || '';
                    
                    const matches = productName.includes(searchTerm) || 
                                  productSku.includes(searchTerm);
                    
                    if (matches || searchTerm === '') {
                        card.style.display = 'block';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });
                
                // Mostrar mensaje si no hay resultados
                const noResultsMsg = document.getElementById('noResultsMessage');
                if (visibleCount === 0 && searchTerm !== '') {
                    if (!noResultsMsg) {
                        const msg = document.createElement('div');
                        msg.id = 'noResultsMessage';
                        msg.className = 'alert alert-info mt-3';
                        msg.innerHTML = '<i class="bi bi-info-circle"></i> No se encontraron productos que coincidan con tu búsqueda.';
                        document.getElementById('productsContainer').appendChild(msg);
                    }
                } else if (noResultsMsg) {
                    noResultsMsg.remove();
                }
            }
            
            // Event listeners
            searchInput.addEventListener('input', filterProducts);
            
            clearButton.addEventListener('click', function() {
                searchInput.value = '';
                filterProducts();
                searchInput.focus();
            });
            
            // Inicializar filtro
            filterProducts();
        });

        // Auto-focus en el campo de nombre al cargar la página
        document.getElementById('name').focus();
    </script>
</body>
</html> 