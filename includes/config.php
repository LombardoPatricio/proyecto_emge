<?php
// ─── CONFIGURACIÓN DE SESIÓN ───────────────────────────────────────────────
$cookieSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $cookieSecure,
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();

if (empty($_SESSION['user_agent'])) {
    $_SESSION['user_agent'] = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
}

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: DENY');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' https:; object-src 'none'; base-uri 'self'; frame-ancestors 'none';");

// ─── CONFIGURACIÓN DE BASE DE DATOS ───────────────────────────────────────
// Opción A: MySQL (recomendado con XAMPP)
// Opción B: SQLite (sin instalación extra)
// Cambiá $USE_MYSQL a false para usar SQLite

$USE_MYSQL = true;  // ← true = MySQL (XAMPP) | false = SQLite (sin configuración)

if ($USE_MYSQL) {
    // ── MySQL ──────────────────────────────────────────────
    define('DB_HOST', 'db');
    define('DB_NAME', 'phoneshop');
    define('DB_USER', 'root');
    define('DB_PASS', 'root');      

    $db = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS
    );
} else {
    // ── SQLite (funciona sin configuración extra) ──────────
    $db_path = __DIR__ . '/../database.sqlite';
    $db = new PDO('sqlite:' . $db_path);
}

$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// ─── CREAR TABLAS SI NO EXISTEN (solo SQLite — para MySQL usar database.sql)
if (!$USE_MYSQL) {
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            name       TEXT    NOT NULL,
            email      TEXT    UNIQUE NOT NULL,
            password   TEXT    NOT NULL,
            role       TEXT    NOT NULL DEFAULT 'user',
            active     INTEGER NOT NULL DEFAULT 1,
            created_at TEXT    NOT NULL DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS products (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            name        TEXT    NOT NULL,
            description TEXT,
            price       REAL    NOT NULL,
            image       TEXT,
            source      TEXT    NOT NULL DEFAULT 'manual',
            created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS orders (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id    INTEGER NOT NULL,
            total      REAL    NOT NULL,
            status     TEXT    NOT NULL DEFAULT 'completed',
            created_at TEXT    NOT NULL DEFAULT (datetime('now')),
            FOREIGN KEY (user_id) REFERENCES users(id)
        );

        CREATE TABLE IF NOT EXISTS order_items (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id   INTEGER NOT NULL,
            product_id INTEGER NOT NULL,
            quantity   INTEGER NOT NULL DEFAULT 1,
            unit_price REAL    NOT NULL,
            FOREIGN KEY (order_id)   REFERENCES orders(id),
            FOREIGN KEY (product_id) REFERENCES products(id)
        );
    ");
}

// ─── CREAR / ACTUALIZAR ADMIN POR DEFECTO ───────────────────────────────
$admin = $db->query("SELECT id, password FROM users WHERE role = 'admin' ORDER BY id LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$admin) {
    $hash = password_hash('Admin@2026!', PASSWORD_DEFAULT);
    $db->prepare("INSERT INTO users (name, email, password, role)
                  VALUES ('Administrador', 'admin@phoneshop.com', ?, 'admin')")
       ->execute([$hash]);
} elseif (!empty($admin['password']) && password_verify('admin123', $admin['password'])) {
    $hash = password_hash('Admin@2026!', PASSWORD_DEFAULT);
    $db->prepare("UPDATE users SET name = 'Administrador', email = 'admin@phoneshop.com', password = ?, role = 'admin' WHERE id = ?")
       ->execute([$hash, $admin['id']]);
}

// ─── FUNCIONES DE AYUDA ───────────────────────────────────────────────────

// Verificar si el usuario está logueado
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Verificar si el usuario es admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Redirigir con un mensaje
function redirect($url, $msg = '', $type = 'success') {
    if ($msg) {
        $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
    }
    header("Location: $url");
    exit;
}

// Generar o recuperar el token CSRF actual
function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Validar un token CSRF recibido
function verifyCsrfToken($token) {
    return is_string($token) && hash_equals(csrfToken(), $token);
}

// Regenerar la sesión al iniciar sesión
function regenerateSession() {
    session_regenerate_id(true);
    $_SESSION['user_agent'] = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
}

// Exigir un token CSRF válido para acciones sensibles
function requireCsrf($token) {
    if (!verifyCsrfToken($token)) {
        redirect('index.php', 'Solicitud inválida o expirada.', 'error');
    }
}

// Mostrar y limpiar mensajes flash
function flash() {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        $class = $f['type'] === 'error' ? 'alert-error' : 'alert-success';
        echo "<div class='alert $class'>{$f['msg']}</div>";
    }
}
?>
