<?php
require_once __DIR__ . '/includes/bootstrap.php';
$activo = 'vender';
$u = exigir_login();
$cats   = filas('SELECT ID_Categoria, Nombre FROM categoria ORDER BY Nombre');
$marcas = filas('SELECT ID_Marca, Nombre FROM marca ORDER BY Nombre');
$err = []; $v = ['titulo'=>'','desc'=>'','cat'=>'','marca'=>'','talle'=>'','color'=>'','material'=>'','estado'=>'','precio'=>''];

if (es_post()) {
    verificar_csrf();
    foreach ($v as $k => $_) $v[$k] = post($k);
    if ($v['titulo'] === '' || largo($v['titulo']) > 100)     $err[] = 'Ingresá un título (hasta 100 caracteres).';
    if ($v['desc'] === '')                                    $err[] = 'Ingresá la descripción.';
    if (!valor('SELECT COUNT(*) FROM categoria WHERE ID_Categoria = ?', [(int)$v['cat']])) $err[] = 'Elegí una categoría.';
    $idMarca = null;
    if ($v['marca'] !== '') { if (valor('SELECT COUNT(*) FROM marca WHERE ID_Marca = ?', [(int)$v['marca']])) $idMarca = (int)$v['marca']; else $err[] = 'Marca inválida.'; }
    if ($v['talle'] === '' || largo($v['talle']) > 10)        $err[] = 'Ingresá el talle (hasta 10 caracteres).';
    if ($v['color'] === '' || largo($v['color']) > 30)        $err[] = 'Ingresá el color (hasta 30 caracteres).';
    if ($v['material'] === '' || largo($v['material']) > 60)  $err[] = 'Ingresá el material (hasta 60 caracteres).';
    if (!in_array($v['estado'], CONSERVACION, true))          $err[] = 'Elegí el estado de conservación.';
    if (!is_numeric($v['precio']) || (float)$v['precio'] < 1 || (float)$v['precio'] > 99999999) $err[] = 'Ingresá un precio válido en pesos.';

    // fotos
    $subidas = [];
    $f = $_FILES['fotos'] ?? null;
    if ($f && is_array($f['name'])) {
        foreach ($f['name'] as $i => $nombre) {
            if ($f['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
            if ($f['error'][$i] !== UPLOAD_ERR_OK || $f['size'][$i] > 5 * 1024 * 1024) { $err[] = 'Cada foto debe pesar hasta 5 MB.'; break; }
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name'][$i]);
            $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
            if (!$ext || !@getimagesize($f['tmp_name'][$i])) { $err[] = 'Sólo se aceptan fotos JPG, PNG o WebP.'; break; }
            $subidas[] = [$f['tmp_name'][$i], $ext];
        }
    }
    if (count($subidas) < MIN_FOTOS) $err[] = 'Subí al menos ' . MIN_FOTOS . ' fotos.';
    if (count($subidas) > MAX_FOTOS) $err[] = 'Podés subir hasta ' . MAX_FOTOS . ' fotos.';

    if (!$err) {
        $pdo = db(); $pdo->beginTransaction(); $movidos = [];
        try {
            q("INSERT INTO prenda (ID_Vendedor, ID_Categoria, ID_Marca, Titulo, Descripcion, Talle, Color, Material, Estado_Conversacion, Precio, Estado, FechaPublicacion)
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pendiente', ?)",
              [$u['ID_Usuario'], (int)$v['cat'], $idMarca, $v['titulo'], $v['desc'], $v['talle'], $v['color'], $v['material'], $v['estado'], round((float)$v['precio'], 2), ahora()]);
            $idp = (int)$pdo->lastInsertId();
            $dir = __DIR__ . '/uploads/prendas';
            if (!is_dir($dir) && !mkdir($dir, 0775, true)) throw new RuntimeException('No se pudo crear la carpeta de fotos');
            foreach ($subidas as $n => [$tmp, $ext]) {
                $rel = sprintf('uploads/prendas/%04d_%d.%s', $idp, $n + 1, $ext);
                if (!move_uploaded_file($tmp, __DIR__ . '/' . $rel)) throw new RuntimeException('No se pudo guardar una foto');
                $movidos[] = __DIR__ . '/' . $rel;
                q('INSERT INTO foto_prenda (ID_Prenda, URL, Orden) VALUES (?, ?, ?)', [$idp, $rel, $n + 1]);
            }
            $pdo->commit();
            aviso('¡Prenda enviada a revisión! Un empleado de ChukoStock la revisará antes de publicarla.');
            ir('perfil.php#publicaciones');
        } catch (Throwable $t) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            foreach ($movidos as $m) @unlink($m);
            $err[] = 'No pudimos guardar la prenda, reintentá.';
        }
    }
}
$titulo = 'Vender';
require __DIR__ . '/includes/header.php';
?>
<h1>Publicar una prenda</h1>
<p>Subí al menos <?= MIN_FOTOS ?> fotos. Un empleado revisará la prenda antes de publicarla. Te quedás con el <?= (int)round((1 - COMISION_CHUKO) * 100) ?>%.</p>
<form class="ancho" method="post" action="vender.php" enctype="multipart/form-data"><?= csrf_campo() ?>
<?php foreach ($err as $m): ?><p class="err"><?= e($m) ?></p><?php endforeach; ?>
<label>Título<input name="titulo" maxlength="100" required value="<?= e($v['titulo']) ?>"></label>
<label>Descripción y estado<textarea name="desc" rows="3" required><?= e($v['desc']) ?></textarea></label>
<label>Categoría<select name="cat" required><option value="">Elegir…</option><?php foreach ($cats as $c): ?><option value="<?= $c['ID_Categoria'] ?>"<?= $v['cat'] == $c['ID_Categoria'] ? ' selected' : '' ?>><?= e($c['Nombre']) ?></option><?php endforeach; ?></select></label>
<label>Marca<select name="marca"><option value="">Sin marca / otra</option><?php foreach ($marcas as $m): ?><option value="<?= $m['ID_Marca'] ?>"<?= $v['marca'] == $m['ID_Marca'] ? ' selected' : '' ?>><?= e($m['Nombre']) ?></option><?php endforeach; ?></select></label>
<label>Talle<input name="talle" maxlength="10" list="talles" required value="<?= e($v['talle']) ?>"></label><datalist id="talles"><?php foreach (['XS','S','M','L','XL','XXL','Único'] as $t) echo '<option>' . $t . '</option>'; ?></datalist>
<label>Color<input name="color" maxlength="30" required value="<?= e($v['color']) ?>"></label>
<label>Material<input name="material" maxlength="60" required value="<?= e($v['material']) ?>"></label>
<label>Estado de conservación<select name="estado" required><option value="">Elegir…</option><?php foreach (CONSERVACION as $c): ?><option<?= $v['estado'] === $c ? ' selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select></label>
<label>Precio (ARS)<input type="number" name="precio" min="1" step="1" required value="<?= e($v['precio']) ?>"></label>
<label>Fotos (mínimo <?= MIN_FOTOS ?>, máximo <?= MAX_FOTOS ?>; JPG, PNG o WebP de hasta 5 MB)<input type="file" name="fotos[]" accept="image/jpeg,image/png,image/webp" multiple required></label>
<button type="submit">Enviar a revisión</button></form>
<?php require __DIR__ . '/includes/footer.php'; ?>
