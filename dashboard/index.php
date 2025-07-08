<?php
    require_once '../auth/middleware.php';
    require_once '../connection.php';

    // Obtener nombre de usuario
    $username = $_SESSION['username'] ?? 'Usuario';
    $user_role = $_SESSION['user_role'] ?? 'Usuario';

    // Obtener estadísticas reales de la base de datos
    $stats_query = "
        SELECT 
            COUNT(*) as total_products,
            SUM(CASE WHEN quantity > 10 THEN 1 ELSE 0 END) as disponibles,
            SUM(CASE WHEN quantity > 0 AND quantity <= 10 THEN 1 ELSE 0 END) as bajo_stock,
            SUM(CASE WHEN quantity = 0 THEN 1 ELSE 0 END) as agotados,
            SUM(quantity * price) as valor_total
        FROM products
    ";
    $stats_result = $mysqli->query($stats_query);
    $stats = $stats_result->fetch_assoc();

    // Obtener producto con menor stock
    $min_stock_query = "
        SELECT product_name, quantity, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        WHERE quantity > 0
        ORDER BY quantity ASC
        LIMIT 1
    ";
    $min_stock_result = $mysqli->query($min_stock_query);
    $min_stock = $min_stock_result->fetch_assoc();

    // Obtener producto con mayor stock
    $max_stock_query = "
        SELECT product_name, quantity, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        ORDER BY quantity DESC
        LIMIT 1
    ";
    $max_stock_result = $mysqli->query($max_stock_query);
    $max_stock = $max_stock_result->fetch_assoc();

    // Obtener último producto agregado
    $last_product_query = "
        SELECT product_name, created_at
        FROM products
        ORDER BY created_at DESC
        LIMIT 1
    ";
    $last_product_result = $mysqli->query($last_product_query);
    $last_product = $last_product_result->fetch_assoc();

    // Obtener movimientos de hoy
    $movimientos_hoy_query = "
        SELECT COUNT(*) as total
        FROM movements
        WHERE DATE(movement_date) = CURDATE()
    ";
    $movimientos_hoy_result = $mysqli->query($movimientos_hoy_query);
    $movimientos_hoy = $movimientos_hoy_result->fetch_assoc()['total'];

    // Obtener producto más movido
    $most_moved_query = "
        SELECT p.product_name, COUNT(*) as total_movs
        FROM movements m
        JOIN products p ON m.product_id = p.product_id
        GROUP BY m.product_id
        ORDER BY total_movs DESC
        LIMIT 1
    ";
    $most_moved_result = $mysqli->query($most_moved_query);
    $most_moved = $most_moved_result->fetch_assoc();

    // Obtener categoría más popular
    $top_category_query = "
        SELECT c.name as category_name, COUNT(*) as total
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        GROUP BY p.category_id
        ORDER BY total DESC
        LIMIT 1
    ";
    $top_category_result = $mysqli->query($top_category_query);
    $top_category = $top_category_result->fetch_assoc();

    // Obtener proveedor más popular
    $top_supplier_query = "
        SELECT s.name as supplier_name, COUNT(*) as total
        FROM products p
        LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
        GROUP BY p.supplier_id
        ORDER BY total DESC
        LIMIT 1
    ";
    $top_supplier_result = $mysqli->query($top_supplier_query);
    $top_supplier = $top_supplier_result->fetch_assoc();

    // Obtener datos para gráficas
    $stock_data_query = "
        SELECT p.product_name, p.quantity
        FROM products p
        ORDER BY p.quantity DESC
        LIMIT 6
    ";
    $stock_data_result = $mysqli->query($stock_data_query);
    $labels_stock = [];
    $data_stock = [];
    while ($row = $stock_data_result->fetch_assoc()) {
        $labels_stock[] = $row['product_name'];
        $data_stock[] = $row['quantity'];
    }

    // Obtener movimientos de la semana
    $movements_week_query = "
        SELECT 
            DATE(movement_date) as fecha,
            SUM(CASE WHEN quantity > 0 THEN 1 ELSE 0 END) as entradas,
            SUM(CASE WHEN quantity < 0 THEN 1 ELSE 0 END) as salidas
        FROM movements
        WHERE movement_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(movement_date)
        ORDER BY fecha
    ";
    $movements_week_result = $mysqli->query($movements_week_query);
    $labels_movs = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
    $data_entradas = array_fill(0, 7, 0);
    $data_salidas = array_fill(0, 7, 0);
    
    $day_index = 0;
    while ($row = $movements_week_result->fetch_assoc()) {
        $data_entradas[$day_index] = $row['entradas'];
        $data_salidas[$day_index] = $row['salidas'];
        $day_index++;
    }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | Gestor de inventarios Alarmas y Cámaras de seguridad del sureste</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f4f6fb;
        }
        .main-content {
            background: #f4f6fb;
            border-radius: 18px;
            box-shadow: 0 4px 32px rgba(18,24,102,0.07);
            margin-top: 18px;
            padding: 0 0 32px 0;
        }
        .dashboard-header {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 36px;
            align-items: center;
        }
        .dashboard-header h2 {
            font-size: 2.2rem;
            color: #121866;
            font-weight: 800;
            margin-bottom: 0;
            letter-spacing: 0.5px;
        }
        .dashboard-header p {
            color: #232a7c;
            font-size: 1.15rem;
            margin: 0;
        }
        .dashboard-cards {
            display: flex;
            flex-wrap: wrap;
            gap: 32px;
            margin: 0 0 18px 0;
            justify-content: center;
        }
        .card {
            flex: 1 1 220px;
            background: #fff;
            padding: 38px 24px 30px 24px;
            border-radius: 14px;
            text-align: center;
            box-shadow: 0 2px 16px rgba(18,24,102,0.10);
            border: 1.5px solid #e3e6f0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-width: 220px;
            min-height: 140px;
            position: relative;
        }
        .card h3 {
            color: #121866;
            font-size: 1.15rem;
            margin-bottom: 12px;
            font-weight: 700;
        }
        .card p {
            font-size: 2.2rem;
            font-weight: 700;
            color: #232a7c;
            margin: 0;
        }
        .card .icon {
            font-size: 2.2rem;
            color: #232a7c;
            margin-bottom: 8px;
        }
        .dashboard-extra {
            display: flex;
            flex-wrap: wrap;
            gap: 24px;
            margin-bottom: 32px;
            justify-content: center;
        }
        .extra-card {
            flex: 1 1 220px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(18,24,102,0.07);
            padding: 18px 18px 14px 18px;
            min-width: 220px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            border-left: 5px solid #121866;
        }
        .extra-card h4 {
            color: #121866;
            font-size: 1.05rem;
            margin-bottom: 6px;
            font-weight: 700;
        }
        .extra-card p {
            font-size: 1.1rem;
            color: #232a7c;
            margin: 0;
        }
        .header-hora {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(18,24,102,0.07);
            padding: 18px 28px;
            margin-bottom: 32px;
        }
        .header-hora .saludo {
            font-size: 1.3rem;
            color: #121866;
            font-weight: 700;
        }
        .header-hora .hora {
            font-size: 1.1rem;
            color: #232a7c;
            font-weight: 500;
            letter-spacing: 1px;
        }
        .header-hora .usuario {
            font-size: 1.1rem;
            color: #121866;
            font-weight: 600;
            margin-left: 18px;
        }
        .dashboard-graficas {
            display: flex;
            flex-wrap: wrap;
            gap: 32px;
            margin-top: 38px;
            justify-content: center;
        }
        .grafica-card {
            flex: 1 1 380px;
            min-width: 320px;
            max-width: 480px;
            background: #fff;
            padding: 28px 18px 18px 18px;
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(18,24,102,0.10);
            margin-bottom: 18px;
            display: flex;
            flex-direction: column;
            align-items: center;
            border: 1.5px solid #e3e6f0;
        }
        .grafica-card h3 {
            color: #121866;
            font-size: 1.13rem;
            margin-bottom: 18px;
            text-align: center;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .grafica-card canvas {
            display: block;
            width: 340px !important;
            height: 340px !important;
            max-width: 100vw;
            max-height: 60vw;
            aspect-ratio: 1/1;
            margin: 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(18,24,102,0.07);
        }
        .grafica-card.barras {
            align-items: stretch;
            overflow-x: auto;
            padding: 18px 0 18px 0;
        }
        .grafica-card.barras canvas {
            width: 100% !important;
            height: 340px !important;
            max-width: 520px;
            min-width: 240px;
            max-height: 400px;
            display: block;
            margin: 0 auto;
        }
        @media (max-width: 900px) {
            .dashboard-graficas { flex-direction: column; gap: 18px; margin-top: 24px; }
            .grafica-card { max-width: 98vw; }
            .grafica-card canvas { width: 90vw !important; height: 90vw !important; max-width: 350px; max-height: 350px; }
            .grafica-card.barras canvas { width: 100% !important; height: 240px !important; max-width: 98vw; max-height: 300px; }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="dashboard-container">
            <div class="header-empresa">
                <div class="header-logo-nombre">
                    <img src="../assets/img/LogoWeb.png" alt="Logo empresa" class="logo-empresa">
                    <div class="nombre-empresa">ALARMAS & CAMARAS DEL SURESTE</div>
                </div>
            </div>
            <div class="header-hora">
                <div class="saludo">
                    ¡Bienvenido, <?= htmlspecialchars($username) ?>!
                </div>
                <div class="hora" id="horaActual"></div>
                <div class="usuario">
                    <i class="bi bi-person-circle"></i> <?= htmlspecialchars($user_role) ?>
                </div>
            </div>
            <!-- INICIO DEL CONTENIDO PRINCIPAL -->
            <div class="dashboard-header">
                <h2><i class="bi bi-speedometer2"></i> Dashboard</h2>
                <p>Panel de control del sistema de gestión de inventarios</p>
            </div>
            <div class="dashboard-cards">
                <div class="card">
                    <div class="icon"><i class="bi bi-box"></i></div>
                    <h3>Total Productos</h3>
                    <p><?= $stats['total_products'] ?? 0 ?></p>
                </div>
                <div class="card">
                    <div class="icon"><i class="bi bi-check-circle"></i></div>
                    <h3>Disponibles</h3>
                    <p><?= $stats['disponibles'] ?? 0 ?></p>
                </div>
                <div class="card">
                    <div class="icon"><i class="bi bi-exclamation-triangle"></i></div>
                    <h3>Bajo Stock</h3>
                    <p><?= $stats['bajo_stock'] ?? 0 ?></p>
                </div>
                <div class="card">
                    <div class="icon"><i class="bi bi-x-circle"></i></div>
                    <h3>Agotados</h3>
                    <p><?= $stats['agotados'] ?? 0 ?></p>
                </div>
            </div>
            <div class="dashboard-extra">
                <div class="extra-card alert-stock">
                    <h4><i class="bi bi-exclamation-triangle"></i> Menor Stock</h4>
                    <p><?= $min_stock ? htmlspecialchars($min_stock['product_name']) : 'N/A' ?> (<?= $min_stock ? $min_stock['quantity'] : 0 ?>)</p>
                </div>
                <div class="extra-card alert-movido">
                    <h4><i class="bi bi-arrow-left-right"></i> Movimientos Hoy</h4>
                    <p><?= $movimientos_hoy ?> movimientos</p>
                </div>
                <div class="extra-card alert-ultimo">
                    <h4><i class="bi bi-clock"></i> Último Producto</h4>
                    <p><?= $last_product ? htmlspecialchars($last_product['product_name']) : 'N/A' ?></p>
                </div>
                <div class="extra-card alert-categoria">
                    <h4><i class="bi bi-tags"></i> Categoría Top</h4>
                    <p><?= $top_category ? htmlspecialchars($top_category['category_name']) : 'N/A' ?> (<?= $top_category ? $top_category['total'] : 0 ?>)</p>
                </div>
                <div class="extra-card alert-proveedor">
                    <h4><i class="bi bi-truck"></i> Proveedor Top</h4>
                    <p><?= $top_supplier ? htmlspecialchars($top_supplier['supplier_name']) : 'N/A' ?> (<?= $top_supplier ? $top_supplier['total'] : 0 ?>)</p>
                </div>
                <div class="extra-card alert-mayor">
                    <h4><i class="bi bi-graph-up"></i> Mayor Stock</h4>
                    <p><?= $max_stock ? htmlspecialchars($max_stock['product_name']) : 'N/A' ?> (<?= $max_stock ? $max_stock['quantity'] : 0 ?>)</p>
                </div>
                </div>
            <div class="dashboard-graficas">
                <div class="grafica-card">
                    <h3><i class="bi bi-pie-chart"></i> Stock por Producto</h3>
                    <canvas id="stockChart"></canvas>
                </div>
                <div class="grafica-card barras">
                    <h3><i class="bi bi-bar-chart"></i> Movimientos Semanales</h3>
                    <canvas id="movementsChart"></canvas>
                </div>
            </div>
            <!-- FIN DEL CONTENIDO PRINCIPAL -->
        </div>
    </main>
    <script src="../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Actualizar hora en tiempo real
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('es-ES', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            document.getElementById('horaActual').textContent = timeString;
        }
        
        updateTime();
        setInterval(updateTime, 1000);

        // Gráfica de stock
        const stockCtx = document.getElementById('stockChart').getContext('2d');
        new Chart(stockCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($labels_stock) ?>,
                datasets: [{
                    data: <?= json_encode($data_stock) ?>,
                    backgroundColor: [
                        '#121866', '#232a7c', '#388e3c', '#1976d2', '#e53935', '#ffc107'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            }
        });

        // Gráfica de movimientos
        const movementsCtx = document.getElementById('movementsChart').getContext('2d');
        new Chart(movementsCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels_movs) ?>,
                datasets: [{
                        label: 'Entradas',
                        data: <?= json_encode($data_entradas) ?>,
                    backgroundColor: '#43a047',
                    borderColor: '#2e7d32',
                    borderWidth: 1
                }, {
                        label: 'Salidas',
                        data: <?= json_encode($data_salidas) ?>,
                        backgroundColor: '#e53935',
                    borderColor: '#b71c1c',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    }
                }
            }
        });
    </script>
</body>
</html>