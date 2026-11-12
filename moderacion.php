<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/pedidos.php';
exigir_empleado();

if (es_post()) {
    verificar_csrf();
    $accion = post('accion'); $id = (int)post('id');
    if ($accion === 'aprobar' || $accion === 'rechazar') {
        $nuevo = $accion === 'aprobar' ? 'Aprobada' : 'Rechazada';
        $st = $nuevo === 'Aprobada'
            ? q("UPDATE prenda SET Estado = 'Aprobada', FechaPublicacion = ? WHERE ID_Prenda = ? AND Estado = 'Pendiente'", [ahora(), $id])
            : q("UPDATE prenda SET Estado = 'Rechazada' WHERE ID_Prenda = ? AND Estado = 'Pendiente'", [$id]);
        aviso($st->rowCount() ? "Prenda #$id: $nuevo." : 'Esa prenda ya fue revisada.', $st->rowCount() ? 'success' : 'warning');
    } elseif (in_array($accion, ['pagado', 'enviado', 'entregado', 'cancelar'], true)) {
        $pe = fila('SELECT Estado FROM pedido WHERE ID_Pedido = ?', [$id]);
        $est = $pe['Estado'] ?? '';
        if ($accion === 'pagado' && $est === 'Pendiente_Pago') { q("UPDATE pedido SET Estado = 'Pagado' WHERE ID_Pedido = ?", [$id]); aviso("Pedido #$id marcado como pagado."); }
        elseif ($accion === 'enviado' && $est === 'Pagado') {
            $emp = post('empresa'); $cod = post('codigo');
            if (!in_array($emp, EMPRESAS_ENVIO, true) || $cod === '' || largo($cod) > 40) aviso('Elegí la empresa e ingresá el código de seguimiento (hasta 40 caracteres).', 'danger');
            else {
                $pdo = db(); $pdo->beginTransaction();
                try {
                    q('INSERT INTO envio (ID_Pedido, Empresa_Envio, Codigo_Seguimiento, Fecha_Despacho, Fecha_Entrega) VALUES (?, ?, ?, ?, NULL)', [$id, $emp, $cod, ahora()]);
                    q("UPDATE pedido SET Estado = 'Enviado' WHERE ID_Pedido = ?", [$id]);
                    $pdo->commit(); aviso("Pedido #$id marcado como enviado.");
                } catch (Throwable $t) { if ($pdo->inTransaction()) $pdo->rollBack(); aviso('No se pudo registrar el envío.', 'danger'); }
            }
        } elseif ($accion === 'entregado' && $est === 'Enviado') {
            q('UPDATE envio SET Fecha_Entrega = ? WHERE ID_Pedido = ?', [ahora(), $id]);
            q("UPDATE pedido SET Estado = 'Entregado' WHERE ID_Pedido = ?", [$id]); aviso("Pedido #$id marcado como entregado.");
        } elseif ($accion === 'cancelar' && in_array($est, ['Pendiente_Pago', 'Pagado'], true)) { cancelar_pedido($id); aviso("Pedido #$id cancelado; las prendas volvieron a la venta.", 'info'); }
        else aviso('Ese cambio de estado no corresponde.', 'warning');
    }
    ir('moderacion.php');
}

