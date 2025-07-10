<?php
require_once '../connection.php';

echo "=== VERIFICACIÓN DE ESTADOS DE COTIZACIÓN ===\n\n";

// Verificar estados existentes
$estados = $mysqli->query("SELECT est_cot_id, nombre_estado FROM est_cotizacion ORDER BY est_cot_id ASC");
if ($estados && $estados->num_rows > 0) {
    echo "Estados de cotización encontrados:\n";
    while ($estado = $estados->fetch_assoc()) {
        echo "- ID: {$estado['est_cot_id']} | Nombre: {$estado['nombre_estado']}\n";
    }
} else {
    echo "No se encontraron estados de cotización.\n";
}

echo "\n=== VERIFICACIÓN DE ACCIONES DE COTIZACIÓN ===\n\n";

// Verificar acciones existentes
$acciones = $mysqli->query("SELECT accion_id, nombre_accion FROM cotizaciones_acciones ORDER BY accion_id ASC");
if ($acciones && $acciones->num_rows > 0) {
    echo "Acciones de cotización encontradas:\n";
    while ($accion = $acciones->fetch_assoc()) {
        echo "- ID: {$accion['accion_id']} | Nombre: {$accion['nombre_accion']}\n";
    }
} else {
    echo "No se encontraron acciones de cotización.\n";
}

echo "\n=== VERIFICACIÓN DE TIPOS DE MOVIMIENTO ===\n\n";

// Verificar tipos de movimiento
$movement_types = $mysqli->query("SELECT movement_type_id, name FROM movement_types ORDER BY movement_type_id ASC");
if ($movement_types && $movement_types->num_rows > 0) {
    echo "Tipos de movimiento encontrados:\n";
    while ($type = $movement_types->fetch_assoc()) {
        echo "- ID: {$type['movement_type_id']} | Nombre: {$type['name']}\n";
    }
} else {
    echo "No se encontraron tipos de movimiento.\n";
}

$mysqli->close();
echo "\nVerificación completada.\n";
?> 