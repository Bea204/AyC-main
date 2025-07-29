<?php if (isset($cotizaciones) && $cotizaciones->num_rows > 0): ?>
    <?php while ($cot = $cotizaciones->fetch_assoc()): ?>
        <div class="cotizacion-card">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <a href="ver.php?id=<?= $cot['cotizacion_id'] ?>" class="text-decoration-none">
                        <?= htmlspecialchars($cot['numero_cotizacion']) ?>
                    </a>
                    <span class="estado-badge estado-<?= strtolower($cot['estado']) ?> ms-2">
                        <?= htmlspecialchars($cot['estado']) ?>
                    </span>
                    <p class="text-muted mb-0"><?= htmlspecialchars($cot['cliente_nombre_real'] ?? $cot['cliente_nombre']) ?></p>
                    <small class="text-muted"><?= htmlspecialchars($cot['cliente_telefono_real'] ?? $cot['cliente_telefono']) ?></small><br>
                    <small class="text-muted"><?= htmlspecialchars($cot['cliente_direccion_real'] ?? $cot['cliente_ubicacion']) ?></small>
                    <small class="text-muted"><?= date('d/m/Y', strtotime($cot['fecha_cotizacion'])) ?></small>
                </div>
                <div class="col-md-2">
                    <strong>$<?= number_format($cot['total'], 2) ?></strong>
                    <br><small class="text-muted"><?= $cot['total_productos'] ?> productos</small>
                </div>
                <div class="col-md-2">
                    <small class="text-muted">Creada por:</small><br>
                    <strong><?= htmlspecialchars($cot['usuario_nombre'] ?? 'Sistema') ?></strong>
                </div>
                <div class="col-md-3">
                    <div class="acciones-cotizacion">
                        <a href="ver.php?id=<?= $cot['cotizacion_id'] ?>" class="btn-accion btn btn-outline-primary">
                            <i class="bi bi-eye"></i> Ver
                        </a>
                        <a href="editar.php?id=<?= $cot['cotizacion_id'] ?>" class="btn-accion btn btn-outline-secondary">
                            <i class="bi bi-pencil"></i> Editar
                        </a>
                        <a href="ver.php?id=<?= $cot['cotizacion_id'] ?>&imprimir=1" class="btn-accion btn btn-outline-info" target="_blank">
                            <i class="bi bi-printer"></i> Imprimir
                        </a>
                        <?php if ($cot['estado'] === 'aprobada'): ?>
                            <a href="convertir.php?id=<?= $cot['cotizacion_id'] ?>" class="btn-accion btn btn-success">
                                <i class="bi bi-check-circle"></i> Convertir
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
<?php else: ?>
    <div class="text-center py-5">
        <i class="bi bi-file-earmark-text" style="font-size: 4rem; color: #ccc;"></i>
        <h4 class="mt-3">No hay cotizaciones</h4>
        <p class="text-muted">Crea tu primera cotización para comenzar</p>
        <a href="crear.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Crear Cotización
        </a>
    </div>
<?php endif; ?> 