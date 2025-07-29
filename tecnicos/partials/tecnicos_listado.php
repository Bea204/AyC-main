<?php if ($tecnicos && $tecnicos->num_rows > 0): ?>
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Fecha de ingreso</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($t = $tecnicos->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($t['codigo']) ?></td>
                        <td><?= htmlspecialchars($t['nombre']) ?></td>
                        <td><?= $t['fecha_ingreso'] ? date('d/m/Y', strtotime($t['fecha_ingreso'])) : '-' ?></td>
                        <td>
                            <button class="btn btn-sm btn-primary btn-edit" 
                                data-id="<?= $t['tecnico_id'] ?>"
                                data-codigo="<?= htmlspecialchars($t['codigo']) ?>"
                                data-nombre="<?= htmlspecialchars($t['nombre']) ?>"
                                data-fecha="<?= $t['fecha_ingreso'] ?>">
                                <i class="bi bi-pencil"></i> Editar
                            </button>
                            <button class="btn btn-sm btn-danger btn-delete" 
                                data-id="<?= $t['tecnico_id'] ?>"
                                data-nombre="<?= htmlspecialchars($t['nombre']) ?>">
                                <i class="bi bi-trash"></i> Eliminar
                            </button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="empty-state">
        <i class="bi bi-person-badge"></i>
        <h5>No hay técnicos registrados</h5>
        <p>Agrega técnicos para comenzar a asignar tareas o equipos.</p>
    </div>
<?php endif; ?> 