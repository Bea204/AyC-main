<?php
// Datos simulados para el sistema de inventario unificado
// En el futuro esto vendrá de la base de datos

// Productos del catálogo (maestro)
$productos_catalogo = [
    [
        'id' => 1,
        'nombre' => 'Cable UTP Cat6 (Bobina 305m)',
        'sku' => 'CABLE-UTP-CAT6-305M',
        'categoria' => 'Cables y Conectores',
        'precio' => 762.50,
        'stock' => 5,
        'proveedor' => 'Syscom',
        'tipo_gestion' => 'bobina',
        'descripcion' => 'Cable UTP Cat6 de alta calidad, bobina de 305 metros'
    ],
    [
        'id' => 2,
        'nombre' => 'Conectores RJ45 (Caja 100pzs)',
        'sku' => 'CONN-RJ45-100PZ',
        'categoria' => 'Cables y Conectores',
        'precio' => 120.00,
        'stock' => 8,
        'proveedor' => 'Syscom',
        'tipo_gestion' => 'normal',
        'descripcion' => 'Conectores RJ45 macho, caja de 100 piezas'
    ],
    [
        'id' => 3,
        'nombre' => 'Cinta aislante (Rollos)',
        'sku' => 'CINTA-AISL-ROLLO',
        'categoria' => 'Accesorios',
        'precio' => 25.00,
        'stock' => 20,
        'proveedor' => 'Ferretería Central',
        'tipo_gestion' => 'normal',
        'descripcion' => 'Cinta aislante eléctrica, rollos de 20 metros'
    ],
    [
        'id' => 4,
        'nombre' => 'Terminales de conexión (Caja 50pzs)',
        'sku' => 'TERM-CONN-50PZ',
        'categoria' => 'Cables y Conectores',
        'precio' => 40.00,
        'stock' => 12,
        'proveedor' => 'Syscom',
        'tipo_gestion' => 'normal',
        'descripcion' => 'Terminales de conexión para cables, caja de 50 piezas'
    ],
    [
        'id' => 5,
        'nombre' => 'Cable coaxial RG6 (Bobina 100m)',
        'sku' => 'CABLE-COAX-RG6-100M',
        'categoria' => 'Cables y Conectores',
        'precio' => 850.00,
        'stock' => 3,
        'proveedor' => 'Syscom',
        'tipo_gestion' => 'bobina',
        'descripcion' => 'Cable coaxial RG6, bobina de 100 metros'
    ],
    [
        'id' => 6,
        'nombre' => 'Tornillos autorroscantes (Caja 500pzs)',
        'sku' => 'TORN-AUTOR-500PZ',
        'categoria' => 'Herramientas',
        'precio' => 75.00,
        'stock' => 15,
        'proveedor' => 'Ferretería Central',
        'tipo_gestion' => 'normal',
        'descripcion' => 'Tornillos autorroscantes, caja de 500 piezas'
    ]
];

