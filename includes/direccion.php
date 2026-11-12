<?php
/** Lee y valida los campos de dirección (tabla direccion) desde $_POST con el prefijo dado. */
function leer_direccion(string $pre = 'dir_'): array
{
    $d = [];
    foreach (['calle','numero','piso','localidad','provincia','cp'] as $k) $d[$k] = post($pre . $k);
    $err = [];
    if ($d['calle'] === '' || largo($d['calle']) > 100)          $err[] = 'Ingresá la calle (hasta 100 caracteres).';
    if ($d['numero'] === '' || largo($d['numero']) > 10)         $err[] = 'Ingresá el número (hasta 10 caracteres).';
    if (largo($d['piso']) > 20)                                  $err[] = 'Piso/depto: hasta 20 caracteres.';
    if ($d['localidad'] === '' || largo($d['localidad']) > 80)   $err[] = 'Ingresá la localidad (hasta 80 caracteres).';
    if (!in_array($d['provincia'], PROVINCIAS, true))            $err[] = 'Elegí una provincia.';
    if ($d['cp'] === '' || largo($d['cp']) > 10)                 $err[] = 'Ingresá el código postal (hasta 10 caracteres).';
    return [$d, $err];
}
function guardar_direccion(int $idUsuario, array $d): int
{
    q('INSERT INTO direccion (ID_Usuario, Calle, Numero, Piso_depto, Localidad, Provincia, Codigo_Postal) VALUES (?, ?, ?, ?, ?, ?, ?)',
      [$idUsuario, $d['calle'], $d['numero'], $d['piso'], $d['localidad'], $d['provincia'], $d['cp']]);
    return (int)db()->lastInsertId();
}
function campos_direccion(string $pre = 'dir_', array $v = []): string
{
    $g = fn($k) => e($v[$k] ?? ($_POST[$pre . $k] ?? ''));
    $prov = '<option value="">Provincia</option>';
    foreach (PROVINCIAS as $p) $prov .= '<option' . (($v['provincia'] ?? ($_POST[$pre . 'provincia'] ?? '')) === $p ? ' selected' : '') . '>' . e($p) . '</option>';
    return '<label>Calle<input name="' . $pre . 'calle" maxlength="100" value="' . $g('calle') . '"></label>'
         . '<label>Número<input name="' . $pre . 'numero" maxlength="10" value="' . $g('numero') . '"></label>'
         . '<label>Piso / depto (opcional)<input name="' . $pre . 'piso" maxlength="20" value="' . $g('piso') . '"></label>'
         . '<label>Localidad<input name="' . $pre . 'localidad" maxlength="80" value="' . $g('localidad') . '"></label>'
         . '<label>Provincia<select name="' . $pre . 'provincia">' . $prov . '</select></label>'
         . '<label>Código postal<input name="' . $pre . 'cp" maxlength="10" value="' . $g('cp') . '"></label>';
}
