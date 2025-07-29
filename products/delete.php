<?php
    require_once '../auth/middleware.php';
    require_once '../connection.php';

    // Ensure a valid product ID is passed
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        header("Location: list.php");
        exit;
    }

    $product_id = intval($_GET['id']);

    // Verificar cantidad antes de eliminar
    $stmt = $mysqli->prepare("SELECT quantity FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $stmt->bind_result($quantity);
    $stmt->fetch();
    $stmt->close();

    if ($quantity > 0) {
        // No permitir eliminar si hay stock
        header("Location: list.php?error=stock");
        exit;
    }

    // Delete the product solo si cantidad es 0
    $stmt = $mysqli->prepare("DELETE FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);

    if ($stmt->execute()) {
        $stmt->close();
        header("Location: list.php?msg=deleted");
        exit;
    } else {
        $stmt->close();
        header("Location: list.php?error=1");
        exit;
    }
?>