// Insumos derivados de productos
$insumos = [
    [
        'id' => 1,
        'producto_origen_id' => 1,
        'nombre' => 'Cable UTP Cat 5e',
        'categoria' => 'Cables',
        'unidad' => 'metros',
        'cantidad' => 500,
        'minimo' => 100,
        'proveedor' => 'Syscom',
        'precio_unitario' => 2.50,
        'ubicacion' => 'Tienda Principal',
        'ultima_actualizacion' => '2024-01-15',
        'estado' => 'disponible',
        'producto_origen' => 'Cable UTP Cat6 (Bobina 305m)',
        'consumo_semanal' => 45
    ],
    [
        'id' => 2,
        'producto_origen_id' => 2,
        'nombre' => 'Conectores RJ45',
        'categoria' => 'Conectores',
        'unidad' => 'piezas',
        'cantidad' => 120,
        'minimo' => 50,
        'proveedor' => 'Syscom',
        'precio_unitario' => 1.20,
        'ubicacion' => 'Tienda Principal',
        'ultima_actualizacion' => '2024-01-14',
        'estado' => 'disponible',
        'producto_origen' => 'Conectores RJ45 (Caja 100pzs)',
        'consumo_semanal' => 18
    ],
    [
        'id' => 3,
        'producto_origen_id' => 3,
        'nombre' => 'Cinta aislante',
        'categoria' => 'Accesorios',
        'unidad' => 'rollos',
        'cantidad' => 15,
        'minimo' => 10,
        'proveedor' => 'Ferretería Central',
        'precio_unitario' => 25.00,
        'ubicacion' => 'Tienda Principal',
        'ultima_actualizacion' => '2024-01-13',
        'estado' => 'disponible',
        'producto_origen' => 'Cinta aislante (Rollos)',
        'consumo_semanal' => 3
    ],
    [
        'id' => 4,
        'producto_origen_id' => 4,
        'nombre' => 'Terminales de conexión',
        'categoria' => 'Conectores',
        'unidad' => 'piezas',
        'cantidad' => 8,
        'minimo' => 20,
        'proveedor' => 'Syscom',
        'precio_unitario' => 0.80,
        'ubicacion' => 'Tienda Principal',
        'ultima_actualizacion' => '2024-01-12',
        'estado' => 'bajo_stock',
        'producto_origen' => 'Terminales (Caja 50pzs)',
        'consumo_semanal' => 12
    ],
    [
        'id' => 5,
        'producto_origen_id' => 5,
        'nombre' => 'Cable coaxial RG6',
        'categoria' => 'Cables',
        'unidad' => 'metros',
        'cantidad' => 0,
        'minimo' => 50,
        'proveedor' => 'Syscom',
        'precio_unitario' => 8.50,
        'ubicacion' => 'Tienda Principal',
        'ultima_actualizacion' => '2024-01-10',
        'estado' => 'agotado',
        'producto_origen' => 'Cable coaxial RG6 (Bobina 100m)',
        'consumo_semanal' => 25
    ],
    [
        'id' => 6,
        'producto_origen_id' => 6,
        'nombre' => 'Tornillos autorroscantes',
        'categoria' => 'Herramientas',
        'unidad' => 'piezas',
        'cantidad' => 250,
        'minimo' => 100,
        'proveedor' => 'Ferretería Central',
        'precio_unitario' => 0.15,
        'ubicacion' => 'Tienda Principal',
        'ultima_actualizacion' => '2024-01-11',
        'estado' => 'disponible',
        'producto_origen' => 'Tornillos (Caja 500pzs)',
        'consumo_semanal' => 35
    ]
];

// Datos para el dashboard
$datos_dashboard = [
    'min_stock' => [
        'nombre' => 'Cable coaxial RG6',
        'stock' => 0,
        'categoria' => 'Cables'
    ],
    'max_stock' => [
        'nombre' => 'Cable UTP Cat 5e',
        'stock' => 500,
        'categoria' => 'Cables'
    ],
    'last_product' => [
        'product_name' => 'Cable UTP Cat6 (Bobina 305m)',
        'created_at' => '2024-01-15 14:30:00'
    ],
    'movimientos_hoy' => 12,
    'most_moved' => [
        'product_name' => 'Cable UTP Cat 5e',
        'total_movs' => 8
    ],
    'top_category' => [
        'category_name' => 'Cables y Conectores',
        'total' => 15
    ],
    'top_supplier' => [
        'supplier' => 'Syscom',
        'total' => 8
    ]
];

// Datos para gráficas
$datos_graficas = [
    'labels_stock' => ['Cable UTP', 'Conectores RJ45', 'Cinta aislante', 'Terminales', 'Cable coaxial', 'Tornillos'],
    'data_stock' => [500, 120, 15, 8, 0, 250],
    'labels_movimientos' => ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
    'data_entradas' => [5, 3, 8, 2, 6, 1, 0],
    'data_salidas' => [12, 15, 8, 20, 14, 5, 2]
];

// Funciones para obtener datos
function getProductosInventario() {
    global $productos_catalogo;
    return $productos_catalogo;
}

