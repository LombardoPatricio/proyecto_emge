<?php
// ─── CONFIGURACIÓN DE SESIÓN ───────────────────────────────────────────────
session_start();

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

// ─── CREAR ADMIN POR DEFECTO (si no existe) ───────────────────────────────
$admin = $db->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1")->fetch();
if (!$admin) {
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $db->prepare("INSERT INTO users (name, email, password, role)
                  VALUES ('Administrador', 'admin@phoneshop.com', ?, 'admin')")
       ->execute([$hash]);
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
