<?php
$titulo = 'Catálogo'; $activo = 'catalogo';
require __DIR__ . '/includes/header.php';

$busca = trim((string)($_GET['q'] ?? ''));
$cat   = (int)($_GET['cat'] ?? 0);
$marca = (int)($_GET['marca'] ?? 0);
$talle = trim((string)($_GET['talle'] ?? ''));
$max   = (string)($_GET['max'] ?? '');

$where = ["p.Estado = 'Aprobada'"]; $par = [];
if ($busca !== '') { $where[] = '(p.Titulo LIKE ? OR p.Descripcion LIKE ? OR p.Color LIKE ? OR p.Material LIKE ?)'; $l = '%' . $busca . '%'; array_push($par, $l, $l, $l, $l); }
if ($cat)   { $where[] = 'p.ID_Categoria = ?'; $par[] = $cat; }
if ($marca) { $where[] = 'p.ID_Marca = ?';     $par[] = $marca; }
if ($talle !== '') { $where[] = 'p.Talle = ?'; $par[] = $talle; }
if ($max !== '' && is_numeric($max)) { $where[] = 'p.Precio <= ?'; $par[] = (float)$max; }

$lista    = prendas(implode(' AND ', $where), $par);
$cats     = filas('SELECT ID_Categoria, Nombre FROM categoria ORDER BY Nombre');
$marcas   = filas('SELECT ID_Marca, Nombre FROM marca ORDER BY Nombre');
$talles   = filas("SELECT DISTINCT Talle FROM prenda WHERE Estado = 'Aprobada' ORDER BY Talle");
?>
<h1>Catálogo</h1>
<form class="filtros" method="get" action="catalogo.php" style="max-width:none;flex-direction:row">
<input name="q" placeholder="Buscar prenda" value="<?= e($busca) ?>">
<select name="cat"><option value="0">Categoría</option><?php foreach ($cats as $c): ?><option value="<?= $c['ID_Categoria'] ?>"<?= $cat == $c['ID_Categoria'] ? ' selected' : '' ?>><?= e($c['Nombre']) ?></option><?php endforeach; ?></select>
<select name="marca"><option value="0">Marca</option><?php foreach ($marcas as $m): ?><option value="<?= $m['ID_Marca'] ?>"<?= $marca == $m['ID_Marca'] ? ' selected' : '' ?>><?= e($m['Nombre']) ?></option><?php endforeach; ?></select>
<select name="talle"><option value="">Talle</option><?php foreach ($talles as $t): ?><option<?= $talle === $t['Talle'] ? ' selected' : '' ?>><?= e($t['Talle']) ?></option><?php endforeach; ?></select>
<input type="number" name="max" min="0" placeholder="Precio máx." value="<?= e($max) ?>">
<button type="submit">Filtrar</button> <a class="btn sec" href="catalogo.php">Limpiar</a></form>
<?php if ($lista): ?><div class="grid"><?php foreach ($lista as $p) echo tarjeta_prenda($p); ?></div>
<?php else: ?><p>No encontramos prendas con esos filtros.</p><?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
