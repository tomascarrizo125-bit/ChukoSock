<?php
$titulo = 'Vendedores';
require __DIR__ . '/includes/header.php';
$id = (int)($_GET['id'] ?? 0);
$v = $id ? fila('SELECT ID_Usuario, AliasPublico, Biografia, Fecha_Registro FROM usuario WHERE ID_Usuario = ?', [$id]) : null;

if (!$v) {
    $lista = filas("SELECT u.ID_Usuario, u.AliasPublico, u.Biografia, COUNT(*) AS Prendas
        FROM usuario u JOIN prenda p ON p.ID_Vendedor = u.ID_Usuario WHERE p.Estado = 'Aprobada'
        GROUP BY u.ID_Usuario, u.AliasPublico, u.Biografia ORDER BY u.AliasPublico");
    ?><h1>Vendedores</h1>
    <?php if ($lista): ?><div class="grid"><?php foreach ($lista as $x): ?><a class="card" href="vendedor.php?id=<?= $x['ID_Usuario'] ?>"><b><?= e($x['AliasPublico']) ?></b><small><?= e($x['Biografia']) ?></small><small><?= (int)$x['Prendas'] ?> prenda<?= $x['Prendas'] == 1 ? '' : 's' ?> en venta</small></a><?php endforeach; ?></div>
    <?php else: ?><p>Todavía no hay vendedores con prendas publicadas.</p><?php endif;
    require __DIR__ . '/includes/footer.php'; exit;
}
$val = fila('SELECT AVG(Puntaje) AS prom, COUNT(*) AS n FROM valoracion WHERE ID_Vendedor = ?', [$id]);
$ventas = (int)valor("SELECT COUNT(*) FROM detalle_pedido d JOIN prenda p ON p.ID_Prenda = d.ID_Prenda JOIN pedido pe ON pe.ID_Pedido = d.ID_Pedido
    WHERE p.ID_Vendedor = ? AND pe.Estado IN ('Pagado','Enviado','Entregado')", [$id]);
$prendas = prendas("p.ID_Vendedor = ? AND p.Estado = 'Aprobada'", [$id]);
$coment = filas('SELECT Puntaje, Comentario, Fecha_Valoracion FROM valoracion WHERE ID_Vendedor = ? ORDER BY Fecha_Valoracion DESC LIMIT 10', [$id]);
?>
<h1><?= e($v['AliasPublico']) ?></h1>
<div class="caja"><p>Vendedor/a desde <?= e(date('Y', strtotime($v['Fecha_Registro']))) ?> · <?= $ventas ?> venta<?= $ventas == 1 ? '' : 's' ?> · <?= $val['n'] ? 'Valoración ' . number_format((float)$val['prom'], 1, ',', '') . ' / 5 (' . (int)$val['n'] . ')' : 'Sin valoraciones todavía' ?><?= $v['Biografia'] !== '' ? '<br>' . e($v['Biografia']) : '' ?></p></div>
<h2>Sus prendas</h2>
<?php if ($prendas): ?><div class="grid"><?php foreach ($prendas as $p) echo tarjeta_prenda($p); ?></div><?php else: ?><p>No tiene prendas en venta ahora.</p><?php endif; ?>
<?php if ($coment): ?><h2>Valoraciones</h2><?php foreach ($coment as $c): ?><div class="caja"><span class="estrellas"><?= str_repeat('★', (int)$c['Puntaje']) . str_repeat('☆', 5 - (int)$c['Puntaje']) ?></span> <small><?= fecha($c['Fecha_Valoracion']) ?></small><br><?= e($c['Comentario']) ?></div><?php endforeach; endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
