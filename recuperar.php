<?php
require_once __DIR__ . '/includes/bootstrap.php';
// La BD no tiene tabla de tokens ni se envían emails: la identidad se confirma con email + DNI de la cuenta.
$err = '';
if (es_post()) {
    verificar_csrf();
    $email = post('email'); $dni = post('dni');
    $p1 = (string)($_POST['password'] ?? ''); $p2 = (string)($_POST['password2'] ?? '');
    if (strlen($p1) < 8)      $err = 'La nueva contraseña debe tener al menos 8 caracteres.';
    elseif ($p1 !== $p2)      $err = 'Las contraseñas no coinciden.';
    else {
        $u = preg_match('/^\d{7,8}$/', $dni) ? fila('SELECT ID_Usuario FROM usuario WHERE LOWER(Email) = LOWER(?) AND DNI = ?', [$email, (int)$dni]) : null;
        if ($u) {
            q('UPDATE usuario SET Contrasena_hash = ? WHERE ID_Usuario = ?', [password_hash($p1, PASSWORD_BCRYPT), $u['ID_Usuario']]);
            aviso('Contraseña actualizada. Ya podés ingresar.');
            ir('login.php');
        }
        $err = 'Los datos no coinciden con ninguna cuenta.';
    }
}
$titulo = 'Recuperar contraseña';
require __DIR__ . '/includes/header.php';
?>
<h1>Recuperar contraseña</h1>
<p>Ingresá el email y el DNI con los que te registraste y elegí una contraseña nueva.</p>
<form method="post" action="recuperar.php"><?= csrf_campo() ?>
<?php if ($err): ?><p class="err"><?= e($err) ?></p><?php endif; ?>
<label>Email<input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"></label>
<label>DNI<input name="dni" inputmode="numeric" required></label>
<label>Nueva contraseña<input type="password" name="password" required></label>
<label>Repetir nueva contraseña<input type="password" name="password2" required></label>
<button type="submit">Restablecer contraseña</button></form>
<?php require __DIR__ . '/includes/footer.php'; ?>
