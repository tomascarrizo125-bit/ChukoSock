<?php
require_once __DIR__ . '/includes/bootstrap.php';
$u = exigir_login();
$titulo = 'Lista de deseados';
require __DIR__ . '/includes/header.php';
$lista = filas(SQL_PRENDA . 'JOIN favorito fv ON fv.ID_Prenda = p.ID_Prenda WHERE fv.ID_Usuario = ? ORDER BY fv.Fecha_Agregado DESC', [$u['ID_Usuario']]);
?>
<h1>Lista de deseados</h1>
<?php if ($lista): ?><div class="grid"><?php foreach ($lista as $p) echo tarjeta_prenda($p); ?></div>
<?php else: ?><p>Todavía no guardaste prendas. Entrá a una prenda y tocá “♡ Deseados”. <a href="catalogo.php">Ver catálogo</a></p><?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
