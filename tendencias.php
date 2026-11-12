<?php
$titulo = 'Tendencias'; $activo = 'tendencias';
require __DIR__ . '/includes/header.php';
// La BD no registra visitas/clics: el ranking se arma con lo que sí guarda (deseados y ventas).
$lista = filas("SELECT p.ID_Prenda, p.Titulo, c.Nombre AS Categoria, m.Nombre AS Marca,
    (SELECT COUNT(*) FROM favorito f WHERE f.ID_Prenda = p.ID_Prenda) AS Deseados,
    (SELECT COUNT(*) FROM detalle_pedido d JOIN pedido pe ON pe.ID_Pedido = d.ID_Pedido WHERE d.ID_Prenda = p.ID_Prenda AND pe.Estado <> 'Cancelado') AS Ventas
    FROM prenda p JOIN categoria c ON c.ID_Categoria = p.ID_Categoria LEFT JOIN marca m ON m.ID_Marca = p.ID_Marca
    WHERE p.Estado IN ('Aprobada','Vendida')
    ORDER BY (SELECT COUNT(*) FROM favorito f WHERE f.ID_Prenda = p.ID_Prenda) DESC, p.FechaPublicacion DESC LIMIT 15");
?>
<h1>Tendencias</h1><p>Las prendas más deseadas y vendidas.</p>
<?php if ($lista): ?><table><tr><th>#</th><th>Prenda</th><th>Deseados</th><th>Vendida</th></tr>
<?php foreach ($lista as $i => $p): ?><tr><td><?= $i + 1 ?></td><td><a href="prenda.php?id=<?= $p['ID_Prenda'] ?>"><?= e($p['Titulo'] ?: $p['Categoria'] . ($p['Marca'] ? ' ' . $p['Marca'] : '')) ?></a></td><td><?= (int)$p['Deseados'] ?></td><td><?= $p['Ventas'] ? 'Sí' : '—' ?></td></tr><?php endforeach; ?></table>
<?php else: ?><p>Todavía no hay prendas para mostrar.</p><?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
