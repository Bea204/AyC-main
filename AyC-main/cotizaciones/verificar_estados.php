<?php
require_once '../connection.php';

// Verificar si existen estados de cotización, si no, crearlos
$estados = $mysqli->query("SELECT est_cot_id, nombre_estado FROM est_cotizacion ORDER BY est_cot_id ASC");
if ($estados && $estados->num_rows == 0) {
    // Crear estados básicos si no existen
    $estados_basicos = [
        ['nombre_estado' => 'Borrador'],
        ['nombre_estado' => 'Enviada'],
        ['nombre_estado' => 'Aprobada'],
        ['nombre_estado' => 'Rechazada'],
        ['nombre_estado' => 'Convertida']
    ];
    
    foreach ($estados_basicos as $estado) {
        $stmt = $mysqli->prepare("INSERT INTO est_cotizacion (nombre_estado) VALUES (?)");
        $stmt->bind_param('s', $estado['nombre_estado']);
        $stmt->execute();
        $stmt->close();
    }
    
    echo "Estados de cotización creados exitosamente.\n";
} else {
    echo "Estados de cotización ya existen.\n";
}

// Verificar si existen acciones de cotización, si no, crearlas
$acciones = $mysqli->query("SELECT accion_id, nombre_accion FROM cotizaciones_acciones ORDER BY accion_id ASC");
if ($acciones && $acciones->num_rows == 0) {
    // Crear acciones básicas si no existen
    $acciones_basicas = [
        ['nombre_accion' => 'Creada'],
        ['nombre_accion' => 'Enviada'],
        ['nombre_accion' => 'Aprobada'],
        ['nombre_accion' => 'Rechazada'],
        ['nombre_accion' => 'Convertida'],
        ['nombre_accion' => 'Modificada']
    ];
    
    foreach ($acciones_basicas as $accion) {
        $stmt = $mysqli->prepare("INSERT INTO cotizaciones_acciones (nombre_accion) VALUES (?)");
        $stmt->bind_param('s', $accion['nombre_accion']);
        $stmt->execute();
        $stmt->close();
    }
    
    echo "Acciones de cotización creadas exitosamente.\n";
} else {
    echo "Acciones de cotización ya existen.\n";
}

$mysqli->close();
echo "Verificación completada.\n";
?> 