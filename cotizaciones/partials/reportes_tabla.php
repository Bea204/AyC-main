<?php
// Este archivo espera que ya estén definidas las variables:
// $cotizaciones, $stats, $tasa_conversion, $tasa_aprobacion, $top_clientes
?>
<!-- Estadísticas principales -->
<div class="stats-grid">
    <div class="stat-item">
        <h3><?= number_format($stats['total_cotizaciones']) ?></h3>
        <p>Total Cotizaciones</p>
    </div>
    <div class="stat-item">
        <h3>$<?= number_format($stats['valor_total'], 2) ?></h3>
        <p>Valor Total</p>
    </div>
    <div class="stat-item">
        <h3>$<?= number_format($stats['promedio_valor'], 2) ?></h3>
        <p>Promedio por Cotización</p>
    </div>
    <div class="stat-item">
        <h3><?= $tasa_conversion ?>%</h3>
        <p>Tasa de Conversión</p>
    </div>
    <div class="stat-item">
        <h3><?= $tasa_aprobacion ?>%</h3>
        <p>Tasa de Aprobación</p>
    </div>
    <div class="stat-item">
        <h3><?= number_format($stats['convertidas']) ?></h3>
        <p>Convertidas a Venta</p>
    </div>
</div>

<!-- Gráficos -->
<div class="row">
    <div class="col-md-6">
        <div class="chart-container">
            <h5 class="mb-3"><i class="bi bi-pie-chart"></i> Distribución por Estado</h5>
            <canvas id="estadosChart" width="400" height="200"
                data-estados='<?= json_encode([
                    "Convertidas" => (int)$stats["convertidas"],
                    "Aprobadas" => (int)$stats["aprobadas"],
                    "Enviadas" => (int)$stats["enviadas"],
                    "Rechazadas" => (int)$stats["rechazadas"],
                    "Borrador" => (int)$stats["total_cotizaciones"] - (int)$stats["convertidas"] - (int)$stats["aprobadas"] - (int)$stats["enviadas"] - (int)$stats["rechazadas"]
                ]) ?>'></canvas>
        </div>
    </div>
    <div class="col-md-6">
        <div class="chart-container">
            <h5 class="mb-3"><i class="bi bi-bar-chart"></i> Top 5 Clientes</h5>
            <canvas id="clientesChart" width="400" height="200"
                data-clientes='<?= json_encode((function() use ($top_clientes) {
                    $top_clientes->data_seek(0);
                    $arr = [];
                    while ($c = $top_clientes->fetch_assoc()) {
                        $arr[] = [
                            "nombre" => $c["cliente_nombre"],
                            "valor" => (float)$c["valor_total"]
                        ];
                    }
                    return $arr;
                })()) ?>'></canvas>
        </div>
    </div>
</div>

<!-- Top Clientes -->
<div class="stats-card">
    <h5 class="mb-3"><i class="bi bi-people"></i> Top 5 Clientes por Valor</h5>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Cotizaciones</th>
                    <th>Valor Total</th>
                    <th>Promedio</th>
                </tr>
            </thead>
            <tbody>
                <?php $top_clientes->data_seek(0); while ($cliente = $top_clientes->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($cliente['cliente_nombre']) ?></strong></td>
                        <td><?= $cliente['total_cotizaciones'] ?></td>
                        <td>$<?= number_format($cliente['valor_total'], 2) ?></td>
                        <td>$<?= number_format($cliente['promedio_valor'], 2) ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Lista de Cotizaciones -->
<div class="stats-card">
    <h5 class="mb-3"><i class="bi bi-list-ul"></i> Cotizaciones del Período</h5>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Cliente</th>
                    <th>Fecha</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th>Productos</th>
                </tr>
            </thead>
            <tbody>
                <?php $cotizaciones->data_seek(0); while ($cot = $cotizaciones->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <a href="ver.php?id=<?= $cot['cotizacion_id'] ?>" class="text-decoration-none">
                                <?= htmlspecialchars($cot['numero_cotizacion']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($cot['cliente_nombre']) ?></td>
                        <td><?= date('d/m/Y', strtotime($cot['fecha_cotizacion'])) ?></td>
                        <td><strong>$<?= number_format($cot['total'], 2) ?></strong></td>
                        <td>
                            <span class="estado-badge estado-<?= strtolower($cot['estado']) ?>">
                                <?= htmlspecialchars($cot['estado']) ?>
                            </span>
                        </td>
                        <td><?= $cot['productos_total'] ?? 0 ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div> 