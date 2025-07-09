<?php
require_once 'connection.php';

echo "🔧 Configurando datos iniciales para el sistema de inventarios...\n\n";

try {
    // 1. Insertar roles
    echo "📋 Insertando roles...\n";
    $roles = [
        ['role_name' => 'admin', 'description' => 'Administrador del sistema', 'permissions' => '{"products":{"read":true,"write":true,"delete":true},"movements":{"read":true,"write":true,"delete":true},"cotizaciones":{"read":true,"write":true,"delete":true},"insumos":{"read":true,"write":true,"delete":true},"equipos":{"read":true,"write":true,"delete":true},"usuarios":{"read":true,"write":true,"delete":true},"reportes":{"read":true,"write":true},"configuracion":{"read":true,"write":true}}'],
        ['role_name' => 'user', 'description' => 'Usuario estándar', 'permissions' => '{"products":{"read":true,"write":true,"delete":false},"movements":{"read":true,"write":true,"delete":false},"cotizaciones":{"read":true,"write":true,"delete":false},"insumos":{"read":true,"write":true,"delete":false},"equipos":{"read":true,"write":false,"delete":false},"usuarios":{"read":false,"write":false,"delete":false},"reportes":{"read":true,"write":false},"configuracion":{"read":false,"write":false}}'],
        ['role_name' => 'viewer', 'description' => 'Solo visualización', 'permissions' => '{"products":{"read":true,"write":false,"delete":false},"movements":{"read":true,"write":false,"delete":false},"cotizaciones":{"read":true,"write":false,"delete":false},"insumos":{"read":true,"write":false,"delete":false},"equipos":{"read":true,"write":false,"delete":false},"usuarios":{"read":false,"write":false,"delete":false},"reportes":{"read":true,"write":false},"configuracion":{"read":false,"write":false}}']
    ];
    
    $stmt = $mysqli->prepare("INSERT INTO roles (role_name, description, permissions) VALUES (?, ?, ?)");
    foreach ($roles as $role) {
        $stmt->bind_param('sss', $role['role_name'], $role['description'], $role['permissions']);
        $stmt->execute();
    }
    echo "✅ Roles insertados correctamente\n\n";

    // 2. Insertar usuarios
    echo "👤 Insertando usuarios...\n";
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $user_password = password_hash('user123', PASSWORD_DEFAULT);
    
    $users = [
        ['username' => 'admin', 'email' => 'admin@empresa.com', 'password' => $admin_password, 'first_name' => 'Administrador', 'last_name' => 'Sistema', 'role_id' => 1],
        ['username' => 'user', 'email' => 'user@empresa.com', 'password' => $user_password, 'first_name' => 'Usuario', 'last_name' => 'Estándar', 'role_id' => 2]
    ];
    
    $stmt = $mysqli->prepare("INSERT INTO users (username, email, password, first_name, last_name, role_id) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($users as $user) {
        $stmt->bind_param('sssssi', $user['username'], $user['email'], $user['password'], $user['first_name'], $user['last_name'], $user['role_id']);
        $stmt->execute();
    }
    echo "✅ Usuarios insertados correctamente\n\n";

    // 3. Insertar categorías
    echo "🏷️ Insertando categorías...\n";
    $categories = [
        ['name' => 'Cables y Conectores', 'description' => 'Cables, conectores y accesorios de red'],
        ['name' => 'Cámaras de Seguridad', 'description' => 'Cámaras IP, domos y accesorios'],
        ['name' => 'Sistemas de Alarma', 'description' => 'Sensores, paneles y accesorios de alarma'],
        ['name' => 'Herramientas', 'description' => 'Herramientas para instalación y mantenimiento'],
        ['name' => 'Accesorios', 'description' => 'Accesorios varios para instalaciones'],
        ['name' => 'Equipos de Red', 'description' => 'Switches, routers y equipos de red']
    ];
    
    $stmt = $mysqli->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
    foreach ($categories as $category) {
        $stmt->bind_param('ss', $category['name'], $category['description']);
        $stmt->execute();
    }
    echo "✅ Categorías insertadas correctamente\n\n";

    // 4. Insertar proveedores
    echo "🏢 Insertando proveedores...\n";
    $suppliers = [
        ['name' => 'Syscom', 'email' => 'ventas@syscom.com', 'phone' => '555-0101', 'address' => 'Ciudad de México'],
        ['name' => 'Ferretería Central', 'email' => 'info@ferreteriacentral.com', 'phone' => '555-0202', 'address' => 'Mérida, Yucatán'],
        ['name' => 'Electrónica del Sureste', 'email' => 'contacto@electronicasureste.com', 'phone' => '555-0303', 'address' => 'Mérida, Yucatán'],
        ['name' => 'Distribuidora de Seguridad', 'email' => 'ventas@distribuidoraseguridad.com', 'phone' => '555-0404', 'address' => 'Cancún, Quintana Roo']
    ];
    
    $stmt = $mysqli->prepare("INSERT INTO suppliers (name, email, phone, address) VALUES (?, ?, ?, ?)");
    foreach ($suppliers as $supplier) {
        $stmt->bind_param('ssss', $supplier['name'], $supplier['email'], $supplier['phone'], $supplier['address']);
        $stmt->execute();
    }
    echo "✅ Proveedores insertados correctamente\n\n";

    // 5. Insertar tipos de movimiento
    echo "📊 Insertando tipos de movimiento...\n";
    $movement_types = [
        ['name' => 'Entrada', 'description' => 'Entrada de productos al inventario', 'type' => 'entrada'],
        ['name' => 'Salida', 'description' => 'Salida de productos del inventario', 'type' => 'salida'],
        ['name' => 'Ajuste', 'description' => 'Ajuste de inventario', 'type' => 'ajuste'],
        ['name' => 'Venta', 'description' => 'Venta de productos', 'type' => 'salida'],
        ['name' => 'Compra', 'description' => 'Compra de productos', 'type' => 'entrada']
    ];
    
    $stmt = $mysqli->prepare("INSERT INTO movement_types (name, description, type) VALUES (?, ?, ?)");
    foreach ($movement_types as $movement_type) {
        $stmt->bind_param('sss', $movement_type['name'], $movement_type['description'], $movement_type['type']);
        $stmt->execute();
    }
    echo "✅ Tipos de movimiento insertados correctamente\n\n";

    // 6. Insertar productos de ejemplo
    echo "📦 Insertando productos de ejemplo...\n";
    $products = [
        [
            'product_name' => 'Cable UTP Cat6 (Bobina 305m)',
            'sku' => 'CABLE-UTP-CAT6-305M',
            'barcode' => '1234567890123',
            'description' => 'Cable UTP Cat6 de alta calidad, bobina de 305 metros',
            'price' => 762.50,
            'quantity' => 5,
            'category_id' => 1,
            'supplier_id' => 1,
            'tipo_gestion' => 'bobina',
            'unit_measure' => 'metros'
        ],
        [
            'product_name' => 'Conectores RJ45 (Caja 100pzs)',
            'sku' => 'CONN-RJ45-100PZ',
            'barcode' => '1234567890124',
            'description' => 'Conectores RJ45 macho, caja de 100 piezas',
            'price' => 120.00,
            'quantity' => 8,
            'category_id' => 1,
            'supplier_id' => 1,
            'tipo_gestion' => 'normal',
            'unit_measure' => 'piezas'
        ],
        [
            'product_name' => 'Cinta aislante (Rollos)',
            'sku' => 'CINTA-AISL-ROLLO',
            'barcode' => '1234567890125',
            'description' => 'Cinta aislante eléctrica, rollos de 20 metros',
            'price' => 25.00,
            'quantity' => 20,
            'category_id' => 5,
            'supplier_id' => 2,
            'tipo_gestion' => 'normal',
            'unit_measure' => 'rollos'
        ],
        [
            'product_name' => 'Cámara IP Dahua 2MP',
            'sku' => 'CAM-IP-DAHUA-2MP',
            'barcode' => '1234567890126',
            'description' => 'Cámara IP Dahua 2MP con visión nocturna',
            'price' => 850.00,
            'quantity' => 3,
            'category_id' => 2,
            'supplier_id' => 1,
            'tipo_gestion' => 'normal',
            'unit_measure' => 'piezas'
        ],
        [
            'product_name' => 'Sensor de Movimiento',
            'sku' => 'SENS-MOV-PIR',
            'barcode' => '1234567890127',
            'description' => 'Sensor de movimiento PIR para sistemas de alarma',
            'price' => 45.00,
            'quantity' => 15,
            'category_id' => 3,
            'supplier_id' => 3,
            'tipo_gestion' => 'normal',
            'unit_measure' => 'piezas'
        ]
    ];
    
    $stmt = $mysqli->prepare("INSERT INTO products (product_name, sku, barcode, description, price, quantity, category_id, supplier_id, tipo_gestion, unit_measure) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($products as $product) {
        $stmt->bind_param('ssssddiiss', 
            $product['product_name'], $product['sku'], $product['barcode'], 
            $product['description'], $product['price'], $product['quantity'], 
            $product['category_id'], $product['supplier_id'], 
            $product['tipo_gestion'], $product['unit_measure']
        );
        $stmt->execute();
    }
    echo "✅ Productos insertados correctamente\n\n";

    // 7. Insertar bobinas para productos tipo bobina
    echo "🔄 Insertando bobinas...\n";
    $bobinas = [
        ['product_id' => 1, 'metros_iniciales' => 305.0, 'metros_actuales' => 250.0, 'identificador' => 'Bobina #1'],
        ['product_id' => 1, 'metros_iniciales' => 305.0, 'metros_actuales' => 180.0, 'identificador' => 'Bobina #2'],
        ['product_id' => 1, 'metros_iniciales' => 305.0, 'metros_actuales' => 305.0, 'identificador' => 'Bobina #3']
    ];
    
    $stmt = $mysqli->prepare("INSERT INTO bobinas (product_id, metros_iniciales, metros_actuales, identificador) VALUES (?, ?, ?, ?)");
    foreach ($bobinas as $bobina) {
        $stmt->bind_param('idds', $bobina['product_id'], $bobina['metros_iniciales'], $bobina['metros_actuales'], $bobina['identificador']);
        $stmt->execute();
    }
    echo "✅ Bobinas insertadas correctamente\n\n";

    // 8. Insertar configuración del sistema
    echo "⚙️ Insertando configuración del sistema...\n";
    $config = [
        ['config_key' => 'company_name', 'config_value' => 'ALARMAS & CAMARAS DEL SURESTE', 'description' => 'Nombre de la empresa'],
        ['config_key' => 'company_phone', 'config_value' => '999 134 3979', 'description' => 'Teléfono de la empresa'],
        ['config_key' => 'company_address', 'config_value' => 'Mérida, Yucatán', 'description' => 'Dirección de la empresa'],
        ['config_key' => 'low_stock_threshold', 'config_value' => '5', 'description' => 'Umbral de stock bajo'],
        ['config_key' => 'critical_stock_threshold', 'config_value' => '2', 'description' => 'Umbral de stock crítico']
    ];
    
    $stmt = $mysqli->prepare("INSERT INTO system_config (config_key, config_value, description) VALUES (?, ?, ?)");
    foreach ($config as $conf) {
        $stmt->bind_param('sss', $conf['config_key'], $conf['config_value'], $conf['description']);
        $stmt->execute();
    }
    echo "✅ Configuración insertada correctamente\n\n";

    echo "🎉 ¡Configuración completada exitosamente!\n\n";
    echo "📋 Resumen de datos insertados:\n";
    echo "- 3 roles (admin, user, viewer)\n";
    echo "- 2 usuarios (admin/admin123, user/user123)\n";
    echo "- 6 categorías\n";
    echo "- 4 proveedores\n";
    echo "- 5 tipos de movimiento\n";
    echo "- 5 productos de ejemplo\n";
    echo "- 3 bobinas para productos tipo bobina\n";
    echo "- 5 configuraciones del sistema\n\n";
    
    echo "🔑 Credenciales de acceso:\n";
    echo "Usuario: admin | Contraseña: admin123\n";
    echo "Usuario: user | Contraseña: user123\n\n";
    
    echo "✅ El sistema está listo para usar.\n";

} catch (Exception $e) {
    echo "❌ Error durante la configuración: " . $e->getMessage() . "\n";
}

$mysqli->close();
?> 