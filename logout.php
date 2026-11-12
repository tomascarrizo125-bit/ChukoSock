<?php
require_once __DIR__ . '/includes/bootstrap.php';
$_SESSION = [];
if (ini_get('session.use_cookies')) { $c = session_get_cookie_params(); setcookie(session_name(), '', time() - 3600, $c['path'], $c['domain'], $c['secure'], $c['httponly']); }
session_destroy();
session_start();
aviso('Cerraste sesión.', 'info');
ir('index.php');
