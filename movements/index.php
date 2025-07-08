<?php
    require_once '../auth/middleware.php';
    require_once '../connection.php';

    // Obtener movimientos con nombre de producto, tipo de movimiento e información de bobina
    $query = "
        SELECT 
            m.*, 
            p.product_name,
            p.tipo_gestion,
            mt.name AS movement_type_nombre,
            b.identificador AS bobina_identificador,
            b.metros_actuales AS bobina_metros_actuales
        FROM 
            movements m
        JOIN 
            products p ON m.product_id = p.product_id
        LEFT JOIN
            movement_types mt ON m.movement_type_id = mt.movement_type_id
        LEFT JOIN
            bobinas b ON m.bobina_id = b.bobina_id
        ORDER BY 
            m.movement_date DESC
    ";
    $result = $mysqli->query($query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Movimientos | Gestor de inventarios Alarmas y Cámaras de seguridad del sureste</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .main-content {
            position: relative;
        }
        .main-content h2 {
            margin-bottom: 10px;
        }
        .add-btn {
            display: inline-block;
            margin-bottom: 18px;
            width: auto;
            padding: 8px 18px;
            font-size: 0.98rem;
            border-radius: 6px;
            background: #121866;
            color: #fff;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(18,24,102,0.08);
            transition: background 0.18s;
        }
        .add-btn:hover {
            background: #232a7c;
        }
        .table {
            font-size: 0.97rem;
            box-shadow: 0 2px 12px rgba(18,24,102,0.07);
            border-radius: 10px;
            overflow: hidden;
        }
        .table th {
            background-color: #121866 !important;
            color: #fff;
            font-weight: 700;
            border: none;
        }
        .table td {
            vertical-align: middle;
        }
        .badge {
            font-size: 0.85rem;
        }
        .bobina-info {
            background: #e3f2fd;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            color: #1565c0;
            margin-top: 2px;
            display: inline-block;
        }
        .quantity-bobina {
            color: #7b1fa2;
            font-weight: 600;
        }
        @media (max-width: 900px) {
            .main-content h2 { font-size: 1.1rem; }
            .add-btn { display: block; width: 100%; }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <h2>Movimientos de inventario</h2>
        <a href="new.php"><button class="btn btn-primary"><i class="bi bi-plus-circle"></i> Registrar movimiento</button></a>
        <?php if ($result && $result->num_rows > 0): ?>
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Producto</th>
                        <th scope="col">Tipo</th>
                        <th scope="col">Cantidad</th>
                        <th scope="col">Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['movement_id'] ?></td>
                            <td>
                                <div>
                                    <?= htmlspecialchars($row['product_name']) ?>
                                    <?php if ($row['tipo_gestion'] === 'bobina' && $row['bobina_identificador']): ?>
                                        <div class="bobina-info">
                                            <i class="bi bi-receipt"></i>
                                            <?= htmlspecialchars($row['bobina_identificador']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-info text-dark"><?= htmlspecialchars($row['movement_type_nombre']) ?></span>
                            </td>
                            <td>
                                <strong class="<?= $row['tipo_gestion'] === 'bobina' ? 'quantity-bobina' : '' ?>">
                                    <?= $row['quantity'] ?>
                                    <?php if ($row['tipo_gestion'] === 'bobina'): ?>
                                        m
                                    <?php endif; ?>
                                </strong>
                                <?php if ($row['tipo_gestion'] === 'bobina' && $row['bobina_metros_actuales'] !== null): ?>
                                    <br><small class="text-muted">Restante: <?= $row['bobina_metros_actuales'] ?>m</small>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($row['movement_date'])) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No hay movimientos registrados.</p>
        <?php endif; ?>
    </main>
    <script src="../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Resalta el menú activo
        document.querySelector('.sidebar-movimientos').classList.add('active');
    </script>
</body>
</html>