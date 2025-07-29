<?php if ($clientes && $clientes->num_rows > 0): ?>
    <div id="clientsContainer">
        <?php while ($cliente = $clientes->fetch_assoc()): ?>
            <div class="client-card" data-client-name="<?= strtolower($cliente['cliente_nombre']) ?>" data-client-phone="<?= strtolower($cliente['cliente_telefono']) ?>" data-client-location="<?= strtolower($cliente['cliente_ubicacion']) ?>">
                <div class="client-header">
                    <h5 class="client-name">
                        <i class="bi bi-person-circle"></i>
                        <?= htmlspecialchars($cliente['cliente_nombre']) ?>
                    </h5>
                    <span class="badge bg-<?= $cliente['total_cotizaciones'] > 1 ? 'success' : 'info' ?>">
                        <?= $cliente['total_cotizaciones'] ?> cotización<?= $cliente['total_cotizaciones'] != 1 ? 'es' : '' ?>
                    </span>
                </div>
                <div class="client-contact">
                    <?php if ($cliente['cliente_telefono']): ?>
                        <div class="contact-item">
                            <i class="bi bi-telephone contact-icon"></i>
                            <span class="contact-text"><?= htmlspecialchars($cliente['cliente_telefono']) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($cliente['cliente_ubicacion']): ?>
                        <div class="contact-item">
                            <i class="bi bi-geo-alt contact-icon"></i>
                            <span class="contact-text"><?= htmlspecialchars($cliente['cliente_ubicacion']) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($cliente['cliente_email']): ?>
                        <div class="contact-item">
                            <i class="bi bi-envelope contact-icon"></i>
                            <span class="contact-text"><?= htmlspecialchars($cliente['cliente_email']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="client-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?= $cliente['total_cotizaciones'] ?></span>
                        <span class="stat-label">Cotizaciones</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">$<?= number_format($cliente['total_ventas'] ?? 0, 0) ?></span>
                        <span class="stat-label">Total Ventas</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?= $cliente['ultima_cotizacion'] ? date('d/m/Y', strtotime($cliente['ultima_cotizacion'])) : 'N/A' ?></span>
                        <span class="stat-label">Última Cotización</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?= $cliente['primera_cotizacion'] ? date('d/m/Y', strtotime($cliente['primera_cotizacion'])) : 'N/A' ?></span>
                        <span class="stat-label">Primera Cotización</span>
                    </div>
                </div>
                <div class="client-actions">
                    <a href="../cotizaciones/index.php?cliente=<?= urlencode($cliente['cliente_nombre']) ?>" class="btn-action btn-view">
                        <i class="bi bi-eye"></i> Ver Cotizaciones
                    </a>
                    <a href="#" class="btn-action btn-edit" data-bs-toggle="modal" data-bs-target="#modalEditarCliente" data-id="<?= $cliente['cliente_id'] ?>" data-nombre="<?= htmlspecialchars($cliente['cliente_nombre']) ?>" data-telefono="<?= htmlspecialchars($cliente['cliente_telefono']) ?>" data-ubicacion="<?= htmlspecialchars($cliente['cliente_ubicacion']) ?>" data-email="<?= htmlspecialchars($cliente['cliente_email'] ?? '') ?>">
                        <i class="bi bi-pencil"></i> Editar
                    </a>
                    <a href="#" class="btn-action btn-delete" data-bs-toggle="modal" data-bs-target="#modalEliminarCliente" data-id="<?= $cliente['cliente_id'] ?>" data-nombre="<?= htmlspecialchars($cliente['cliente_nombre']) ?>">
                        <i class="bi bi-trash"></i> Eliminar
                    </a>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
<?php else: ?>
    <div class="empty-state">
        <i class="bi bi-people"></i>
        <h5>No hay clientes registrados</h5>
        <p>Los clientes se crean automáticamente cuando realizas cotizaciones o puedes agregarlos manualmente.</p>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAgregarCliente">
            <i class="bi bi-plus-circle"></i> Agregar Cliente
        </button>
    </div>
<?php endif; ?> 