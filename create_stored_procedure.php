<?php
require_once 'connection.php';

echo "🔧 Creando procedimiento almacenado para actualización de stock...\n";

// Eliminar el procedimiento si existe
$drop_sp = "DROP PROCEDURE IF EXISTS sp_update_product_stock";
$mysqli->query($drop_sp);

// Crear el procedimiento almacenado
$create_sp = "
CREATE PROCEDURE sp_update_product_stock(IN p_product_id INT)
BEGIN
    DECLARE total_stock DECIMAL(10,2) DEFAULT 0;
    
    -- Calcular stock total basado en movimientos
    SELECT COALESCE(SUM(quantity), 0) INTO total_stock
    FROM movements 
    WHERE product_id = p_product_id;
    
    -- Actualizar el stock del producto
    UPDATE products 
    SET quantity = total_stock 
    WHERE product_id = p_product_id;
    
END
";

if ($mysqli->query($create_sp)) {
    echo "✅ Procedimiento almacenado sp_update_product_stock creado correctamente\n";
    echo "📝 El procedimiento calcula el stock total basado en los movimientos de inventario\n";
} else {
    echo "❌ Error al crear procedimiento almacenado: " . $mysqli->error . "\n";
}

// Crear tablas para plantillas de cotizaciones
$mysqli->query("
CREATE TABLE IF NOT EXISTS plantillas_cotizaciones (
    plantilla_id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT,
    categoria VARCHAR(100),
    tipo_servicio VARCHAR(100),
    cliente_frecuente_id INT,
    user_id INT,
    es_publica BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_frecuente_id) REFERENCES clientes(cliente_id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$mysqli->query("
CREATE TABLE IF NOT EXISTS plantillas_productos (
    plantilla_producto_id INT AUTO_INCREMENT PRIMARY KEY,
    plantilla_id INT NOT NULL,
    product_id INT,
    nombre_producto VARCHAR(255) NOT NULL,
    sku VARCHAR(100),
    cantidad DECIMAL(10,2) NOT NULL DEFAULT 1,
    precio_unitario DECIMAL(10,2) NOT NULL,
    orden INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (plantilla_id) REFERENCES plantillas_cotizaciones(plantilla_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$mysqli->query("
CREATE TABLE IF NOT EXISTS categorias_plantillas (
    categoria_id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    color VARCHAR(7) DEFAULT '#121866',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// Insertar categorías básicas
$categorias_basicas = [
    ['nombre' => 'Instalación de Cámaras', 'descripcion' => 'Plantillas para instalación de sistemas de videovigilancia', 'color' => '#17a2b8'],
    ['nombre' => 'Cableado de Red', 'descripcion' => 'Plantillas para instalación de cableado estructurado', 'color' => '#28a745'],
    ['nombre' => 'Equipos de Red', 'descripcion' => 'Plantillas para switches, routers y equipos de red', 'color' => '#ffc107'],
    ['nombre' => 'Mantenimiento', 'descripcion' => 'Plantillas para servicios de mantenimiento', 'color' => '#6c757d'],
    ['nombre' => 'Sistemas de Alarma', 'descripcion' => 'Plantillas para instalación de alarmas', 'color' => '#dc3545'],
    ['nombre' => 'Otros Servicios', 'descripcion' => 'Plantillas para otros tipos de servicios', 'color' => '#6f42c1']
];

foreach ($categorias_basicas as $cat) {
    $stmt = $mysqli->prepare("INSERT IGNORE INTO categorias_plantillas (nombre, descripcion, color) VALUES (?, ?, ?)");
    $stmt->bind_param('sss', $cat['nombre'], $cat['descripcion'], $cat['color']);
    $stmt->execute();
    $stmt->close();
}

echo "✅ Tablas de plantillas de cotizaciones creadas exitosamente\n";

$mysqli->close();
?> 