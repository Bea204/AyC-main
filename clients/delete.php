<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id > 0) {
    $stmt = $mysqli->prepare("DELETE FROM clientes WHERE cliente_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}
header('Location: index.php?success=3');
exit; 