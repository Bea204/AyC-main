<?php
require_once '../auth/middleware.php';
require_once 'functions.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Obtener el tipo de exportación
$type = $_GET['type'] ?? '';
$format = $_GET['format'] ?? '';

if (empty($type) || empty($format)) {
    die('Parámetros inválidos');
}

// Función para generar CSV
function exportToCSV($data, $filename) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // BOM para UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Escribir encabezados
    if (!empty($data)) {
        fputcsv($output, array_keys($data[0]));
        
        // Escribir datos
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    }
    
    fclose($output);
    exit;
}

// Función para generar Excel (HTML table)
function exportToExcel($data, $filename, $headers) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    
    echo '<table border="1">';
    
    // Encabezados
    echo '<tr>';
    foreach ($headers as $header) {
        echo '<th>' . htmlspecialchars($header) . '</th>';
    }
    echo '</tr>';
    
    // Datos
    foreach ($data as $row) {
        echo '<tr>';
        foreach ($row as $cell) {
            echo '<td>' . htmlspecialchars($cell) . '</td>';
        }
        echo '</tr>';
    }
    
    echo '</table>';
    exit;
}

// Función para generar PDF (versión HTML para imprimir)
function exportToPDF($data, $filename, $headers, $title) {
    header('Content-Type: text/html; charset=utf-8');
    
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>' . $title . '</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .header { text-align: center; margin-bottom: 20px; }
            .title { font-size: 18px; font-weight: bold; margin-bottom: 10px; }
            .date { font-size: 12px; color: #666; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #121866; color: white; font-weight: bold; }
            tr:nth-child(even) { background-color: #f2f2f2; }
            @media print {
                body { margin: 0; }
                .no-print { display: none; }
            }
        </style>
    </head>
    <body>
        <div class="header">
            <div class="title">' . $title . '</div>
            <div class="date">Generado el: ' . date('d/m/Y H:i:s') . '</div>
        </div>
        
        <table>
            <thead>
                <tr>';
    
    foreach ($headers as $header) {
        echo '<th>' . htmlspecialchars($header) . '</th>';
    }
    
    echo '</tr>
            </thead>
            <tbody>';
    
    foreach ($data as $row) {
        echo '<tr>';
        foreach ($row as $cell) {
            echo '<td>' . htmlspecialchars($cell) . '</td>';
        }
        echo '</tr>';
    }
    
    echo '</tbody>
        </table>
        
        <div class="no-print" style="margin-top: 20px; text-align: center;">
            <button onclick="window.print()">Imprimir PDF</button>
            <button onclick="window.close()">Cerrar</button>
        </div>
    </body>
    </html>';
    
    exit;
}

// Procesar exportación según el tipo
switch ($type) {
    case 'movements':
        $movements = getMonthlyMovements($mysqli, 6);
        $data = [];
        
        if ($movements && $movements->num_rows > 0) {
            while ($row = $movements->fetch_assoc()) {
                $date = new DateTime($row['month'] . '-01');
                $data[] = [
                    'Mes' => $date->format('M Y'),
                    'Entradas' => $row['entradas'],
                    'Salidas' => $row['salidas'],
                    'Total Entradas' => $row['total_entradas'],
                    'Total Salidas' => $row['total_salidas']
                ];
            }
        }
        
        $headers = ['Mes', 'Entradas', 'Salidas', 'Total Entradas', 'Total Salidas'];
        $title = 'Movimientos Mensuales';
        break;
        
    case 'categories':
        $categories = getTopCategories($mysqli, 10);
        $data = [];
        
        if ($categories && $categories->num_rows > 0) {
            while ($row = $categories->fetch_assoc()) {
                $data[] = [
                    'Categoría' => $row['category_name'],
                    'Productos' => $row['product_count'],
                    'Valor Total' => formatCurrency($row['total_value']),
                    'Precio Promedio' => formatCurrency($row['avg_price'])
                ];
            }
        }
        
        $headers = ['Categoría', 'Productos', 'Valor Total', 'Precio Promedio'];
        $title = 'Top Categorías por Valor';
        break;
        
    case 'suppliers':
        $suppliers = getTopSuppliers($mysqli, 10);
        $data = [];
        
        if ($suppliers && $suppliers->num_rows > 0) {
            while ($row = $suppliers->fetch_assoc()) {
                $data[] = [
                    'Proveedor' => $row['supplier_name'],
                    'Productos' => $row['product_count'],
                    'Valor Total' => formatCurrency($row['total_value']),
                    'Precio Promedio' => formatCurrency($row['avg_price'])
                ];
            }
        }
        
        $headers = ['Proveedor', 'Productos', 'Valor Total', 'Precio Promedio'];
        $title = 'Top Proveedores';
        break;
        
    case 'products':
        $products = getTopProducts($mysqli, 20);
        $data = [];
        
        if ($products && $products->num_rows > 0) {
            while ($row = $products->fetch_assoc()) {
                $data[] = [
                    'Producto' => $row['product_name'],
                    'SKU' => $row['sku'],
                    'Categoría' => $row['category_name'] ?? 'Sin categoría',
                    'Movimientos' => $row['total_movements'],
                    'Frecuencia' => $row['movement_count'],
                    'Valor Ventas' => formatCurrency($row['total_sales_value'])
                ];
            }
        }
        
        $headers = ['Producto', 'SKU', 'Categoría', 'Movimientos', 'Frecuencia', 'Valor Ventas'];
        $title = 'Productos Más Vendidos';
        break;
        
    case 'low-stock':
        $products = getLowStockProducts($mysqli, 20);
        $data = [];
        
        if ($products && $products->num_rows > 0) {
            while ($row = $products->fetch_assoc()) {
                $data[] = [
                    'Producto' => $row['product_name'],
                    'SKU' => $row['sku'],
                    'Stock Actual' => $row['quantity'],
                    'Stock Mínimo' => $row['min_stock'],
                    'Categoría' => $row['category_name'] ?? 'Sin categoría',
                    'Proveedor' => $row['supplier_name'] ?? 'Sin proveedor',
                    'Precio' => formatCurrency($row['price'])
                ];
            }
        }
        
        $headers = ['Producto', 'SKU', 'Stock Actual', 'Stock Mínimo', 'Categoría', 'Proveedor', 'Precio'];
        $title = 'Productos con Stock Bajo';
        break;
        
    default:
        die('Tipo de reporte no válido');
}

// Generar exportación según el formato
$filename = $title . '_' . date('Y-m-d_H-i-s');

switch ($format) {
    case 'pdf':
        exportToPDF($data, $filename, $headers, $title);
        break;
    case 'excel':
        exportToExcel($data, $filename, $headers);
        break;
    case 'csv':
        exportToCSV($data, $filename);
        break;
    default:
        die('Formato de exportación no válido');
}
?> 