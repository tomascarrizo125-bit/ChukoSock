<?php
$titulo = 'Inicio'; $activo = 'inicio';
require __DIR__ . '/includes/header.php';
$ultimas = prendas("p.Estado = 'Aprobada'", [], 'p.FechaPublicacion DESC, p.ID_Prenda DESC', 6);
?>
<section class="hero"><h1>Ropa usada en buen estado, revisada por nosotros</h1><p>ChukoStock es la plataforma para vender y comprar indumentaria de segunda mano desde cualquier punto del país. Cada prenda se revisa con fotos antes de publicarse.</p><p><a class="btn" href="catalogo.php">Ver catálogo</a> <a class="btn sec" href="vender.php">Quiero vender</a></p></section>
<div class="dos"><div class="caja"><h3>Para vendedores</h3><p>Te quedás con el <?= (int)round((1 - COMISION_CHUKO) * 100) ?>% del precio de venta. ChukoStock retiene el <?= (int)round(COMISION_CHUKO * 100) ?>%.</p></div><div class="caja"><h3>Para compradores</h3><p>Prendas de calidad con control previo. El envío corre por cuenta del comprador.</p></div></div>
<h2>Últimas prendas</h2>
<?php if ($ultimas): ?><div class="grid"><?php foreach ($ultimas as $p) echo tarjeta_prenda($p); ?></div>
<?php else: ?><p>Todavía no hay prendas publicadas. <a href="vender.php">Publicá la primera</a>.</p><?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
