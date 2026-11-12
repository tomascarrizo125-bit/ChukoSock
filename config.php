<?php
/**
 * Configuración de ChukoStock.
 * NO subas contraseñas reales a GitHub: copiá config.local.php.example a
 * config.local.php (está en .gitignore) y cambiá ahí lo que necesites.
 */
if (is_file(__DIR__ . '/config.local.php')) {
    require __DIR__ . '/config.local.php';
}

// --- Base de datos (valores por defecto de XAMPP) ---
defined('DB_HOST')    || define('DB_HOST', 'localhost');
defined('DB_NAME')    || define('DB_NAME', 'chukostock');
defined('DB_USER')    || define('DB_USER', 'root');
defined('DB_PASS')    || define('DB_PASS', '');
defined('DB_CHARSET') || define('DB_CHARSET', 'utf8mb4');

// --- Reglas de negocio (README / documento: 85% vendedor, 15% ChukoStock, envío a cargo del comprador) ---
defined('COMISION_CHUKO') || define('COMISION_CHUKO', 0.15);
defined('COSTO_ENVIO')    || define('COSTO_ENVIO', 4000);   // $ fijo por pedido (valor del prototipo)
defined('MIN_FOTOS')      || define('MIN_FOTOS', 3);
defined('MAX_FOTOS')      || define('MAX_FOTOS', 8);

// --- Empleados de ChukoStock (acceso a Moderación y gestión de pedidos) ---
// La base de datos no tiene columna de rol, por eso se definen por email acá.
defined('MODERADORES') || define('MODERADORES', []);   // ej: ['empleado@chukostock.com']

// Email público de ayuda (opcional). Si queda vacío no se muestra.
defined('EMAIL_AYUDA') || define('EMAIL_AYUDA', '');
