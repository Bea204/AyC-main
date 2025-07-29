<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

// Obtener parámetros
$barcode = $_GET['barcode'] ?? '';
$product_name = $_GET['product_name'] ?? '';
$cantidad = intval($_GET['cantidad'] ?? 1);
$tamano = $_GET['tamano'] ?? '50x30';

if (empty($barcode)) {
    die('Código de barras no especificado');
}

// Validar cantidad
if ($cantidad < 1 || $cantidad > 50) {
    $cantidad = 1;
}

// Validar tamaño
$tamanos_validos = ['40x20', '50x30', '60x40'];
if (!in_array($tamano, $tamanos_validos)) {
    $tamano = '50x30';
}

$tamano_width = explode('x', $tamano)[0];
$tamano_height = explode('x', $tamano)[1];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Imprimir etiquetas - <?= htmlspecialchars($product_name) ?></title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 10px;
        }
        .etiqueta { 
            border: 1px solid #000; 
            padding: 3px; 
            margin: 1px;
            text-align: center;
            display: inline-block;
            page-break-inside: avoid;
            box-sizing: border-box;
            background: white;
        }
        .codigo-barras { 
            font-family: 'Courier New', monospace; 
            font-size: 12px; 
            font-weight: bold;
            margin: 2px 0;
            letter-spacing: 0.5px;
            line-height: 1;
        }
        .nombre-producto { 
            font-size: 8px; 
            margin: 1px 0;
            word-wrap: break-word;
            line-height: 1;
            font-weight: normal;
        }
        @media print {
            body { 
                margin: 0; 
                padding: 0; 
                background: white;
            }
            .etiqueta { 
                border: none; 
                margin: 0; 
                padding: 1px;
            }
            .no-print { display: none; }
            @page {
                margin: 0;
                size: auto;
            }
        }
        .info-impresion {
            background: #f8f9fa;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
        .etiqueta-preview {
            border: 2px dashed #ccc;
            background: #f9f9f9;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <div class="info-impresion">
            <h4><i class="bi bi-printer"></i> Imprimir etiquetas de código de barras</h4>
            <p><strong>Producto:</strong> <?= htmlspecialchars($product_name) ?></p>
            <p><strong>Código de barras:</strong> <code><?= htmlspecialchars($barcode) ?></code></p>
            <p><strong>Cantidad:</strong> <?= $cantidad ?> etiquetas</p>
            <p><strong>Tamaño:</strong> <?= $tamano ?>mm</p>
            <button onclick="window.print()" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
                <i class="bi bi-printer"></i> Imprimir
            </button>
            <button onclick="window.close()" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">
                <i class="bi bi-x-circle"></i> Cerrar
            </button>
        </div>
    </div>

    <?php for ($i = 0; $i < $cantidad; $i++): ?>
        <div class="etiqueta <?= !isset($_GET['autoprint']) ? 'etiqueta-preview' : '' ?>" style="width: <?= $tamano_width ?>mm; height: <?= $tamano_height ?>mm;">
            <div class="nombre-producto"><?= htmlspecialchars($product_name) ?></div>
            <div class="codigo-barras"><?= htmlspecialchars($barcode) ?></div>
        </div>
    <?php endfor; ?>

    <script>
        // Auto-imprimir si se accede directamente
        if (window.location.search.includes('autoprint=1')) {
            window.onload = function() {
                setTimeout(function() {
                    window.print();
                    setTimeout(function() {
                        window.close();
                    }, 1000);
                }, 500);
            };
        }
    </script>
</body>
</html> 