<?php
require_once __DIR__ . '/bootstrap.php';
$u = usuario();
$nCarrito = count(carrito());
$activo = $activo ?? '';
$act = fn(string $k) => $activo === $k ? ' active' : '';
?><!DOCTYPE html><html lang="es" data-bs-theme="dark"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= e($titulo ?? 'ChukoStock') ?> | ChukoStock</title><link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Dela+Gothic+One&family=Zen+Kaku+Gothic+New:wght@400;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"><link rel="stylesheet" href="style.css"></head><body>
<nav class="navbar navbar-expand-lg navbar-dark sticky-top"><div class="container-fluid px-lg-4">
<a class="navbar-brand" href="index.php"><i class="bi bi-bag-heart"></i> ChukoStock</a>
<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu" aria-label="Menú"><span class="navbar-toggler-icon"></span></button>
<div class="collapse navbar-collapse" id="menu">
<ul class="navbar-nav me-auto"><li class="nav-item"><a class="nav-link<?= $act('inicio') ?>" href="index.php">Inicio</a></li><li class="nav-item"><a class="nav-link<?= $act('catalogo') ?>" href="catalogo.php">Catálogo</a></li><li class="nav-item"><a class="nav-link<?= $act('vender') ?>" href="vender.php">Vender</a></li><li class="nav-item"><a class="nav-link<?= $act('tendencias') ?>" href="tendencias.php">Tendencias</a></li>
<li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Más</a><ul class="dropdown-menu">
<li><a class="dropdown-item" href="ayuda.php"><i class="bi bi-question-circle"></i> Ayuda</a></li>
<li><a class="dropdown-item" href="vendedor.php"><i class="bi bi-shop"></i> Vendedores</a></li>
<?php if (es_empleado($u)): ?><li><a class="dropdown-item" href="moderacion.php"><i class="bi bi-shield-check"></i> Moderación</a></li><?php endif; ?></ul></li></ul>
<ul class="navbar-nav align-items-lg-center">
<li class="nav-item"><a class="nav-link acceso" href="favoritos.php" title="Deseados"><i class="bi bi-heart"></i></a></li>
<li class="nav-item"><a class="nav-link acceso" href="carrito.php" title="Carrito"><i class="bi bi-cart3"></i><?php if ($nCarrito): ?> <span class="badge rounded-pill bg-primary"><?= $nCarrito ?></span><?php endif; ?></a></li>
<li class="nav-item dropdown"><a class="nav-link acceso dropdown-toggle" href="#" data-bs-toggle="dropdown" title="Mi cuenta"><i class="bi bi-person-circle"></i></a><ul class="dropdown-menu dropdown-menu-end">
<?php if ($u): ?>
<li><span class="dropdown-item-text"><small><?= e($u['AliasPublico']) ?></small></span></li>
<li><a class="dropdown-item" href="perfil.php"><i class="bi bi-person"></i> Mi perfil</a></li>
<li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Salir</a></li>
<?php else: ?>
<li><a class="dropdown-item" href="login.php"><i class="bi bi-box-arrow-in-right"></i> Ingresar</a></li>
<li><a class="dropdown-item" href="registro.php"><i class="bi bi-person-plus"></i> Crear cuenta</a></li>
<li><a class="dropdown-item" href="recuperar.php"><i class="bi bi-key"></i> Recuperar contraseña</a></li>
<?php endif; ?></ul></li></ul></div></div></nav>
<?= avisos_html() ?>
<main>
