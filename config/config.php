<?php
// Configuración centralizada del sistema de inventarios

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'inventory_management_system2');

// Configuración de la aplicación
define('APP_NAME', 'Gestor de Inventarios');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/inventory-management-system-main');

// Configuración de archivos
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('PRODUCT_IMAGES_DIR', UPLOAD_DIR . 'products/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// Configuración de seguridad
define('SESSION_TIMEOUT', 3600); // 1 hora
define('PASSWORD_MIN_LENGTH', 8);

// Configuración de paginación
define('ITEMS_PER_PAGE', 20);

// Configuración de notificaciones
define('LOW_STOCK_THRESHOLD', 5);
define('CRITICAL_STOCK_THRESHOLD', 2);

// Configuración de logs
define('LOG_DIR', __DIR__ . '/../logs/');
define('ERROR_LOG', LOG_DIR . 'error.log');
define('ACCESS_LOG', LOG_DIR . 'access.log');

// Crear directorios si no existen
if (!file_exists(LOG_DIR)) {
    mkdir(LOG_DIR, 0755, true);
}

if (!file_exists(PRODUCT_IMAGES_DIR)) {
    mkdir(PRODUCT_IMAGES_DIR, 0755, true);
}

// Función para obtener la URL base
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['SCRIPT_NAME']);
    return $protocol . '://' . $host . $path;
}

// Función para validar imagen
function isValidImage($file) {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return false;
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    return in_array($mimeType, ALLOWED_IMAGE_TYPES);
}

// Función para generar nombre único de archivo
function generateUniqueFileName($originalName, $extension) {
    $timestamp = time();
    $random = bin2hex(random_bytes(8));
    return $timestamp . '_' . $random . '.' . $extension;
}

// Función para limpiar entrada de usuario
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Función para validar SKU
function isValidSKU($sku) {
    return preg_match('/^[A-Z0-9\-_]+$/', $sku);
}

// Función para validar código de barras
function isValidBarcode($barcode) {
    return preg_match('/^[0-9]{8,13}$/', $barcode);
}
?> 