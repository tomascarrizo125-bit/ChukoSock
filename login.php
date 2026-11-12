<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (usuario()) ir('perfil.php');
$error = '';
if (es_post()) {
    verificar_csrf();
    $email = post('email'); $pass = (string)($_POST['password'] ?? '');
    $u = fila('SELECT * FROM usuario WHERE Email = ?', [$email]);
    // password_verify también se ejecuta si el usuario no existe, para no revelar cuentas por tiempo de respuesta
    $hash = $u['Contrasena_hash'] ?? '$2y$10$abcdefghijklmnopqrstuuJ1Hq3Jx9yH4C0qWk0oH1lZ8m7xq5q5K';
    if (password_verify($pass, $hash) && $u) {
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)$u['ID_Usuario'];
        $volver = $_SESSION['volver'] ?? 'perfil.php'; unset($_SESSION['volver']);
        ir(preg_match('/^[A-Za-z0-9_]+\.php(\?[A-Za-z0-9_=&%.\-]*)?$/', $volver) ? $volver : 'perfil.php');
    }
    $error = 'Email o contraseña incorrectos.';
}
$titulo = 'Ingresar';
require __DIR__ . '/includes/header.php';
?>
<h1>Ingresar</h1>
<form method="post" action="login.php"><?= csrf_campo() ?>
<?php if ($error): ?><p class="err"><?= e($error) ?></p><?php endif; ?>
<label>Email<input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"></label>
<label>Contraseña<input type="password" name="password" required></label>
<button type="submit">Ingresar</button><a href="recuperar.php">Olvidé mi contraseña</a><a href="registro.php">Crear una cuenta</a></form>
<?php require __DIR__ . '/includes/footer.php'; ?>
