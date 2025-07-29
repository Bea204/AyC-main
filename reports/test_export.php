<?php
require_once '../auth/middleware.php';
require_once 'functions.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Obtener algunos datos de prueba
$top_products = getTopProducts($mysqli, 5);
$categories = getTopCategories($mysqli, 5);
$suppliers = getTopSuppliers($mysqli, 5);

echo "<h2>Prueba de Datos</h2>";

echo "<h3>Productos más vendidos:</h3>";
if ($top_products && $top_products->num_rows > 0) {
    while ($product = $top_products->fetch_assoc()) {
        echo "<p>- " . $product['product_name'] . " (SKU: " . $product['sku'] . ") - Movimientos: " . $product['total_movements'] . "</p>";
    }
} else {
    echo "<p>No hay productos con movimientos</p>";
}

echo "<h3>Categorías:</h3>";
if ($categories && $categories->num_rows > 0) {
    while ($category = $categories->fetch_assoc()) {
        echo "<p>- " . $category['category_name'] . " - Productos: " . $category['product_count'] . " - Valor: " . formatCurrency($category['total_value']) . "</p>";
    }
} else {
    echo "<p>No hay categorías con productos</p>";
}

echo "<h3>Proveedores:</h3>";
if ($suppliers && $suppliers->num_rows > 0) {
    while ($supplier = $suppliers->fetch_assoc()) {
        echo "<p>- " . $supplier['supplier_name'] . " - Productos: " . $supplier['product_count'] . " - Valor: " . formatCurrency($supplier['total_value']) . "</p>";
    }
} else {
    echo "<p>No hay proveedores con productos</p>";
}

echo "<h3>Enlaces de prueba:</h3>";
echo "<p><a href='export.php?type=products&format=excel' target='_blank'>Exportar Productos a Excel</a></p>";
echo "<p><a href='export.php?type=categories&format=csv' target='_blank'>Exportar Categorías a CSV</a></p>";
echo "<p><a href='export.php?type=suppliers&format=pdf' target='_blank'>Exportar Proveedores a PDF</a></p>";
?> 