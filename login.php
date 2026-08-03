<?php
require_once 'includes/config.php';

// Si ya está logueado, redirigir al inicio
if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';

// ─── PROCESAR FORMULARIO ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';
    $email  = trim($_POST['email'] ?? '');
    $pass   = $_POST['password'] ?? '';

    // ── INICIAR SESIÓN ──────────────────────────────────────────────────
    if ($action === 'login') {
        // Buscar usuario por email
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verificar contraseña
        if ($user && password_verify($pass, $user['password'])) {

            // Verificar que la cuenta esté activa
            if ((int)$user['active'] === 0) {
                $error = 'Tu cuenta está desactivada. Contactá al administrador.';
            } else {
                // Guardar datos en sesión
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name']    = $user['name'];
                $_SESSION['role']    = $user['role'];

                // Redirigir según rol
                if ($user['role'] === 'admin') {
                    redirect('admin/products.php', '¡Bienvenido, Admin!');
                } else {
                    redirect('index.php', '¡Bienvenido, ' . $user['name'] . '!');
                }
            }
        } else {
            $error = 'Email o contraseña incorrectos.';
        }
    }

    // ── REGISTRARSE ─────────────────────────────────────────────────────
    if ($action === 'register') {
        $name = trim($_POST['name'] ?? '');

        if (!$name || !$email || !$pass) {
            $error = 'Todos los campos son obligatorios.';
        } else {
            // Verificar que el email no exista
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $error = 'Ese email ya está registrado.';
            } else {
                // Crear usuario con rol 'user'
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')");
                $stmt->execute([$name, $email, $hash]);

                redirect('login.php', 'Cuenta creada. Ahora podés iniciar sesión.');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PhoneShop · Acceso</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/validation.js"></script>
    <style>
        /* Tabs de login/register */
        .tabs { display: flex; gap: 0; margin-bottom: 1.5rem; border-radius: 8px; overflow: hidden; border: 1px solid var(--border); }
        .tab-btn { flex: 1; padding: .65rem; background: var(--dark); color: var(--muted); border: none; cursor: pointer; font-size: .95rem; font-weight: 600; transition: all .2s; }
        .tab-btn.active { background: var(--orange); color: #000; }
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }
    </style>
</head>
<body>
<nav class="navbar">
    <a href="index.php" class="logo">📱 PhoneShop</a>
</nav>
<main class="container">

<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (isset($_SESSION['flash'])): ?>
    <?php flash(); ?>
<?php endif; ?>

<div class="form-card">
    <h2>📱 PhoneShop</h2>

    <!-- Tabs -->
    <div class="tabs">
        <button class="tab-btn active" onclick="showTab('login')">Iniciar Sesión</button>
        <button class="tab-btn" onclick="showTab('register')">Registrarse</button>
    </div>

    <!-- ── FORMULARIO DE LOGIN ──────────────────────────── -->
    <div id="tab-login" class="tab-panel active">
        <form method="POST" id="loginForm" novalidate>
            <input type="hidden" name="action" value="login">

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" id="login-email" placeholder="tu@email.com">
            </div>
            <div class="form-group">
                <label>Contraseña</label>
                <input type="password" name="password" id="login-password" placeholder="••••••••">
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%">Entrar</button>
        </form>

        <div class="form-footer" style="margin-top:1.5rem; padding-top:1rem; border-top:1px solid var(--border); font-size:.8rem;">
            <strong style="color:var(--muted)">Admin de prueba:</strong><br>
            admin@phoneshop.com / admin123
        </div>
    </div>

    <!-- ── FORMULARIO DE REGISTRO ───────────────────────── -->
    <div id="tab-register" class="tab-panel">
        <form method="POST" id="registerForm" novalidate>
            <input type="hidden" name="action" value="register">

            <div class="form-group">
                <label>Nombre</label>
                <input type="text" name="name" id="register-name" placeholder="Tu nombre">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" id="register-email" placeholder="tu@email.com">
            </div>
            <div class="form-group">
                <label>Contraseña</label>
                <input type="password" name="password" id="register-password" placeholder="Mínimo 6 caracteres">
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%">Crear cuenta</button>
        </form>
    </div>
</div>

</main>

<script>
function showTab(tab) {
    // Ocultar todos los paneles y desactivar botones
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));

    // Activar el seleccionado
    document.getElementById('tab-' + tab).classList.add('active');
    event.target.classList.add('active');
}

// ─── VALIDACIÓN: FORMULARIO DE LOGIN ───────────────────────────────────────
document.getElementById('loginForm').addEventListener('submit', function (e) {
    let valid = true;

    const email = document.getElementById('login-email');
    const pass  = document.getElementById('login-password');

    clearError(email);
    clearError(pass);

    if (!isNotEmpty(email.value)) {
        showError(email, 'El email es obligatorio.');
        valid = false;
    } else if (!isValidEmail(email.value)) {
        showError(email, 'Ingresá un email válido.');
        valid = false;
    }

    if (!isNotEmpty(pass.value)) {
        showError(pass, 'La contraseña es obligatoria.');
        valid = false;
    }

    if (!valid) e.preventDefault(); // Cancela el envío si hay errores
});

// ─── VALIDACIÓN: FORMULARIO DE REGISTRO ────────────────────────────────────
document.getElementById('registerForm').addEventListener('submit', function (e) {
    let valid = true;

    const name  = document.getElementById('register-name');
    const email = document.getElementById('register-email');
    const pass  = document.getElementById('register-password');

    clearError(name);
    clearError(email);
    clearError(pass);

    if (!isNotEmpty(name.value)) {
        showError(name, 'El nombre es obligatorio.');
        valid = false;
    }

    if (!isNotEmpty(email.value)) {
        showError(email, 'El email es obligatorio.');
        valid = false;
    } else if (!isValidEmail(email.value)) {
        showError(email, 'Ingresá un email válido.');
        valid = false;
    }

    if (!hasMinLength(pass.value, 6)) {
        showError(pass, 'La contraseña debe tener al menos 6 caracteres.');
        valid = false;
    }

    if (!valid) e.preventDefault();
});
</script>
</body>
</html>
