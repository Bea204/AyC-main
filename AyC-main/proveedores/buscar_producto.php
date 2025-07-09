<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$product = null;

if ($product_id > 0) {
    $stmt = $mysqli->prepare("SELECT * FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();
}

if (!$product) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Buscar Precios | <?= htmlspecialchars($product['product_name']) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .main-content { max-width: 800px; margin: 40px auto 0 auto; }
        .product-info {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 24px rgba(18,24,102,0.10);
            border: 1.5px solid #e3e6f0;
        }
        .product-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
        }
        .product-icon {
            font-size: 2rem;
            color: #121866;
        }
        .product-details {
            flex: 1;
        }
        .product-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: #121866;
            margin: 0;
        }
        .product-sku {
            color: #666;
            font-size: 0.9rem;
            margin-top: 4px;
        }
        .search-providers {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
            margin-top: 24px;
        }
        .provider-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 12px rgba(18,24,102,0.07);
            border: 1px solid #e3e6f0;
            transition: all 0.3s ease;
            text-align: center;
        }
        .provider-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(18,24,102,0.12);
        }
        .provider-icon {
            font-size: 2.5rem;
            margin-bottom: 12px;
        }
        .provider-name {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .provider-description {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 16px;
        }
        .btn-provider {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
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
        .btn-mercadolibre {
            background: #fff8e1;
            color: #ff8f00;
        }
        .btn-mercadolibre:hover {
            background: #ff8f00;
            color: #fff;
        }
        .btn-ebay {
            background: #fce4ec;
            color: #c2185b;
        }
        .btn-ebay:hover {
            background: #c2185b;
            color: #fff;
        }
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #1565c0;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        @media (max-width: 900px) { 
            .main-content { max-width: 95vw; padding: 0 2vw; } 
            .search-providers { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="product-info">
            <div class="product-header">
                <div class="product-icon">
                    <i class="bi bi-box"></i>
                </div>
                <div class="product-details">
                    <h2 class="product-name"><?= htmlspecialchars($product['product_name']) ?></h2>
                    <div class="product-sku">
                        SKU: <?= htmlspecialchars($product['sku']) ?>
                        <?php if ($product['tipo_gestion'] === 'bobina'): ?>
                            <span class="badge bg-info">Bobina</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php if ($product['description']): ?>
                <p class="text-muted"><?= htmlspecialchars($product['description']) ?></p>
            <?php endif; ?>
            
            <div class="info-box">
                <h6><i class="bi bi-info-circle"></i> Buscar Precios</h6>
                <p class="mb-0">Haz clic en cualquier proveedor para buscar precios de este producto. Se abrirá en una ventana externa para no cerrar el programa.</p>
            </div>
        </div>

        <div class="search-providers">
            <!-- Syscom -->
            <div class="provider-card">
                <div class="provider-icon" style="color: #1565c0;">
                    <i class="bi bi-building"></i>
                </div>
                <div class="provider-name">Syscom</div>
                <div class="provider-description">Proveedor principal de electrónicos y componentes</div>
                <button class="btn-provider btn-syscom" onclick="buscarEnProveedor('<?= htmlspecialchars($product['product_name']) ?>', 'syscom')">
                    <i class="bi bi-search"></i> Buscar en Syscom
                </button>
            </div>

            <!-- PCH -->
            <div class="provider-card">
                <div class="provider-icon" style="color: #7b1fa2;">
                    <i class="bi bi-cpu"></i>
                </div>
                <div class="provider-name">PCH</div>
                <div class="provider-description">Proveedor de componentes y equipos</div>
                <button class="btn-provider btn-pch" onclick="buscarEnProveedor('<?= htmlspecialchars($product['product_name']) ?>', 'pch')">
                    <i class="bi bi-search"></i> Buscar en PCH
                </button>
            </div>

            <!-- Amazon -->
            <div class="provider-card">
                <div class="provider-icon" style="color: #f57c00;">
                    <i class="bi bi-bag"></i>
                </div>
                <div class="provider-name">Amazon México</div>
                <div class="provider-description">Tienda online con amplia variedad</div>
                <button class="btn-provider btn-amazon" onclick="buscarEnProveedor('<?= htmlspecialchars($product['product_name']) ?>', 'amazon')">
                    <i class="bi bi-search"></i> Buscar en Amazon
                </button>
            </div>

            <!-- Google -->
            <div class="provider-card">
                <div class="provider-icon" style="color: #2e7d32;">
                    <i class="bi bi-google"></i>
                </div>
                <div class="provider-name">Google Shopping</div>
                <div class="provider-description">Comparador de precios general</div>
                <button class="btn-provider btn-google" onclick="buscarEnProveedor('<?= htmlspecialchars($product['product_name']) ?>', 'google')">
                    <i class="bi bi-search"></i> Buscar en Google
                </button>
            </div>

            <!-- Mercado Libre -->
            <div class="provider-card">
                <div class="provider-icon" style="color: #ff8f00;">
                    <i class="bi bi-shop"></i>
                </div>
                <div class="provider-name">Mercado Libre</div>
                <div class="provider-description">Marketplace con múltiples vendedores</div>
                <button class="btn-provider btn-mercadolibre" onclick="buscarEnProveedor('<?= htmlspecialchars($product['product_name']) ?>', 'mercadolibre')">
                    <i class="bi bi-search"></i> Buscar en Mercado Libre
                </button>
            </div>

            <!-- eBay -->
            <div class="provider-card">
                <div class="provider-icon" style="color: #c2185b;">
                    <i class="bi bi-globe"></i>
                </div>
                <div class="provider-name">eBay</div>
                <div class="provider-description">Subastas y productos nuevos/usados</div>
                <button class="btn-provider btn-ebay" onclick="buscarEnProveedor('<?= htmlspecialchars($product['product_name']) ?>', 'ebay')">
                    <i class="bi bi-search"></i> Buscar en eBay
                </button>
            </div>
        </div>

        <div class="mt-4 text-center">
            <a href="index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver a Proveedores
            </a>
            <a href="../products/edit.php?id=<?= $product_id ?>" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Editar Producto
            </a>
        </div>
    </main>

    <script src="../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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
            
            // Abrir en ventana externa con dimensiones específicas
            const windowFeatures = 'width=1200,height=800,scrollbars=yes,resizable=yes,menubar=yes,toolbar=yes,location=yes,status=yes';
            window.open(url, '_blank', windowFeatures);
        }
    </script>
</body>
</html> 