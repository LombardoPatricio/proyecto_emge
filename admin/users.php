<?php
require_once '../includes/config.php';

// ─── SOLO ADMINS ──────────────────────────────────────────────────────────
if (!isAdmin()) {
    redirect('../login.php', 'Acceso denegado.', 'error');
}

// ─── CREAR / EDITAR (mismo patrón que admin/products.php) ────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id    = (int)($_POST['id'] ?? 0);
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $role  = $_POST['role'] === 'admin' ? 'admin' : 'user'; 

    // ── VALIDACIÓN BÁSICA (lado PHP) ────────────────────────────────────
    if (!$name || !$email) {
        redirect('users.php', 'El nombre y el email son obligatorios.', 'error');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirect('users.php', 'El email no tiene un formato válido.', 'error');
    }

    if ($id) {
        // ── EDITAR usuario existente ────────────────────────────────────

        // Verificar que el email no pertenezca a OTRO usuario
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $id]);
        if ($stmt->fetch()) {
            redirect('users.php', 'Ese email ya lo usa otro usuario.', 'error');
        }

        if ($pass) {
            // Si escribieron una contraseña nueva, también se actualiza
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET name=?, email=?, role=?, password=? WHERE id=?");
            $stmt->execute([$name, $email, $role, $hash, $id]);
        } else {
            // Si dejaron la contraseña vacía, no se toca
            $stmt = $db->prepare("UPDATE users SET name=?, email=?, role=? WHERE id=?");
            $stmt->execute([$name, $email, $role, $id]);
        }

        redirect('users.php', 'Usuario actualizado.');

    } else {
        // ── CREAR usuario nuevo ──────────────────────────────────────────

        if (!$pass || strlen($pass) < 6) {
            redirect('users.php', 'La contraseña debe tener al menos 6 caracteres.', 'error');
        }

        // Verificar que el email no exista
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            redirect('users.php', 'Ese email ya está registrado.', 'error');
        }

        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role, active) VALUES (?, ?, ?, ?, 1)");
        $stmt->execute([$name, $email, $hash, $role]);

        redirect('users.php', 'Usuario creado.');
    }
}

// ─── CAMBIAR ROL ──────────────────────────────────────────────────────────
if (isset($_GET['role'])) {
    $id      = (int)$_GET['id'];
    $newRole = $_GET['role'] === 'admin' ? 'admin' : 'user';

    if ($id === (int)$_SESSION['user_id']) {
        redirect('users.php', 'No podés cambiar tu propio rol.', 'error');
    }

    $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->execute([$newRole, $id]);
    redirect('users.php', 'Rol actualizado correctamente.');
}

// ─── ACTIVAR / DESACTIVAR ──────────────────────────────────────────────────
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];

    if ($id === (int)$_SESSION['user_id']) {
        redirect('users.php', 'No podés desactivar tu propia cuenta.', 'error');
    }

    // Invertir el valor actual de "active" (1 → 0, 0 → 1)
    $stmt = $db->prepare("UPDATE users SET active = 1 - active WHERE id = ?");
    $stmt->execute([$id]);
    redirect('users.php', 'Estado del usuario actualizado.');
}

// ─── ELIMINAR USUARIO ─────────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    if ($id === (int)$_SESSION['user_id']) {
        redirect('users.php', 'No podés eliminar tu propia cuenta.', 'error');
    }

    $db->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
    redirect('users.php', 'Usuario eliminado.');
}

// ─── CARGAR USUARIO PARA EDITAR ────────────────────────────────────────────
$editUser = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editUser = $stmt->fetch(PDO::FETCH_ASSOC);
}

// ─── BÚSQUEDA ─────────────────────────────────────────────────────────────
// Buscamos por nombre o email
$search = trim($_GET['search'] ?? '');

// ─── PAGINACIÓN ───────────────────────────────────────────────────────────
$perPage = 8;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

