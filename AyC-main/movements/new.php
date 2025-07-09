<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

$success = $error = '';

// Obtener productos para el select
$products = $mysqli->query("SELECT product_id, product_name, tipo_gestion FROM products ORDER BY product_name");

// Obtener tipos de movimiento para el select
$movement_types = $mysqli->query("SELECT movement_type_id, name FROM movement_types ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $movement_type_id = isset($_POST['movement_type_id']) ? intval($_POST['movement_type_id']) : 0;
    $quantity = isset($_POST['quantity']) ? floatval($_POST['quantity']) : 0;
    $bobina_id = isset($_POST['bobina_id']) ? intval($_POST['bobina_id']) : null;

    if ($product_id && $movement_type_id && $quantity > 0) {
        // Verificar si es producto tipo bobina
        $stmt = $mysqli->prepare("SELECT tipo_gestion FROM products WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        $stmt->close();

        if ($product['tipo_gestion'] === 'bobina') {
            // Para productos tipo bobina, verificar que se seleccionó una bobina
            if (!$bobina_id) {
                $error = "Para productos tipo bobina, debes seleccionar una bobina específica.";
            } else {
                // Verificar metros disponibles en la bobina
                $stmt = $mysqli->prepare("SELECT metros_actuales FROM bobinas WHERE bobina_id = ? AND product_id = ?");
                $stmt->bind_param("ii", $bobina_id, $product_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $bobina = $result->fetch_assoc();
                $stmt->close();

                if (!$bobina) {
                    $error = "Bobina no encontrada.";
                } elseif ($bobina['metros_actuales'] < $quantity) {
                    $error = "No hay suficientes metros disponibles en la bobina. Disponible: " . $bobina['metros_actuales'] . "m";
                } else {
                    // Registrar movimiento y actualizar bobina
                    $mysqli->begin_transaction();
                    try {
                        // Insertar movimiento
                        $stmt = $mysqli->prepare("INSERT INTO movements (product_id, movement_type_id, quantity, movement_date, bobina_id) VALUES (?, ?, ?, NOW(), ?)");
                        $stmt->bind_param("iiid", $product_id, $movement_type_id, $quantity, $bobina_id);
                        $stmt->execute();
                        $stmt->close();

                        // Actualizar metros en la bobina
                        $stmt = $mysqli->prepare("UPDATE bobinas SET metros_actuales = metros_actuales - ? WHERE bobina_id = ?");
                        $stmt->bind_param("di", $quantity, $bobina_id);
                        $stmt->execute();
                        $stmt->close();

                        // Actualizar stock del producto (suma de todas las bobinas)
                        $stmt = $mysqli->prepare("UPDATE products SET quantity = (SELECT COALESCE(SUM(metros_actuales), 0) FROM bobinas WHERE product_id = ?) WHERE product_id = ?");
                        $stmt->bind_param("ii", $product_id, $product_id);
                        $stmt->execute();
                        $stmt->close();

                        $mysqli->commit();
                        // Después de registrar el movimiento (tanto para bobina como para producto normal), llamar al procedimiento para actualizar el stock:
                        // Para productos tipo bobina, después de $mysqli->commit();
                        $mysqli->query("CALL sp_update_product_stock($product_id)");
                        $success = "Movimiento de bobina registrado correctamente.";
                    } catch (Exception $e) {
                        $mysqli->rollback();
                        $error = "Error al registrar movimiento: " . $e->getMessage();
                    }
                }
            }
        } else {
            // Para productos normales, registrar movimiento normal
            $stmt = $mysqli->prepare("INSERT INTO movements (product_id, movement_type_id, quantity, movement_date) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("iid", $product_id, $movement_type_id, $quantity);
            $stmt->execute();
            if ($stmt->error) {
                $error = "Error al registrar movimiento: " . $stmt->error;
            } else {
                // Después de registrar el movimiento y antes de mostrar el mensaje de éxito:
                $mysqli->query("CALL sp_update_product_stock($product_id)");
                $success = "Movimiento registrado correctamente.";
            }
            $stmt->close();
        }
    } else {
        $error = "Por favor, completa todos los campos correctamente.";
    }
    // Recargar selects tras el POST
    $products = $mysqli->query("SELECT product_id, product_name, tipo_gestion FROM products ORDER BY product_name");
    $movement_types = $mysqli->query("SELECT movement_type_id, name FROM movement_types ORDER BY name");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar movimiento | Gestor de inventarios</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .main-content { max-width: 440px; margin: 40px auto 0 auto; }
        .main-content h2 { margin-bottom: 22px; }
        .form-group { margin-bottom: 14px; }
        .form-group input, .form-group select { margin-top: 4px; }
        .form-actions { display: flex; gap: 10px; margin-top: 18px; }
        .form-actions button { flex: 1; }
        .bobina-info { 
            background: #e3f2fd; 
            padding: 10px; 
            border-radius: 8px; 
            margin-top: 10px; 
            display: none; 
        }
        @media (max-width: 900px) { .main-content { max-width: 98vw; padding: 0 2vw; } }
    </style>
</head>
<body>
<?php include '../includes/sidebar.php'; ?>
<main class="main-content">
    <h2>Registrar movimiento de inventario</h2>
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> <?= $success ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i> <?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <form action="" method="POST" autocomplete="off">
        <div class="mb-3">
            <label for="product_id" class="form-label">Producto:</label>
            <select name="product_id" id="product_id" class="form-select" required>
                <option value="">-- Selecciona un producto --</option>
                <?php while ($row = $products->fetch_assoc()): ?>
                    <option value="<?= $row['product_id'] ?>" 
                            data-tipo="<?= $row['tipo_gestion'] ?>"
                            <?= (isset($_POST['product_id']) && $_POST['product_id'] == $row['product_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($row['product_name']) ?>
                        <?php if ($row['tipo_gestion'] === 'bobina'): ?>
                            (Bobina)
                        <?php endif; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <!-- Sección de bobinas (solo para productos tipo bobina) -->
        <div id="bobinaSection" style="display:none;">
            <div class="mb-3">
                <label for="bobina_id" class="form-label">Bobina:</label>
                <select name="bobina_id" id="bobina_id" class="form-select">
                    <option value="">-- Selecciona una bobina --</option>
                </select>
            </div>
            <div id="bobinaInfo" class="bobina-info">
                <i class="bi bi-info-circle"></i>
                <span id="bobinaInfoText"></span>
            </div>
        </div>

        <div class="mb-3">
            <label for="movement_type_id" class="form-label">Tipo de movimiento:</label>
            <select name="movement_type_id" id="movement_type_id" class="form-select" required>
                <option value="">-- Selecciona un tipo --</option>
                <?php while ($mt = $movement_types->fetch_assoc()): ?>
                    <option value="<?= $mt['movement_type_id'] ?>" <?= (isset($_POST['movement_type_id']) && $_POST['movement_type_id'] == $mt['movement_type_id']) ? 'selected' : '' ?>><?= htmlspecialchars($mt['name']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="quantity" class="form-label">Cantidad:</label>
            <input type="number" name="quantity" id="quantity" class="form-control" min="0.01" step="0.01" required value="<?= isset($_POST['quantity']) ? htmlspecialchars($_POST['quantity']) : '' ?>">
            <small class="text-muted" id="quantityHelp">Ingresa la cantidad</small>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-fill">
                <i class="bi bi-plus-circle"></i> Registrar movimiento
            </button>
            <a href="index.php" class="btn btn-secondary flex-fill">
                <i class="bi bi-arrow-left"></i> Volver al listado
            </a>
        </div>
    </form>
</main>
<script src="../assets/js/script.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Manejar cambio de producto
    document.getElementById('product_id').addEventListener('change', function() {
        const productId = this.value;
        const selectedOption = this.options[this.selectedIndex];
        const tipoGestion = selectedOption.dataset.tipo;
        const bobinaSection = document.getElementById('bobinaSection');
        const quantityInput = document.getElementById('quantity');
        const quantityHelp = document.getElementById('quantityHelp');
        
        if (tipoGestion === 'bobina') {
            bobinaSection.style.display = 'block';
            quantityInput.step = '0.01';
            quantityInput.min = '0.01';
            quantityHelp.textContent = 'Ingresa los metros a consumir';
            
            // Cargar bobinas disponibles
            fetch(`../bobinas/bobinas_por_producto.php?product_id=${productId}`)
                .then(response => response.json())
                .then(bobinas => {
                    const bobinaSelect = document.getElementById('bobina_id');
                    bobinaSelect.innerHTML = '<option value="">-- Selecciona una bobina --</option>';
                    
                    bobinas.forEach(bobina => {
                        const option = document.createElement('option');
                        option.value = bobina.bobina_id;
                        option.textContent = `${bobina.identificador || 'Bobina #' + bobina.bobina_id} - ${bobina.metros_actuales}m disponibles`;
                        option.dataset.metros = bobina.metros_actuales;
                        bobinaSelect.appendChild(option);
                    });
                });
        } else {
            bobinaSection.style.display = 'none';
            quantityInput.step = '1';
            quantityInput.min = '1';
            quantityHelp.textContent = 'Ingresa la cantidad';
        }
    });

    // Manejar cambio de bobina
    document.getElementById('bobina_id').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const bobinaInfo = document.getElementById('bobinaInfo');
        const bobinaInfoText = document.getElementById('bobinaInfoText');
        
        if (this.value) {
            const metros = selectedOption.dataset.metros;
            bobinaInfoText.textContent = `Metros disponibles: ${metros}m`;
            bobinaInfo.style.display = 'block';
        } else {
            bobinaInfo.style.display = 'none';
        }
    });

    // Validar formulario
    document.querySelector('form').addEventListener('submit', function(e) {
        const productId = document.getElementById('product_id').value;
        const selectedOption = document.getElementById('product_id').options[document.getElementById('product_id').selectedIndex];
        const tipoGestion = selectedOption.dataset.tipo;
        const bobinaId = document.getElementById('bobina_id').value;
        
        if (tipoGestion === 'bobina' && !bobinaId) {
            e.preventDefault();
            alert('Para productos tipo bobina, debes seleccionar una bobina específica.');
        }
    });
</script>
</body>
</html>