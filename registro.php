<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (usuario()) ir('perfil.php');
$err = []; $v = ['nombre'=>'','apellido'=>'','email'=>'','dni'=>'','telefono'=>'','alias'=>'','bio'=>''];
if (es_post()) {
    verificar_csrf();
    foreach ($v as $k => $_) $v[$k] = post($k);
    $pass = (string)($_POST['password'] ?? ''); $pass2 = (string)($_POST['password2'] ?? '');

    if ($v['nombre'] === '' || largo($v['nombre']) > 60)     $err[] = 'Ingresá tu nombre (hasta 60 caracteres).';
    if ($v['apellido'] === '' || largo($v['apellido']) > 60) $err[] = 'Ingresá tu apellido (hasta 60 caracteres).';
    if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL) || largo($v['email']) > 120) $err[] = 'El email no es válido.';
    if (!preg_match('/^\d{7,8}$/', $v['dni']))               $err[] = 'El DNI argentino debe tener 7 u 8 números, sin puntos.';
    if (!preg_match('/^\d{8,10}$/', $v['telefono']) || (int)$v['telefono'] > 4294967295) $err[] = 'El teléfono debe tener entre 8 y 10 números, sin 0 ni 15 (ej: 1123456789).';
    if (!preg_match('/^[A-Za-z0-9_.]{3,50}$/', $v['alias'])) $err[] = 'El alias público debe tener 3 a 50 caracteres: letras, números, punto o guión bajo.';
    if (largo($v['bio']) > 300)                              $err[] = 'La biografía admite hasta 300 caracteres.';
    if (strlen($pass) < 8)                                   $err[] = 'La contraseña debe tener al menos 8 caracteres.';
    if ($pass !== $pass2)                                    $err[] = 'Las contraseñas no coinciden.';

    if (!$err) {
        if (valor('SELECT COUNT(*) FROM usuario WHERE LOWER(Email) = LOWER(?)', [$v['email']])) $err[] = 'Ya existe una cuenta con ese email.';
        if (valor('SELECT COUNT(*) FROM usuario WHERE DNI = ?', [(int)$v['dni']]))             $err[] = 'Ya existe una cuenta con ese DNI.';
        if (valor('SELECT COUNT(*) FROM usuario WHERE LOWER(AliasPublico) = LOWER(?)', [$v['alias']])) $err[] = 'Ese alias público ya está en uso.';
    }
    if (!$err) {
        $hash = password_hash($pass, PASSWORD_BCRYPT);
        // usuario.ID_Usuario no es AUTO_INCREMENT en la BD: se calcula MAX+1 (con reintento ante choques)
        $nuevo = null;
        for ($i = 0; $i < 5 && $nuevo === null; $i++) {
            try {
                q('INSERT INTO usuario (ID_Usuario, Nombre, Apellido, DNI, Dni_Verificado, Email, Contrasena_hash, Telefono, AliasPublico, Biografia, Fecha_Registro)
                   SELECT COALESCE(MAX(ID_Usuario), 0) + 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? FROM usuario',
                  [$v['nombre'], $v['apellido'], (int)$v['dni'], 1, $v['email'], $hash, (int)$v['telefono'], $v['alias'], $v['bio'], ahora()]);
                $nuevo = (int)valor('SELECT ID_Usuario FROM usuario WHERE Email = ?', [$v['email']]);
            } catch (PDOException $ex) { if ($ex->getCode() !== '23000') throw $ex; }
        }
        if ($nuevo) {
            session_regenerate_id(true);
            $_SESSION['uid'] = $nuevo;
            aviso('¡Cuenta creada! Bienvenido/a a ChukoStock.');
            ir('perfil.php');
        }
        $err[] = 'No pudimos crear la cuenta, reintentá.';
    }
}
$titulo = 'Crear cuenta';
require __DIR__ . '/includes/header.php';
?>
<h1>Crear cuenta</h1>
<form method="post" action="registro.php"><?= csrf_campo() ?>
<?php foreach ($err as $m): ?><p class="err"><?= e($m) ?></p><?php endforeach; ?>
<label>Nombre<input name="nombre" maxlength="60" required value="<?= e($v['nombre']) ?>"></label>
<label>Apellido<input name="apellido" maxlength="60" required value="<?= e($v['apellido']) ?>"></label>
<label>Email<input type="email" name="email" maxlength="120" required value="<?= e($v['email']) ?>"></label>
<label>DNI argentino<input name="dni" inputmode="numeric" placeholder="Sin puntos" required value="<?= e($v['dni']) ?>"></label>
<label>Teléfono<input name="telefono" inputmode="numeric" placeholder="Ej: 1123456789" required value="<?= e($v['telefono']) ?>"></label>
<label>Alias público (es el nombre que ven los demás)<input name="alias" maxlength="50" required value="<?= e($v['alias']) ?>"></label>
<label>Biografía (opcional)<textarea name="bio" rows="2" maxlength="300"><?= e($v['bio']) ?></textarea></label>
<label>Contraseña (mínimo 8)<input type="password" name="password" required></label>
<label>Repetir contraseña<input type="password" name="password2" required></label>
<button type="submit">Registrarme</button><a href="login.php">Ya tengo cuenta</a></form>
<?php require __DIR__ . '/includes/footer.php'; ?>