// ─── LISTAR USUARIOS (con o sin búsqueda) ────────────────────────────────
if ($search !== '') {
    $like = '%' . $search . '%';

    $stmtCount = $db->prepare("SELECT COUNT(*) FROM users WHERE name LIKE ? OR email LIKE ?");
    $stmtCount->execute([$like, $like]);
    $total = $stmtCount->fetchColumn();

    $stmt = $db->prepare("SELECT * FROM users WHERE name LIKE ? OR email LIKE ?
                           ORDER BY role DESC, id ASC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $like, PDO::PARAM_STR);
    $stmt->bindValue(2, $like, PDO::PARAM_STR);
    $stmt->bindValue(3, $perPage, PDO::PARAM_INT);
    $stmt->bindValue(4, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

} else {
    $total = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();

    $stmt = $db->prepare("SELECT * FROM users ORDER BY role DESC, id ASC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $perPage, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$totalPages = (int)ceil($total / $perPage);

require_once '../includes/pagination.php';
include '../includes/header.php';
?>

<div class="admin-header">
    <h2 class="page-title" style="margin:0">Gestión de <span>Usuarios</span></h2>
    <div style="display:flex; gap:.5rem">
        <a href="products.php" class="btn btn-outline btn-sm">← Productos</a>
        <a href="../index.php"  class="btn btn-outline btn-sm">Ver tienda</a>
    </div>
</div>

<!-- ── FORMULARIO CREAR / EDITAR ──────────────────────── -->
<div class="form-card" style="max-width:100%; margin:0 0 2rem 0;">
    <h2 style="text-align:left; font-size:1.1rem; margin-bottom:1rem">
        <?= $editUser ? '✏️ Editar usuario' : '➕ Crear usuario' ?>
    </h2>

    <form method="POST" id="userForm" novalidate
          data-is-edit="<?= $editUser ? '1' : '0' ?>"
          style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; align-items:end;">
        <input type="hidden" name="id" value="<?= $editUser['id'] ?? 0 ?>">

        <div class="form-group">
            <label>Nombre</label>
            <input type="text" name="name" id="user-name"
                   value="<?= htmlspecialchars($editUser['name'] ?? '') ?>"
                   placeholder="Nombre completo">
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" id="user-email"
                   value="<?= htmlspecialchars($editUser['email'] ?? '') ?>"
                   placeholder="correo@ejemplo.com">
        </div>

        <div class="form-group">
            <label>Contraseña <?= $editUser ? '(dejar vacío para no cambiar)' : '' ?></label>
            <input type="password" name="password" id="user-password"
                   placeholder="Mínimo 6 caracteres">
        </div>

        <div class="form-group">
            <label>Rol</label>
            <select name="role">
                <option value="user"  <?= (($editUser['role'] ?? 'user') === 'user')  ? 'selected' : '' ?>>Usuario</option>
                <option value="admin" <?= (($editUser['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Administrador</option>
            </select>
        </div>

        <div style="display:flex; gap:.5rem; grid-column:1/-1;">
            <button type="submit" class="btn btn-primary">
                <?= $editUser ? '💾 Guardar cambios' : '➕ Crear usuario' ?>
            </button>
            <?php if ($editUser): ?>
                <a href="users.php" class="btn btn-outline">Cancelar</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- ── BUSCADOR ─────────────────────────────────────────── -->
<form method="GET" class="search-bar">
    <div class="search-input-wrap">
        <input type="text" name="search" placeholder="Buscar por nombre o email..."
               value="<?= htmlspecialchars($search) ?>">
    </div>
    <button type="submit" class="btn btn-primary">Buscar</button>
    <?php if ($search !== ''): ?>
        <a href="users.php" class="btn btn-outline">✕ Limpiar</a>
    <?php endif; ?>
</form>

<!-- ── TABLA DE USUARIOS ──────────────────────────────── -->
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Rol</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
                <tr><td colspan="6" style="text-align:center; color:var(--muted)">
                    <?= $search !== '' ? 'No se encontraron usuarios para "' . htmlspecialchars($search) . '".' : 'Sin usuarios' ?>
                </td></tr>
            <?php else: ?>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td>#<?= $u['id'] ?></td>
                    <td>
                        <?= htmlspecialchars($u['name']) ?>
                        <?php if ($u['id'] == $_SESSION['user_id']): ?>
                            <span style="color:var(--muted); font-size:.8rem">(vos)</span>
                        <?php endif; ?>
                    </td>
                    <td style="color:var(--muted)"><?= htmlspecialchars($u['email']) ?></td>
                    <td>
                        <?php if ($u['role'] === 'admin'): ?>
                            <span class="badge-api">Admin</span>
                        <?php else: ?>
                            <span class="badge-manual">Usuario</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ((int)$u['active'] === 1): ?>
                            <span class="badge-manual">Activo</span>
                        <?php else: ?>
                            <span class="badge-api" style="background:#3a1a1a; color:#eb5757">Desactivado</span>
                        <?php endif; ?>
                    </td>
                    <td style="white-space:nowrap">
                        <?php if ($u['id'] != $_SESSION['user_id']): ?>

                            <!-- Editar -->
                            <a href="users.php?edit=<?= $u['id'] ?>" class="btn btn-outline btn-sm">✏️ Editar</a>

                            <?php if ($u['role'] === 'user'): ?>
                                <a href="users.php?role=admin&id=<?= $u['id'] ?>"
                                   class="btn btn-outline btn-sm"
                                   onclick="return confirm('¿Hacer admin a <?= htmlspecialchars($u['name'], ENT_QUOTES) ?>?')">
                                   ⬆️ Hacer Admin
                                </a>
                            <?php else: ?>
                                <a href="users.php?role=user&id=<?= $u['id'] ?>"
                                   class="btn btn-outline btn-sm"
                                   onclick="return confirm('¿Quitar rol de admin a <?= htmlspecialchars($u['name'], ENT_QUOTES) ?>?')">
                                   ⬇️ Quitar Admin
                                </a>
                            <?php endif; ?>

                            <!-- Activar / Desactivar -->
                            <?php if ((int)$u['active'] === 1): ?>
                                <a href="users.php?toggle=<?= $u['id'] ?>"
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('¿Desactivar a <?= htmlspecialchars($u['name'], ENT_QUOTES) ?>?')">
                                   🚫 Desactivar
                                </a>
                            <?php else: ?>
                                <a href="users.php?toggle=<?= $u['id'] ?>"
                                   class="btn btn-primary btn-sm"
                                   onclick="return confirm('¿Reactivar a <?= htmlspecialchars($u['name'], ENT_QUOTES) ?>?')">
                                   ✅ Activar
                                </a>
                            <?php endif; ?>

                        <?php else: ?>
                            <span style="color:var(--muted); font-size:.85rem">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Paginación -->
<?php renderPagination($page, $totalPages, 'users.php', $search !== '' ? ['search' => $search] : []); ?>

<script>
// ─── VALIDACIÓN: FORMULARIO DE USUARIOS ────────────────────────────────────
document.getElementById('userForm').addEventListener('submit', function (e) {
    let valid = true;

    const form  = e.target;
    const name  = document.getElementById('user-name');
    const email = document.getElementById('user-email');
    const pass  = document.getElementById('user-password');
    const isEdit = form.dataset.isEdit === '1';

    clearError(name);
    clearError(email);
    clearError(pass);

    if (!isNotEmpty(name.value)) {
        showError(name, 'El nombre es obligatorio.');
        valid = false;
    } else if (!hasMinLength(name.value, 2)) {
        showError(name, 'El nombre debe tener al menos 2 caracteres.');
        valid = false;
    }

    if (!isNotEmpty(email.value)) {
        showError(email, 'El email es obligatorio.');
        valid = false;
    } else if (!isValidEmail(email.value)) {
        showError(email, 'Ingresá un email válido.');
        valid = false;
    }

    // La contraseña es obligatoria solo al CREAR.
    // Al EDITAR, puede quedar vacía (no se cambia), pero si escriben algo, mínimo 6.
    if (!isEdit) {
        if (!hasMinLength(pass.value, 6)) {
            showError(pass, 'La contraseña debe tener al menos 6 caracteres.');
            valid = false;
        }
    } else {
        if (pass.value.length > 0 && !hasMinLength(pass.value, 6)) {
            showError(pass, 'Si vas a cambiarla, debe tener al menos 6 caracteres.');
            valid = false;
        }
    }

    if (!valid) e.preventDefault();
});
</script>

<?php include '../includes/footer.php'; ?>
