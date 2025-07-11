<?php
/**
 * Script para agregar datos de prueba para los reportes
 * Este script inserta datos realistas que permiten probar todas las funcionalidades del módulo de reportes
 */

require_once '../connection.php';

echo "🔧 Agregando datos de prueba para los reportes...\n\n";

try {
    // 1. Verificar y crear estados de cotización si no existen
    echo "📋 Verificando estados de cotización...\n";
    $estados = $mysqli->query("SELECT est_cot_id, nombre_estado FROM est_cotizacion ORDER BY est_cot_id ASC");
    if ($estados && $estados->num_rows == 0) {
        $estados_basicos = [
            ['nombre_estado' => 'Borrador'],
            ['nombre_estado' => 'Enviada'],
            ['nombre_estado' => 'Aprobada'],
            ['nombre_estado' => 'Rechazada'],
            ['nombre_estado' => 'Convertida']
        ];
        
        $stmt = $mysqli->prepare("INSERT INTO est_cotizacion (nombre_estado) VALUES (?)");
        foreach ($estados_basicos as $estado) {
            $stmt->bind_param('s', $estado['nombre_estado']);
            $stmt->execute();
        }
        echo "✅ Estados de cotización creados\n\n";
    } else {
        echo "✅ Estados de cotización ya existen\n\n";
    }

    // 2. Verificar y crear acciones de cotización si no existen
    echo "📋 Verificando acciones de cotización...\n";
    $acciones = $mysqli->query("SELECT accion_id, nombre_accion FROM cotizaciones_acciones ORDER BY accion_id ASC");
    if ($acciones && $acciones->num_rows == 0) {
        $acciones_basicas = [
            ['nombre_accion' => 'Creada'],
            ['nombre_accion' => 'Enviada'],
            ['nombre_accion' => 'Aprobada'],
            ['nombre_accion' => 'Rechazada'],
            ['nombre_accion' => 'Convertida'],
            ['nombre_accion' => 'Modificada']
        ];
        
        $stmt = $mysqli->prepare("INSERT INTO cotizaciones_acciones (nombre_accion) VALUES (?)");
        foreach ($acciones_basicas as $accion) {
            $stmt->bind_param('s', $accion['nombre_accion']);
            $stmt->execute();
        }
        echo "✅ Acciones de cotización creadas\n\n";
    } else {
        echo "✅ Acciones de cotización ya existen\n\n";
    }

    // 3. Verificar y crear tipos de movimiento si no existen
    echo "📋 Verificando tipos de movimiento...\n";
    $movement_types = $mysqli->query("SELECT movement_type_id, name FROM movement_types ORDER BY movement_type_id ASC");
    if ($movement_types && $movement_types->num_rows < 2) {
        $tipos_movimiento = [
            ['name' => 'Entrada'],
            ['name' => 'Salida'],
            ['name' => 'Ajuste'],
            ['name' => 'Transferencia']
        ];
        
        $stmt = $mysqli->prepare("INSERT IGNORE INTO movement_types (name) VALUES (?)");
        foreach ($tipos_movimiento as $tipo) {
            $stmt->bind_param('s', $tipo['name']);
            $stmt->execute();
        }
        echo "✅ Tipos de movimiento creados\n\n";
    } else {
        echo "✅ Tipos de movimiento ya existen\n\n";
    }

    // 4. Agregar categorías adicionales
    echo "🏷️ Agregando categorías adicionales...\n";
    $categorias_adicionales = [
        'Cámaras de Seguridad',
        'Sistemas de Alarma',
        'Herramientas',
        'Accesorios',
        'Equipos de Red',
        'Cables y Conectores'
    ];
    
    $stmt = $mysqli->prepare("INSERT IGNORE INTO categories (name) VALUES (?)");
    foreach ($categorias_adicionales as $categoria) {
        $stmt->bind_param('s', $categoria);
        $stmt->execute();
    }
    echo "✅ Categorías adicionales agregadas\n\n";

    // 5. Agregar proveedores adicionales
    echo "🏢 Agregando proveedores adicionales...\n";
    $proveedores_adicionales = [
        ['name' => 'Syscom', 'contact_name' => 'Juan Pérez', 'phone' => '555-0101', 'email' => 'ventas@syscom.com'],
        ['name' => 'PCH', 'contact_name' => 'María García', 'phone' => '555-0102', 'email' => 'ventas@pch.com'],
        ['name' => 'Dahua', 'contact_name' => 'Carlos López', 'phone' => '555-0103', 'email' => 'ventas@dahua.com'],
        ['name' => 'TP-Link', 'contact_name' => 'Ana Martínez', 'phone' => '555-0104', 'email' => 'ventas@tplink.com'],
        ['name' => 'Ferretería Central', 'contact_name' => 'Roberto Díaz', 'phone' => '555-0105', 'email' => 'ventas@ferreteria.com']
    ];
    
    $stmt = $mysqli->prepare("INSERT IGNORE INTO suppliers (name, contact_name, phone, email) VALUES (?, ?, ?, ?)");
    foreach ($proveedores_adicionales as $proveedor) {
        $stmt->bind_param('ssss', $proveedor['name'], $proveedor['contact_name'], $proveedor['phone'], $proveedor['email']);
        $stmt->execute();
    }
    echo "✅ Proveedores adicionales agregados\n\n";

    // 6. Agregar clientes
    echo "👥 Agregando clientes...\n";
    $clientes = [
        ['nombre' => 'Empresa ABC', 'telefono' => '555-1001', 'ubicacion' => 'Centro Histórico', 'email' => 'contacto@abc.com'],
        ['nombre' => 'Comercio XYZ', 'telefono' => '555-1002', 'ubicacion' => 'Zona Norte', 'email' => 'ventas@xyz.com'],
        ['nombre' => 'Oficinas 123', 'telefono' => '555-1003', 'ubicacion' => 'Zona Sur', 'email' => 'admin@123.com'],
        ['nombre' => 'Residencial Los Pinos', 'telefono' => '555-1004', 'ubicacion' => 'Colonia Los Pinos', 'email' => 'admin@pinos.com'],
        ['nombre' => 'Centro Comercial Plaza', 'telefono' => '555-1005', 'ubicacion' => 'Plaza Central', 'email' => 'seguridad@plaza.com']
    ];
    
    $stmt = $mysqli->prepare("INSERT IGNORE INTO clientes (nombre, telefono, ubicacion, email) VALUES (?, ?, ?, ?)");
    foreach ($clientes as $cliente) {
        $stmt->bind_param('ssss', $cliente['nombre'], $cliente['telefono'], $cliente['ubicacion'], $cliente['email']);
        $stmt->execute();
    }
    echo "✅ Clientes agregados\n\n";

    // 7. Agregar productos adicionales
    echo "📦 Agregando productos adicionales...\n";
    
    // Obtener IDs de categorías y proveedores
    $categorias = $mysqli->query("SELECT category_id, name FROM categories")->fetch_all(MYSQLI_ASSOC);
    $proveedores = $mysqli->query("SELECT supplier_id, name FROM suppliers")->fetch_all(MYSQLI_ASSOC);
    
    $productos = [
        ['product_name' => 'Cable UTP Cat6 (Bobina 305m)', 'sku' => 'CABLE-UTP-CAT6-305M', 'price' => 762.50, 'cost_price' => 600.00, 'quantity' => 5, 'category' => 'Cables y Conectores', 'supplier' => 'Syscom', 'tipo_gestion' => 'bobina'],
        ['product_name' => 'Conectores RJ45 (Caja 100pzs)', 'sku' => 'CONN-RJ45-100PZ', 'price' => 120.00, 'cost_price' => 80.00, 'quantity' => 8, 'category' => 'Cables y Conectores', 'supplier' => 'Syscom', 'tipo_gestion' => 'normal'],
        ['product_name' => 'Cámara IP Dome 2MP', 'sku' => 'CAM-IP-DOME-2MP', 'price' => 850.00, 'cost_price' => 650.00, 'quantity' => 12, 'category' => 'Cámaras de Seguridad', 'supplier' => 'Dahua', 'tipo_gestion' => 'normal'],
        ['product_name' => 'Panel de Alarma 8 Zonas', 'sku' => 'PANEL-ALARM-8Z', 'price' => 450.00, 'cost_price' => 320.00, 'quantity' => 6, 'category' => 'Sistemas de Alarma', 'supplier' => 'PCH', 'tipo_gestion' => 'normal'],
        ['product_name' => 'Sensor de Movimiento PIR', 'sku' => 'SENSOR-PIR-MOV', 'price' => 75.00, 'cost_price' => 45.00, 'quantity' => 25, 'category' => 'Sistemas de Alarma', 'supplier' => 'PCH', 'tipo_gestion' => 'normal'],
        ['product_name' => 'Switch 8 Puertos PoE', 'sku' => 'SWITCH-8POE', 'price' => 1200.00, 'cost_price' => 900.00, 'quantity' => 3, 'category' => 'Equipos de Red', 'supplier' => 'TP-Link', 'tipo_gestion' => 'normal'],
        ['product_name' => 'Cinta Aislante (Rollos)', 'sku' => 'CINTA-AISL-ROLLO', 'price' => 25.00, 'cost_price' => 15.00, 'quantity' => 20, 'category' => 'Accesorios', 'supplier' => 'Ferretería Central', 'tipo_gestion' => 'normal'],
        ['product_name' => 'Terminales de Conexión (Caja 50pzs)', 'sku' => 'TERM-CONN-50PZ', 'price' => 40.00, 'cost_price' => 25.00, 'quantity' => 12, 'category' => 'Cables y Conectores', 'supplier' => 'Syscom', 'tipo_gestion' => 'normal'],
        ['product_name' => 'Cable Coaxial RG6 (Bobina 100m)', 'sku' => 'CABLE-COAX-RG6-100M', 'price' => 850.00, 'cost_price' => 680.00, 'quantity' => 3, 'category' => 'Cables y Conectores', 'supplier' => 'Syscom', 'tipo_gestion' => 'bobina'],
        ['product_name' => 'Destornillador Phillips', 'sku' => 'DEST-PHILLIPS', 'price' => 35.00, 'cost_price' => 20.00, 'quantity' => 15, 'category' => 'Herramientas', 'supplier' => 'Ferretería Central', 'tipo_gestion' => 'normal']
    ];
    
    $stmt = $mysqli->prepare("INSERT IGNORE INTO products (product_name, sku, price, cost_price, quantity, min_stock, max_stock, supplier_id, category_id, description, tipo_gestion, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
    
    foreach ($productos as $producto) {
        // Buscar category_id y supplier_id
        $category_id = null;
        $supplier_id = null;
        
        foreach ($categorias as $cat) {
            if ($cat['name'] === $producto['category']) {
                $category_id = $cat['category_id'];
                break;
            }
        }
        
        foreach ($proveedores as $prov) {
            if ($prov['name'] === $producto['supplier']) {
                $supplier_id = $prov['supplier_id'];
                break;
            }
        }
        
        $min_stock = $producto['tipo_gestion'] === 'bobina' ? 1 : 5;
        $max_stock = $producto['tipo_gestion'] === 'bobina' ? 10 : 50;
        $description = "Producto de prueba para reportes - " . $producto['product_name'];
        
        $stmt->bind_param('ssddiiiiiss', 
            $producto['product_name'], 
            $producto['sku'], 
            $producto['price'], 
            $producto['cost_price'], 
            $producto['quantity'], 
            $min_stock, 
            $max_stock, 
            $supplier_id, 
            $category_id, 
            $description, 
            $producto['tipo_gestion']
        );
        $stmt->execute();
    }
    echo "✅ Productos adicionales agregados\n\n";

    // 8. Agregar movimientos de prueba
    echo "🔄 Agregando movimientos de prueba...\n";
    
    // Obtener productos y tipos de movimiento
    $productos_db = $mysqli->query("SELECT product_id FROM products WHERE is_active = 1")->fetch_all(MYSQLI_ASSOC);
    $tipos_movimiento = $mysqli->query("SELECT movement_type_id FROM movement_types")->fetch_all(MYSQLI_ASSOC);
    
    if (!empty($productos_db) && !empty($tipos_movimiento)) {
        // Generar movimientos para los últimos 30 días
        for ($i = 0; $i < 50; $i++) {
            $product_id = $productos_db[array_rand($productos_db)]['product_id'];
            $movement_type_id = $tipos_movimiento[array_rand($tipos_movimiento)]['movement_type_id'];
            $quantity = rand(1, 20);
            if ($movement_type_id == 2) { // Salida
                $quantity = -$quantity;
            }
            
            $fecha = date('Y-m-d H:i:s', strtotime('-' . rand(0, 30) . ' days'));
            $reference = $movement_type_id == 1 ? 'Compra' : 'Venta';
            $notes = $movement_type_id == 1 ? 'Entrada de inventario' : 'Salida de inventario';
            
            $stmt = $mysqli->prepare("INSERT INTO movements (product_id, movement_type_id, quantity, reference, notes, movement_date) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('iiisss', $product_id, $movement_type_id, $quantity, $reference, $notes, $fecha);
            $stmt->execute();
        }
        echo "✅ Movimientos de prueba agregados\n\n";
    }

    // 9. Agregar cotizaciones de prueba
    echo "📄 Agregando cotizaciones de prueba...\n";
    
    $clientes_db = $mysqli->query("SELECT cliente_id FROM clientes")->fetch_all(MYSQLI_ASSOC);
    $estados_cot = $mysqli->query("SELECT est_cot_id FROM est_cotizacion")->fetch_all(MYSQLI_ASSOC);
    
    if (!empty($clientes_db) && !empty($estados_cot)) {
        // Generar cotizaciones para los últimos 60 días
        for ($i = 0; $i < 15; $i++) {
            $cliente_id = $clientes_db[array_rand($clientes_db)]['cliente_id'];
            $estado_id = $estados_cot[array_rand($estados_cot)]['est_cot_id'];
            $fecha = date('Y-m-d', strtotime('-' . rand(0, 60) . ' days'));
            $total = rand(500, 5000);
            $numero_cotizacion = 'COT-' . date('Y') . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT);
            
            $stmt = $mysqli->prepare("INSERT INTO cotizaciones (numero_cotizacion, cliente_id, fecha_cotizacion, total, estado_id, user_id) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->bind_param('sisdi', $numero_cotizacion, $cliente_id, $fecha, $total, $estado_id);
            $stmt->execute();
        }
        echo "✅ Cotizaciones de prueba agregadas\n\n";
    }

    // 10. Agregar bobinas de prueba
    echo "🔄 Agregando bobinas de prueba...\n";
    
    $productos_bobina = $mysqli->query("SELECT product_id FROM products WHERE tipo_gestion = 'bobina' AND is_active = 1")->fetch_all(MYSQLI_ASSOC);
    
    if (!empty($productos_bobina)) {
        foreach ($productos_bobina as $producto) {
            $metros = rand(50, 300);
            $identificador = 'BOB-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
            
            $stmt = $mysqli->prepare("INSERT INTO bobinas (product_id, metros_actuales, identificador, is_active) VALUES (?, ?, ?, 1)");
            $stmt->bind_param('ids', $producto['product_id'], $metros, $identificador);
            $stmt->execute();
        }
        echo "✅ Bobinas de prueba agregadas\n\n";
    }

    // 11. Agregar insumos de prueba
    echo "📦 Agregando insumos de prueba...\n";
    
    $productos_insumo = $mysqli->query("SELECT product_id, product_name FROM products WHERE tipo_gestion = 'normal' AND is_active = 1 LIMIT 5")->fetch_all(MYSQLI_ASSOC);
    
    if (!empty($productos_insumo)) {
        foreach ($productos_insumo as $producto) {
            $cantidad = rand(10, 100);
            $minimo = rand(5, 20);
            $precio = rand(10, 100);
            
            $stmt = $mysqli->prepare("INSERT INTO insumos (product_id, nombre, cantidad, minimo, precio_unitario, ubicacion, estado, is_active) VALUES (?, ?, ?, ?, ?, 'Almacén Principal', 'disponible', 1)");
            $stmt->bind_param('isddd', $producto['product_id'], $producto['product_name'], $cantidad, $minimo, $precio);
            $stmt->execute();
        }
        echo "✅ Insumos de prueba agregados\n\n";
    }

    echo "🎉 ¡Datos de prueba agregados exitosamente!\n\n";
    echo "📊 Ahora puedes probar los reportes con datos realistas:\n";
    echo "   - Reporte principal: /reports/index.php\n";
    echo "   - Reporte semanal: /reports/semanal.php\n";
    echo "   - Reporte mensual: /reports/mensual.php\n";
    echo "   - Reporte personalizado: /reports/personalizado.php\n\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

$mysqli->close();
?> 