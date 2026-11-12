<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/direccion.php';
require_once __DIR__ . '/includes/pedidos.php';
$u = exigir_login();
$uid = (int)$u['ID_Usuario'];
$errDir = [];

if (es_post()) {
    verificar_csrf();
    $accion = post('accion');
    if ($accion === 'datos') {
        $alias = post('alias'); $tel = post('telefono'); $bio = post('bio'); $e = [];
        if (!preg_match('/^[A-Za-z0-9_.]{3,50}$/', $alias)) $e[] = 'Alias inválido (3 a 50 caracteres: letras, números, punto o guión bajo).';
        elseif (valor('SELECT COUNT(*) FROM usuario WHERE LOWER(AliasPublico) = LOWER(?) AND ID_Usuario <> ?', [$alias, $uid])) $e[] = 'Ese alias ya está en uso.';
        if (!preg_match('/^\d{8,10}$/', $tel) || (int)$tel > 4294967295) $e[] = 'Teléfono inválido (8 a 10 números, ej: 1123456789).';
        if (largo($bio) > 300) $e[] = 'La biografía admite hasta 300 caracteres.';
        if ($e) foreach ($e as $m) aviso($m, 'danger');
        else { q('UPDATE usuario SET AliasPublico = ?, Telefono = ?, Biografia = ? WHERE ID_Usuario = ?', [$alias, (int)$tel, $bio, $uid]); aviso('Datos actualizados.'); }
    } elseif ($accion === 'direccion') {
        [$d, $e] = leer_direccion();
        if ($e) foreach ($e as $m) aviso($m, 'danger'); else { guardar_direccion($uid, $d); aviso('Dirección guardada.'); }
    } elseif ($accion === 'pausar' || $accion === 'reactivar') {
        [$de, $a] = $accion === 'pausar' ? ['Aprobada', 'Pausada'] : ['Pausada', 'Aprobada'];
        $st = q('UPDATE prenda SET Estado = ? WHERE ID_Prenda = ? AND ID_Vendedor = ? AND Estado = ?', [$a, (int)post('id'), $uid, $de]);
        aviso($st->rowCount() ? 'Publicación actualizada.' : 'No se pudo cambiar el estado.', $st->rowCount() ? 'success' : 'warning');
    } elseif ($accion === 'cancelar') {
        $idp = (int)post('id');
        if (valor("SELECT COUNT(*) FROM pedido WHERE ID_Pedido = ? AND ID_Comprador = ? AND Estado = 'Pendiente_Pago'", [$idp, $uid])) { cancelar_pedido($idp); aviso('Pedido cancelado. Las prendas volvieron a estar disponibles.', 'info'); }
        else aviso('Ese pedido ya no se puede cancelar.', 'warning');
    } elseif ($accion === 'valorar') {
        $idp = (int)post('id'); $vend = (int)post('vendedor'); $pun = (int)post('puntaje'); $com = post('comentario');
        $ok = valor("SELECT COUNT(*) FROM pedido pe JOIN detalle_pedido d ON d.ID_Pedido = pe.ID_Pedido JOIN prenda p ON p.ID_Prenda = d.ID_Prenda
                     WHERE pe.ID_Pedido = ? AND pe.ID_Comprador = ? AND pe.Estado = 'Entregado' AND p.ID_Vendedor = ?", [$idp, $uid, $vend]);
        if (!$ok)                                   aviso('Sólo podés valorar vendedores de pedidos entregados.', 'warning');
        elseif ($pun < 1 || $pun > 5 || largo($com) > 300) aviso('Elegí un puntaje de 1 a 5 (comentario hasta 300 caracteres).', 'danger');
        elseif (valor('SELECT COUNT(*) FROM valoracion WHERE ID_Pedido = ? AND ID_Vendedor = ?', [$idp, $vend])) aviso('Ya valoraste a ese vendedor en este pedido.', 'warning');
        else { q('INSERT INTO valoracion (ID_Pedido, ID_Vendedor, Puntaje, Comentario, Fecha_Valoracion) VALUES (?, ?, ?, ?, ?)', [$idp, $vend, $pun, $com === '' ? null : $com, ahora()]); aviso('¡Gracias por tu valoración!'); }
    }
    ir('perfil.php');
}

$u = fila('SELECT * FROM usuario WHERE ID_Usuario = ?', [$uid]);
$dirs = filas('SELECT * FROM direccion WHERE ID_Usuario = ? ORDER BY ID_Direccion', [$uid]);
$pedidos = filas('SELECT pe.*, e.Empresa_Envio, e.Codigo_Seguimiento FROM pedido pe LEFT JOIN envio e ON e.ID_Pedido = pe.ID_Pedido WHERE pe.ID_Comprador = ? ORDER BY pe.Fecha_pedido DESC, pe.ID_Pedido DESC', [$uid]);
$mias = filas('SELECT p.ID_Prenda, p.Titulo, p.Precio, p.Estado FROM prenda p WHERE p.ID_Vendedor = ? ORDER BY p.FechaPublicacion DESC, p.ID_Prenda DESC', [$uid]);
$ganado = (float)valor("SELECT COALESCE(SUM(d.Monto_Vendedor), 0) FROM detalle_pedido d JOIN prenda p ON p.ID_Prenda = d.ID_Prenda JOIN pedido pe ON pe.ID_Pedido = d.ID_Pedido
                        WHERE p.ID_Vendedor = ? AND pe.Estado IN ('Pagado','Enviado','Entregado')", [$uid]);
$enVenta = (int)valor("SELECT COUNT(*) FROM prenda WHERE ID_Vendedor = ? AND Estado = 'Aprobada'", [$uid]);
$titulo = 'Mi perfil';
require __DIR__ . '/includes/header.php';
?>
<h1>Mi perfil</h1>
<div class="dos"><div class="caja"><h3>Mis datos</h3><p>Nombre: <?= e($u['Nombre'] . ' ' . $u['Apellido']) ?><br>Email: <?= e($u['Email']) ?><br>DNI: <?= e($u['DNI']) ?> <?= $u['Dni_Verificado'] ? '(verificado)' : '(sin verificar)' ?><br>Alias público: <?= e($u['AliasPublico']) ?><br>Teléfono: <?= e($u['Telefono']) ?><?= $u['Biografia'] !== '' ? '<br>Bio: ' . e($u['Biografia']) : '' ?></p>
<details><summary class="btn sec">Editar datos</summary>
<form method="post" style="margin-top:12px"><?= csrf_campo() ?><input type="hidden" name="accion" value="datos">
<label>Alias público<input name="alias" maxlength="50" required value="<?= e($u['AliasPublico']) ?>"></label>
<label>Teléfono<input name="telefono" inputmode="numeric" required value="<?= e($u['Telefono']) ?>"></label>
<label>Biografía<textarea name="bio" rows="2" maxlength="300"><?= e($u['Biografia']) ?></textarea></label>
<button type="submit">Guardar</button></form></details></div>
<div class="caja"><h3>Resumen</h3><p>Compras: <?= count($pedidos) ?><br>Prendas en venta: <?= $enVenta ?><br>Ganancias acumuladas (<?= (int)round((1 - COMISION_CHUKO) * 100) ?>%): <?= dinero($ganado) ?></p><a class="btn" href="vender.php">Publicar prenda</a></div></div>

<h2>Mis direcciones</h2>
<?php foreach ($dirs as $d): ?><p><?= e($d['Calle'] . ' ' . $d['Numero'] . ($d['Piso_depto'] !== '' ? ' (' . $d['Piso_depto'] . ')' : '') . ', ' . $d['Localidad'] . ', ' . $d['Provincia'] . ' · CP ' . $d['Codigo_Postal']) ?></p><?php endforeach; ?>
<?php if (!$dirs): ?><p>Todavía no cargaste direcciones.</p><?php endif; ?>
<details><summary class="btn sec">Agregar dirección</summary><form method="post" style="margin-top:12px"><?= csrf_campo() ?><input type="hidden" name="accion" value="direccion"><?= campos_direccion() ?><button type="submit">Guardar dirección</button></form></details>

<h2 id="compras">Mis compras</h2>
<?php if ($pedidos): ?><table><tr><th>Pedido</th><th>Fecha</th><th>Prendas</th><th>Total</th><th>Estado</th><th></th></tr>
<?php foreach ($pedidos as $pe): $its = items_pedido((int)$pe['ID_Pedido']); ?>
<tr><td>#<?= $pe['ID_Pedido'] ?></td><td><?= fecha($pe['Fecha_pedido']) ?></td>
<td><?php foreach ($its as $it) echo '<a href="prenda.php?id=' . (int)$it['ID_Prenda'] . '">' . e($it['Titulo'] ?: 'Prenda #' . $it['ID_Prenda']) . '</a><br>'; ?></td>
<td><?= dinero($pe['Total']) ?><br><small><?= e($pe['Metodo_Pago']) ?></small></td>
<td><?= e(ESTADOS_PEDIDO[$pe['Estado']]) ?><?= $pe['Empresa_Envio'] ? '<br><small>' . e($pe['Empresa_Envio']) . ' · ' . e($pe['Codigo_Seguimiento']) . '</small>' : '' ?></td>
<td><?php if ($pe['Estado'] === 'Pendiente_Pago'): ?><form class="inline" method="post"><?= csrf_campo() ?><input type="hidden" name="accion" value="cancelar"><input type="hidden" name="id" value="<?= $pe['ID_Pedido'] ?>"><button class="btn sec" type="submit">Cancelar</button></form><?php endif; ?>
<?php if ($pe['Estado'] === 'Entregado'): $vistos = [];
  foreach ($its as $it) { $vend = (int)$it['ID_Vendedor']; if (isset($vistos[$vend])) continue; $vistos[$vend] = 1;
    if (valor('SELECT COUNT(*) FROM valoracion WHERE ID_Pedido = ? AND ID_Vendedor = ?', [$pe['ID_Pedido'], $vend])) { echo '<small>Valoraste a ' . e($it['AliasPublico']) . '</small><br>'; continue; } ?>
<details><summary>Valorar a <?= e($it['AliasPublico']) ?></summary><form method="post"><?= csrf_campo() ?><input type="hidden" name="accion" value="valorar"><input type="hidden" name="id" value="<?= $pe['ID_Pedido'] ?>"><input type="hidden" name="vendedor" value="<?= $vend ?>">
<label>Puntaje<select name="puntaje"><?php for ($i = 5; $i >= 1; $i--) echo "<option value=\"$i\">$i " . str_repeat('★', $i) . '</option>'; ?></select></label><label>Comentario (opcional)<textarea name="comentario" rows="2" maxlength="300"></textarea></label><button type="submit">Enviar valoración</button></form></details>
<?php } endif; ?></td></tr><?php endforeach; ?></table>
<?php else: ?><p>Todavía no hiciste compras. <a href="catalogo.php">Ver catálogo</a></p><?php endif; ?>

<h2 id="publicaciones">Mis publicaciones</h2>
<?php if ($mias): ?><table><tr><th>Prenda</th><th>Precio</th><th>Estado</th><th></th></tr>
<?php foreach ($mias as $p): ?><tr><td><a href="prenda.php?id=<?= $p['ID_Prenda'] ?>"><?= e($p['Titulo'] ?: 'Prenda #' . $p['ID_Prenda']) ?></a></td><td><?= dinero($p['Precio']) ?></td><td><?= e(ESTADOS_PRENDA[$p['Estado']]) ?></td>
<td><?php if (in_array($p['Estado'], ['Aprobada', 'Pausada'], true)): ?><form class="inline" method="post"><?= csrf_campo() ?><input type="hidden" name="accion" value="<?= $p['Estado'] === 'Aprobada' ? 'pausar' : 'reactivar' ?>"><input type="hidden" name="id" value="<?= $p['ID_Prenda'] ?>"><button class="btn sec" type="submit"><?= $p['Estado'] === 'Aprobada' ? 'Pausar' : 'Reactivar' ?></button></form><?php endif; ?></td></tr><?php endforeach; ?></table>
<?php else: ?><p>Todavía no publicaste prendas. <a href="vender.php">Publicar una</a></p><?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
