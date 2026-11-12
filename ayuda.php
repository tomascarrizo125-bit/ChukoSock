<?php
$titulo = 'Ayuda';
require __DIR__ . '/includes/header.php';
?>
<h1>Ayuda al cliente</h1>
<div class="caja"><h3>Preguntas frecuentes</h3><p>
<b>¿Cómo se revisan las prendas?</b> Cada vendedor sube un mínimo de <?= MIN_FOTOS ?> fotos y un empleado de ChukoStock las revisa antes de publicar.<br>
<b>¿Cuánto me queda al vender?</b> El <?= (int)round((1 - COMISION_CHUKO) * 100) ?>% del precio; ChukoStock retiene el <?= (int)round(COMISION_CHUKO * 100) ?>%.<br>
<b>¿Quién paga el envío?</b> El comprador (<?= dinero(COSTO_ENVIO) ?> por pedido).<br>
<b>¿Qué pasa si pierdo mi contraseña?</b> Podés restablecerla desde <a href="recuperar.php">Recuperar contraseña</a> con tu email y tu DNI.<br>
<b>¿Puedo cancelar una compra?</b> Sí, desde <a href="perfil.php">tu perfil</a> mientras el pedido esté pendiente de pago.</p></div>
<?php if (EMAIL_AYUDA !== ''): ?><div class="caja"><h3>¿Necesitás algo más?</h3><p>Escribinos a <a href="mailto:<?= e(EMAIL_AYUDA) ?>"><?= e(EMAIL_AYUDA) ?></a> con tus consultas, problemas o sugerencias.</p></div><?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