function getInsumos() {
    global $insumos;
    return $insumos;
}

function getEstadisticasInventario() {
    global $productos_catalogo;
    
    $total_productos = count($productos_catalogo);
    $disponibles = count(array_filter($productos_catalogo, function($p) { return $p['stock'] > 10; }));
    $bajo_stock = count(array_filter($productos_catalogo, function($p) { return $p['stock'] > 0 && $p['stock'] <= 10; }));
    $agotados = count(array_filter($productos_catalogo, function($p) { return $p['stock'] == 0; }));
    
    // Calcular valor total del inventario
    $valor_total = array_sum(array_map(function($p) { 
        return $p['stock'] * $p['precio']; 
    }, $productos_catalogo));
    
    return [
        'total_productos' => $total_productos,
        'disponibles' => $disponibles,
        'bajo_stock' => $bajo_stock,
        'agotados' => $agotados,
        'valor_total' => $valor_total
    ];
}

function getDatosDashboard() {
    global $datos_dashboard;
    return $datos_dashboard;
}

function getDatosGraficas() {
    global $datos_graficas;
    return $datos_graficas;
}

// Función para crear insumo desde producto
function crearInsumoDesdeProducto($producto_id, $cantidad_extraer, $stock_minimo) {
    global $productos_catalogo, $insumos;
    
    // Buscar el producto origen
    $producto = null;
    foreach ($productos_catalogo as $p) {
        if ($p['id'] == $producto_id) {
            $producto = $p;
            break;
        }
    }
    
    if (!$producto) {
        return false;
    }
    
    // Verificar que hay suficiente stock
    if ($producto['stock'] < $cantidad_extraer) {
        return false;
    }
    
    // Crear el insumo
    $nuevo_insumo = [
        'id' => count($insumos) + 1,
        'producto_origen_id' => $producto_id,
        'nombre' => $producto['nombre'] . ' (Insumo)',
        'categoria' => $producto['categoria'],
        'unidad' => $producto['tipo_gestion'] === 'bobina' ? 'metros' : 'piezas',
        'cantidad' => $cantidad_extraer,
        'minimo' => $stock_minimo,
        'proveedor' => $producto['proveedor'],
        'precio_unitario' => $producto['precio'] / ($producto['tipo_gestion'] === 'bobina' ? 305 : 100),
        'ubicacion' => 'Tienda Principal',
        'ultima_actualizacion' => date('Y-m-d'),
        'estado' => $cantidad_extraer > $stock_minimo ? 'disponible' : 'bajo_stock',
        'producto_origen' => $producto['nombre'],
        'consumo_semanal' => 0
    ];
    
    // Agregar a la lista de insumos
    $insumos[] = $nuevo_insumo;
    
    // Reducir stock del producto origen
    foreach ($productos_catalogo as &$p) {
        if ($p['id'] == $producto_id) {
            $p['stock'] -= $cantidad_extraer;
            break;
        }
    }
    
    return $nuevo_insumo;
}

// Función para obtener reporte semanal de insumo
function getReporteSemanalInsumo($insumo_id) {
    // Datos simulados del reporte semanal
    return [
        'consumo_promedio' => 45,
        'unidad' => 'metros',
        'proyectos_semana' => [
            ['nombre' => 'Instalación Cámara Casa #123', 'consumo' => 15],
            ['nombre' => 'Mantenimiento Sistema #456', 'consumo' => 20],
            ['nombre' => 'Reparación Cable #789', 'consumo' => 10]
        ],
        'historial_semanas' => [
            ['semana' => 'Semana 1', 'consumo' => 50, 'proyectos' => 3, 'costo' => 125.00],
            ['semana' => 'Semana 2', 'consumo' => 40, 'proyectos' => 2, 'costo' => 100.00],
            ['semana' => 'Semana 3', 'consumo' => 45, 'proyectos' => 4, 'costo' => 112.50],
            ['semana' => 'Semana 4', 'consumo' => 35, 'proyectos' => 2, 'costo' => 87.50]
        ]
    ];
}
?> 