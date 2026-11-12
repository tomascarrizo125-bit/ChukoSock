<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';

date_default_timezone_set('America/Argentina/Buenos_Aires');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    ini_set('session.use_strict_mode', '1');
    session_start();
}

/* ---------- Base de datos ---------- */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = defined('DB_DSN')
            ? DB_DSN
            : 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        try {
            $pdo = new PDO($dsn, defined('DB_DSN') ? null : DB_USER, defined('DB_DSN') ? null : DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            exit('No se pudo conectar a la base de datos. Revisá config.php / config.local.php y que MySQL esté encendido.');
        }
    }
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}
function fila(string $sql, array $params = []): ?array { $r = q($sql, $params)->fetch(); return $r ?: null; }
function filas(string $sql, array $params = []): array { return q($sql, $params)->fetchAll(); }
function valor(string $sql, array $params = []) { $r = q($sql, $params)->fetch(PDO::FETCH_NUM); return $r ? $r[0] : null; }
function ahora(): string { return date('Y-m-d H:i:s'); }

/* ---------- Utilidades ---------- */
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function dinero($n): string { return '$' . number_format((float)$n, 0, ',', '.'); }
function fecha($f): string { return $f ? date('d/m/Y', strtotime((string)$f)) : ''; }
function ir(string $url): void { header('Location: ' . $url); exit; }
function post(string $k): string { return trim((string)($_POST[$k] ?? '')); }
function largo(string $s): int { return function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : strlen($s); }

function aviso(string $texto, string $tipo = 'success'): void { $_SESSION['avisos'][] = [$tipo, $texto]; }
function avisos_html(): string
{
    $out = '';
    foreach ($_SESSION['avisos'] ?? [] as [$tipo, $texto]) {
        $out .= '<div class="alert alert-' . e($tipo) . '" role="alert">' . e($texto) . '</div>';
    }
    unset($_SESSION['avisos']);
    return $out;
}

/* ---------- CSRF ---------- */
function csrf(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_campo(): string { return '<input type="hidden" name="csrf" value="' . e(csrf()) . '">'; }
function verificar_csrf(): void
{
    if (!hash_equals(csrf(), (string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit('Solicitud inválida (token). Volvé atrás y reintentá.');
    }
}
function es_post(): bool { return $_SERVER['REQUEST_METHOD'] === 'POST'; }

/* ---------- Sesión / usuario ---------- */
function usuario(): ?array
{
    static $u = false;
    if ($u === false) {
        $u = isset($_SESSION['uid']) ? fila('SELECT * FROM usuario WHERE ID_Usuario = ?', [$_SESSION['uid']]) : null;
        if (!$u) unset($_SESSION['uid']);
    }
    return $u;
}
function exigir_login(): array
{
    $u = usuario();
    if (!$u) {
        aviso('Ingresá a tu cuenta para continuar.', 'warning');
        $_SESSION['volver'] = basename($_SERVER['SCRIPT_NAME']) . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
        ir('login.php');
    }
    return $u;
}
function es_empleado(?array $u = null): bool
{
    $u ??= usuario();
    if (!$u) return false;
    $lista = array_map('mb_strtolower', MODERADORES);
    return in_array(mb_strtolower($u['Email']), $lista, true);
}
function exigir_empleado(): array
{
    $u = exigir_login();
    if (!es_empleado($u)) { http_response_code(403); exit('Acceso restringido a empleados de ChukoStock.'); }
    return $u;
}

/* ---------- Carrito (la BD no tiene tabla de carrito: vive en la sesión) ---------- */
function carrito(): array { return array_values(array_unique(array_map('intval', $_SESSION['carrito'] ?? []))); }
function carrito_guardar(array $ids): void { $_SESSION['carrito'] = array_values(array_unique($ids)); }

/* ---------- Catálogos fijos ---------- */
const PROVINCIAS = ['Buenos Aires','Ciudad Autónoma de Buenos Aires','Catamarca','Chaco','Chubut','Córdoba','Corrientes','Entre Ríos','Formosa','Jujuy','La Pampa','La Rioja','Mendoza','Misiones','Neuquén','Río Negro','Salta','San Juan','San Luis','Santa Cruz','Santa Fe','Santiago del Estero','Tierra del Fuego','Tucumán'];
const METODOS_PAGO = ['Mercado Pago','Transferencia bancaria','Tarjeta de crédito','Tarjeta de débito'];
const ESTADOS_PEDIDO = ['Pendiente_Pago' => 'Pendiente de pago','Pagado' => 'Pagado','Enviado' => 'Enviado','Entregado' => 'Entregado','Cancelado' => 'Cancelado'];
const ESTADOS_PRENDA = ['Pendiente' => 'Pendiente de revisión','Aprobada' => 'Aprobada','Rechazada' => 'Rechazada','Vendida' => 'Vendida','Pausada' => 'Pausada'];
const CONSERVACION = ['Excelente','Muy bueno','Bueno','Decente'];
const EMPRESAS_ENVIO = ['Andreani','Correo Argentino','OCA'];

/* ---------- Prendas ---------- */
const SQL_PRENDA = 'SELECT p.*, c.Nombre AS Categoria, m.Nombre AS Marca, u.AliasPublico,
        (SELECT f.URL FROM foto_prenda f WHERE f.ID_Prenda = p.ID_Prenda ORDER BY f.Orden, f.ID_Foto LIMIT 1) AS Foto
    FROM prenda p
    JOIN categoria c ON c.ID_Categoria = p.ID_Categoria
    JOIN usuario u   ON u.ID_Usuario = p.ID_Vendedor
    LEFT JOIN marca m ON m.ID_Marca = p.ID_Marca ';

function prendas(string $where, array $params = [], string $orden = 'p.FechaPublicacion DESC, p.ID_Prenda DESC', int $limite = 100): array
{
    return filas(SQL_PRENDA . ($where ? "WHERE $where " : '') . "ORDER BY $orden LIMIT " . (int)$limite, $params);
}
function foto_html(?string $url, string $alt = ''): string
{
    $img = $url ? '<img src="' . e($url) . '" alt="' . e($alt) . '" loading="lazy" onerror="this.remove()">' : '';
    return '<div class="foto">' . $img . 'Foto</div>';
}
function tarjeta_prenda(array $p): string
{
    return '<a class="card" href="prenda.php?id=' . (int)$p['ID_Prenda'] . '">' . foto_html($p['Foto'] ?? null, (string)$p['Titulo'])
        . '<b>' . e($p['Titulo'] ?: ($p['Categoria'] . ($p['Marca'] ? ' ' . $p['Marca'] : ''))) . '</b>'
        . '<small>' . e($p['Categoria']) . ' · Talle ' . e($p['Talle']) . ' · Vende: ' . e($p['AliasPublico']) . '</small>'
        . '<span class="precio">' . dinero($p['Precio']) . '</span></a>';
}
