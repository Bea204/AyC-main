<?php
require_once '../connection.php';
require_once './helpers.php';

echo "=== INICIALIZACIÓN DE ACCIONES DE COTIZACIÓN ===\n\n";

// Verificar si las acciones ya existen
if (verificarAccionesCotizacion($mysqli)) {
    echo "✅ Las acciones de cotización ya están inicializadas.\n";
    
    // Mostrar acciones existentes
    $acciones = $mysqli->query("SELECT accion_id, nombre_accion FROM cotizaciones_acciones ORDER BY accion_id ASC");
    echo "\nAcciones existentes:\n";
    while ($accion = $acciones->fetch_assoc()) {
        echo "- ID: {$accion['accion_id']} | Nombre: {$accion['nombre_accion']}\n";
    }
} else {
    echo "🔄 Inicializando acciones de cotización...\n";
    
    if (inicializarAccionesCotizacion($mysqli)) {
        echo "✅ Acciones de cotización inicializadas correctamente.\n";
        
        // Mostrar acciones creadas
        $acciones = $mysqli->query("SELECT accion_id, nombre_accion FROM cotizaciones_acciones ORDER BY accion_id ASC");
        echo "\nAcciones creadas:\n";
        while ($accion = $acciones->fetch_assoc()) {
            echo "- ID: {$accion['accion_id']} | Nombre: {$accion['nombre_accion']}\n";
        }
    } else {
        echo "❌ Error al inicializar las acciones de cotización.\n";
    }
}

echo "\n=== VERIFICACIÓN DE ESTADOS DE COTIZACIÓN ===\n\n";

// Verificar estados de cotización
$estados = $mysqli->query("SELECT est_cot_id, nombre_estado FROM est_cotizacion ORDER BY est_cot_id ASC");
if ($estados && $estados->num_rows > 0) {
    echo "✅ Estados de cotización encontrados:\n";
    while ($estado = $estados->fetch_assoc()) {
        echo "- ID: {$estado['est_cot_id']} | Nombre: {$estado['nombre_estado']}\n";
    }
} else {
    echo "❌ No se encontraron estados de cotización.\n";
}

echo "\n=== VERIFICACIÓN DE TABLAS DE HISTORIAL ===\n\n";

// Verificar tabla de historial
$historial_count = $mysqli->query("SELECT COUNT(*) as total FROM cotizaciones_historial")->fetch_assoc();
echo "📊 Total de registros en historial: {$historial_count['total']}\n";

// Verificar cotizaciones sin historial
$cotizaciones_sin_historial = $mysqli->query("
    SELECT COUNT(*) as total 
    FROM cotizaciones c 
    LEFT JOIN cotizaciones_historial h ON c.cotizacion_id = h.cotizacion_id 
    WHERE h.cotizacion_id IS NULL
")->fetch_assoc();

if ($cotizaciones_sin_historial['total'] > 0) {
    echo "⚠️  Cotizaciones sin historial: {$cotizaciones_sin_historial['total']}\n";
    echo "💡 Recomendación: Crear historial para cotizaciones existentes\n";
} else {
    echo "✅ Todas las cotizaciones tienen historial.\n";
}

$mysqli->close();
echo "\n🎉 Verificación completada.\n";
?> 