<?php
require_once __DIR__ . '/includes/bootstrap.php';
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$p = $id ? fila(SQL_PRENDA . 'WHERE p.ID_Prenda = ?', [$id]) : null;
$u = usuario();
$visible = $p && (in_array($p['Estado'], ['Aprobada', 'Vendida'], true) || ($u && ((int)$u['ID_Usuario'] === (int)$p['ID_Vendedor'])) || es_empleado($u));
if (!$visible) { http_response_code(404); $titulo = 'Prenda no encontrada'; require __DIR__ . '/includes/header.php'; echo '<h1>Prenda no encontrada</h1><p><a href="catalogo.php">Volver al catálogo</a></p>'; require __DIR__ . '/includes/footer.php'; exit; }

$esMia = $u && (int)$u['ID_Usuario'] === (int)$p['ID_Vendedor'];
if (es_post()) {
    verificar_csrf();
    $accion = post('accion');
    if ($accion === 'carrito') {
        if ($p['Estado'] !== 'Aprobada') aviso('Esa prenda ya no está disponible.', 'warning');
        elseif ($esMia)                   aviso('No podés comprar tu propia prenda.', 'warning');
        else { carrito_guardar(array_merge(carrito(), [$id])); aviso('Prenda agregada al carrito.'); ir('carrito.php'); }
    } elseif ($accion === 'favorito') {
        $u = exigir_login();
        if (valor('SELECT COUNT(*) FROM favorito WHERE ID_Usuario = ? AND ID_Prenda = ?', [$u['ID_Usuario'], $id])) {
            q('DELETE FROM favorito WHERE ID_Usuario = ? AND ID_Prenda = ?', [$u['ID_Usuario'], $id]);
            aviso('Quitada de tu lista de deseados.', 'info');
        } else {
            q('INSERT INTO favorito (ID_Usuario, ID_Prenda, Fecha_Agregado) VALUES (?, ?, ?)', [$u['ID_Usuario'], $id, ahora()]);
            aviso('Agregada a tu lista de deseados.');
        }
    }
    ir('prenda.php?id=' . $id);
}

$fotos = filas('SELECT URL FROM foto_prenda WHERE ID_Prenda = ? ORDER BY Orden, ID_Foto', [$id]);
$esFav = $u && valor('SELECT COUNT(*) FROM favorito WHERE ID_Usuario = ? AND ID_Prenda = ?', [$u['ID_Usuario'], $id]);
$enCarrito = in_array($id, carrito(), true);
$nombre = $p['Titulo'] ?: ($p['Categoria'] . ($p['Marca'] ? ' ' . $p['Marca'] : ''));
$titulo = $nombre;
require __DIR__ . '/includes/header.php';
?>
<div class="dos"><div><?php
    $f0 = $fotos[0]['URL'] ?? null;
    echo '<div class="foto g">' . ($f0 ? '<img src="' . e($f0) . '" alt="' . e($nombre) . '" onerror="this.remove()">' : '') . 'Foto principal</div>'; ?></div>
<div><h1><?= e($nombre) ?></h1><p class="precio"><?= dinero($p['Precio']) ?></p>
<p>Categoría: <?= e($p['Categoria']) ?><?= $p['Marca'] ? ' · Marca: ' . e($p['Marca']) : '' ?> · Talle <?= e($p['Talle']) ?><br>Estado: <?= e(mb_strtolower($p['Estado_Conversacion'])) ?> · Color: <?= e($p['Color']) ?> · Material: <?= e($p['Material']) ?></p>
<p><?= nl2br(e($p['Descripcion'])) ?></p>
<p>Vendedor: <a href="vendedor.php?id=<?= (int)$p['ID_Vendedor'] ?>"><?= e($p['AliasPublico']) ?></a></p>
<p><small>Envío a cargo del comprador. Revisada por el equipo de ChukoStock.</small></p>
<?php if ($p['Estado'] !== 'Aprobada'): ?><p><span class="chip"><?= e(ESTADOS_PRENDA[$p['Estado']]) ?></span></p><?php endif; ?>
<?php if ($p['Estado'] === 'Aprobada' && !$esMia): ?>
<form class="inline" method="post"><?= csrf_campo() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="accion" value="carrito">
<?php if ($enCarrito): ?><a class="btn" href="carrito.php">Ya está en tu carrito</a><?php else: ?><button type="submit">Agregar al carrito</button><?php endif; ?></form>
<?php endif; ?>
<form class="inline" method="post"><?= csrf_campo() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="accion" value="favorito"><button class="btn sec" type="submit" style="background:transparent;border:1px solid var(--violeta);color:var(--lila)"><?= $esFav ? '♥ En deseados (quitar)' : '♡ Deseados' ?></button></form>
</div></div>
<?php if (count($fotos) > 1): ?><div class="grid" style="margin-top:16px"><?php foreach (array_slice($fotos, 1) as $i => $f): ?><div class="foto"><img src="<?= e($f['URL']) ?>" alt="Foto <?= $i + 2 ?>" loading="lazy" onerror="this.remove()">Foto <?= $i + 2 ?></div><?php endforeach; ?></div><?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
