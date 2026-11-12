<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/direccion.php';

// Prendas del carrito que siguen disponibles
$items = [];
foreach (carrito() as $idp) {
    $p = fila(SQL_PRENDA . "WHERE p.ID_Prenda = ? AND p.Estado = 'Aprobada'", [$idp]);
    if ($p) $items[] = $p;
}
if (count($items) !== count(carrito())) {
    if (carrito()) aviso('Alguna prenda de tu carrito ya no está disponible y se quitó.', 'warning');
    carrito_guardar(array_map(fn($p) => (int)$p['ID_Prenda'], $items));
}
$subtotal = array_sum(array_map(fn($p) => (float)$p['Precio'], $items));
$envio = $items ? (float)COSTO_ENVIO : 0.0;
$errores = [];

if (es_post()) {
    verificar_csrf();
    $accion = post('accion');
    if ($accion === 'quitar') {
        $x = (int)post('id');
        carrito_guardar(array_values(array_filter(carrito(), fn($i) => $i !== $x)));
        ir('carrito.php');
    }
    if ($accion === 'confirmar' && $items) {
        $u = exigir_login();
        $metodo = post('metodo');
        if (!in_array($metodo, METODOS_PAGO, true)) $errores[] = 'Elegí un método de pago.';
        foreach ($items as $p) if ((int)$p['ID_Vendedor'] === (int)$u['ID_Usuario']) $errores[] = 'Tu carrito tiene una prenda propia: quitala para continuar.';

        $idDir = post('direccion'); $nueva = null;
        if ($idDir === 'nueva' || $idDir === '') { [$nueva, $e2] = leer_direccion(); $errores = array_merge($errores, $e2); }
        elseif (!valor('SELECT COUNT(*) FROM direccion WHERE ID_Direccion = ? AND ID_Usuario = ?', [(int)$idDir, $u['ID_Usuario']])) $errores[] = 'Elegí una dirección de envío.';

        if (!$errores) {
            $pdo = db(); $pdo->beginTransaction();
            try {
                $dirId = $nueva ? guardar_direccion((int)$u['ID_Usuario'], $nueva) : (int)$idDir;
                $total = $subtotal + $envio;
                q("INSERT INTO pedido (ID_Comprador, ID_Direccion, Fecha_pedido, Subtotal, Costo_Envio, Total, Metodo_Pago, Estado) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pendiente_Pago')",
                  [$u['ID_Usuario'], $dirId, ahora(), $subtotal, $envio, $total, $metodo]);
                $idPedido = (int)$pdo->lastInsertId();
                foreach ($items as $p) {
                    $precio = round((float)$p['Precio'], 2);
                    $comision = round($precio * COMISION_CHUKO, 2);
                    q('INSERT INTO detalle_pedido (ID_Pedido, ID_Prenda, Precio_Venta, Comision_Chuko, Monto_Vendedor) VALUES (?, ?, ?, ?, ?)',
                      [$idPedido, $p['ID_Prenda'], $precio, $comision, round($precio - $comision, 2)]);
                    // reserva atómica: sólo pasa a Vendida si seguía Aprobada
                    $st = q("UPDATE prenda SET Estado = 'Vendida' WHERE ID_Prenda = ? AND Estado = 'Aprobada'", [$p['ID_Prenda']]);
                    if ($st->rowCount() !== 1) throw new RuntimeException('no disponible');
                }
                $pdo->commit();
                carrito_guardar([]);
                aviso('¡Compra registrada! Pedido #' . $idPedido . ': queda pendiente de pago.');
                ir('perfil.php#compras');
            } catch (Throwable $t) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $errores[] = $t instanceof RuntimeException ? 'Otra persona compró una de las prendas justo antes. Revisá tu carrito.' : 'No pudimos registrar la compra, reintentá.';
            }
        }
    }
}

$titulo = 'Carrito';
require __DIR__ . '/includes/header.php';
$u = usuario();
$dirs = $u ? filas('SELECT * FROM direccion WHERE ID_Usuario = ? ORDER BY ID_Direccion', [$u['ID_Usuario']]) : [];
?>
<h1>Carrito</h1>
<?php if (!$items): ?><p>Tu carrito está vacío. <a href="catalogo.php">Ver catálogo</a></p><?php else: ?>
<table><tr><th>Prenda</th><th>Vendedor</th><th>Precio</th><th></th></tr>
<?php foreach ($items as $p): ?><tr><td><a href="prenda.php?id=<?= $p['ID_Prenda'] ?>"><?= e($p['Titulo'] ?: $p['Categoria']) ?></a></td><td><?= e($p['AliasPublico']) ?></td><td><?= dinero($p['Precio']) ?></td>
<td><form class="inline" method="post"><?= csrf_campo() ?><input type="hidden" name="accion" value="quitar"><input type="hidden" name="id" value="<?= $p['ID_Prenda'] ?>"><button class="btn sec" type="submit">Quitar</button></form></td></tr><?php endforeach; ?></table>
<p style="margin-top:14px">Subtotal: <?= dinero($subtotal) ?><br>Envío (a cargo del comprador): <?= dinero($envio) ?><br><b>Total: <?= dinero($subtotal + $envio) ?></b></p>

<?php if (!$u): ?><p>Para confirmar la compra necesitás <a href="login.php">ingresar</a> o <a href="registro.php">crear una cuenta</a>.</p>
<?php else: ?>
<form class="ancho" method="post" action="carrito.php"><?= csrf_campo() ?><input type="hidden" name="accion" value="confirmar">
<?php foreach ($errores as $m): ?><p class="err"><?= e($m) ?></p><?php endforeach; ?>
<h3>Dirección de envío</h3>
<?php foreach ($dirs as $i => $d): ?><label style="flex-direction:row;align-items:center;gap:8px"><input type="radio" name="direccion" value="<?= $d['ID_Direccion'] ?>" <?= (post('direccion') === (string)$d['ID_Direccion'] || (!post('direccion') && $i === 0)) ? 'checked' : '' ?>> <?= e($d['Calle'] . ' ' . $d['Numero'] . ($d['Piso_depto'] !== '' ? ' (' . $d['Piso_depto'] . ')' : '') . ', ' . $d['Localidad'] . ', ' . $d['Provincia'] . ' · CP ' . $d['Codigo_Postal']) ?></label><?php endforeach; ?>
<label style="flex-direction:row;align-items:center;gap:8px"><input type="radio" name="direccion" value="nueva" <?= (!$dirs || post('direccion') === 'nueva') ? 'checked' : '' ?>> Usar una dirección nueva</label>
<?= campos_direccion() ?>
<label>Método de pago<select name="metodo"><option value="">Elegir…</option><?php foreach (METODOS_PAGO as $m): ?><option<?= post('metodo') === $m ? ' selected' : '' ?>><?= e($m) ?></option><?php endforeach; ?></select></label>
<div><button type="submit">Confirmar compra</button> <a class="btn sec" href="catalogo.php">Seguir comprando</a></div></form>
<?php endif; endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