$titulo = 'Moderación';
require __DIR__ . '/includes/header.php';
$pend = prendas("p.Estado = 'Pendiente'", [], 'p.FechaPublicacion ASC, p.ID_Prenda ASC', 100);
$pedidos = filas("SELECT pe.*, u.Nombre, u.Apellido, u.AliasPublico, d.Calle, d.Numero, d.Piso_depto, d.Localidad, d.Provincia, d.Codigo_Postal, u.Telefono
    FROM pedido pe JOIN usuario u ON u.ID_Usuario = pe.ID_Comprador JOIN direccion d ON d.ID_Direccion = pe.ID_Direccion
    WHERE pe.Estado IN ('Pendiente_Pago','Pagado','Enviado') ORDER BY pe.Fecha_pedido ASC LIMIT 100");
?>
<h1>Panel de moderación</h1><p>Prendas pendientes de revisión.</p>
<?php if ($pend): ?><table><tr><th>Prenda</th><th>Vendedor</th><th>Fotos</th><th>Acción</th></tr>
<?php foreach ($pend as $p): $fotos = filas('SELECT URL FROM foto_prenda WHERE ID_Prenda = ? ORDER BY Orden', [$p['ID_Prenda']]); ?>
<tr><td><a href="prenda.php?id=<?= $p['ID_Prenda'] ?>"><?= e($p['Titulo']) ?></a><br><small><?= e($p['Categoria']) ?> · Talle <?= e($p['Talle']) ?> · <?= e($p['Estado_Conversacion']) ?> · <?= dinero($p['Precio']) ?></small></td>
<td><?= e($p['AliasPublico']) ?></td>
<td><?= count($fotos) ?><br><?php foreach ($fotos as $f): ?><a href="<?= e($f['URL']) ?>" target="_blank" rel="noopener"><small>ver</small></a> <?php endforeach; ?></td>
<td><form class="inline" method="post"><?= csrf_campo() ?><input type="hidden" name="id" value="<?= $p['ID_Prenda'] ?>"><button name="accion" value="aprobar" type="submit">Aprobar</button><button class="btn sec" name="accion" value="rechazar" type="submit">Rechazar</button></form></td></tr><?php endforeach; ?></table>
<?php else: ?><p>No hay prendas pendientes. 🎉</p><?php endif; ?>

<h2>Pedidos en curso</h2>
<?php if ($pedidos): ?><table><tr><th>Pedido</th><th>Comprador y envío</th><th>Total</th><th>Estado</th><th>Acción</th></tr>
<?php foreach ($pedidos as $pe): ?><tr><td>#<?= $pe['ID_Pedido'] ?><br><small><?= fecha($pe['Fecha_pedido']) ?></small></td>
<td><?= e($pe['Nombre'] . ' ' . $pe['Apellido']) ?> (<?= e($pe['AliasPublico']) ?>) · tel. <?= e($pe['Telefono']) ?><br><small><?= e($pe['Calle'] . ' ' . $pe['Numero'] . ($pe['Piso_depto'] !== '' ? ' (' . $pe['Piso_depto'] . ')' : '') . ', ' . $pe['Localidad'] . ', ' . $pe['Provincia'] . ' · CP ' . $pe['Codigo_Postal']) ?></small></td>
<td><?= dinero($pe['Total']) ?><br><small><?= e($pe['Metodo_Pago']) ?></small></td><td><?= e(ESTADOS_PEDIDO[$pe['Estado']]) ?></td>
<td><form class="inline" method="post"><?= csrf_campo() ?><input type="hidden" name="id" value="<?= $pe['ID_Pedido'] ?>">
<?php if ($pe['Estado'] === 'Pendiente_Pago'): ?><button name="accion" value="pagado" type="submit">Marcar pagado</button><?php endif; ?>
<?php if ($pe['Estado'] === 'Pagado'): ?><select name="empresa"><?php foreach (EMPRESAS_ENVIO as $em) echo '<option>' . e($em) . '</option>'; ?></select><input name="codigo" maxlength="40" placeholder="Código de seguimiento"><button name="accion" value="enviado" type="submit">Marcar enviado</button><?php endif; ?>
<?php if ($pe['Estado'] === 'Enviado'): ?><button name="accion" value="entregado" type="submit">Marcar entregado</button><?php endif; ?>
<?php if ($pe['Estado'] !== 'Enviado'): ?><button class="btn sec" name="accion" value="cancelar" type="submit">Cancelar</button><?php endif; ?></form></td></tr><?php endforeach; ?></table>
<?php else: ?><p>No hay pedidos en curso.</p><?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
