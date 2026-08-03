<?php
require_once '../includes/config.php';

// ─── SOLO ADMINS ─────────────────────────────────────────────────────────
if (!isAdmin()) {
    redirect('../login.php', 'Acceso denegado.', 'error');
}

// ─── PROCESAR ACCIONES CRUD ───────────────────────────────────────────────

// ── CREAR / EDITAR ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id    = (int)($_POST['id'] ?? 0);
    $name  = trim($_POST['name'] ?? '');
    $desc  = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $image = trim($_POST['image'] ?? '');

    if (!$name || !$price) {
        redirect('products.php', 'El nombre y precio son obligatorios.', 'error');
    }

    if ($id) {
        // UPDATE: editar producto existente
        $stmt = $db->prepare("UPDATE products SET name=?, description=?, price=?, image=? WHERE id=?");
        $stmt->execute([$name, $desc, $price, $image, $id]);
        redirect('products.php', 'Producto actualizado.');
    } else {
        // INSERT: nuevo producto manual
        $stmt = $db->prepare("INSERT INTO products (name, description, price, image, source) VALUES (?, ?, ?, ?, 'manual')");
        $stmt->execute([$name, $desc, $price, $image]);
        redirect('products.php', 'Producto creado.');
    }
}

// ── ELIMINAR ────────────────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
    redirect('products.php', 'Producto eliminado.');
}

// ─── CARGAR PRODUCTO PARA EDITAR ─────────────────────────────────────────
$editProduct = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editProduct = $stmt->fetch(PDO::FETCH_ASSOC);
}

// ─── BÚSQUEDA ─────────────────────────────────────────────────────────────
$search = trim($_GET['search'] ?? '');

// ─── PAGINACIÓN ───────────────────────────────────────────────────────────
$perPage = 8;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

// ─── LISTAR PRODUCTOS (con o sin búsqueda) ───────────────────────────────
if ($search !== '') {
    $like = '%' . $search . '%';

    $stmtCount = $db->prepare("SELECT COUNT(*) FROM products WHERE name LIKE ?");
    $stmtCount->execute([$like]);
    $total = $stmtCount->fetchColumn();

    $stmt = $db->prepare("SELECT * FROM products WHERE name LIKE ? ORDER BY id DESC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $like, PDO::PARAM_STR);
    $stmt->bindValue(2, $perPage, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

} else {
    $total = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();

    $stmt = $db->prepare("SELECT * FROM products ORDER BY id DESC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $perPage, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$totalPages = (int)ceil($total / $perPage);

require_once '../includes/pagination.php';
include '../includes/header.php';
?>

<div class="admin-header">
    <h2 class="page-title" style="margin:0">Panel <span>Administrador</span></h2>
    <a href="../index.php" class="btn btn-outline btn-sm">← Ir a la tienda</a>
</div>

<!-- ── FORMULARIO CREAR / EDITAR ──────────────────────── -->
<div class="form-card" style="max-width:100%; margin:0 0 2rem 0;">
    <h2 style="text-align:left; font-size:1.1rem; margin-bottom:1rem">
        <?= $editProduct ? '✏️ Editar producto' : '➕ Agregar producto' ?>
    </h2>

    <form method="POST" id="productForm" novalidate style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; align-items:end;">
        <!-- ID oculto para edición -->
        <input type="hidden" name="id" value="<?= $editProduct['id'] ?? 0 ?>">

        <div class="form-group">
            <label>Nombre</label>
            <input type="text" name="name" id="product-name"
                   value="<?= htmlspecialchars($editProduct['name'] ?? '') ?>"
                   placeholder="Ej: Samsung Galaxy S24">
        </div>

        <div class="form-group">
            <label>Precio (USD)</label>
            <input type="number" name="price" id="product-price" step="0.01"
                   value="<?= $editProduct['price'] ?? '' ?>"
                   placeholder="299.99">
        </div>

        <div class="form-group">
            <label>Descripción</label>
            <input type="text" name="description" id="product-description"
                   value="<?= htmlspecialchars($editProduct['description'] ?? '') ?>"
                   placeholder="Breve descripción del teléfono">
        </div>

        <div class="form-group">
            <label>URL de imagen</label>
            <input type="url" name="image" id="product-image"
                   value="<?= htmlspecialchars($editProduct['image'] ?? '') ?>"
                   placeholder="https://...">
        </div>

        <div style="display:flex; gap:.5rem; grid-column:1/-1;">
            <button type="submit" class="btn btn-primary">
                <?= $editProduct ? '💾 Guardar cambios' : '➕ Crear producto' ?>
            </button>
            <?php if ($editProduct): ?>
                <a href="products.php" class="btn btn-outline">Cancelar</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- ── BUSCADOR ─────────────────────────────────────────── -->
<form method="GET" class="search-bar">
    <div class="search-input-wrap">
        <input type="text" name="search" placeholder="Buscar producto por nombre..."
               value="<?= htmlspecialchars($search) ?>">
    </div>
    <button type="submit" class="btn btn-primary">Buscar</button>
    <?php if ($search !== ''): ?>
        <a href="products.php" class="btn btn-outline">✕ Limpiar</a>
    <?php endif; ?>
</form>

<!-- ── TABLA DE PRODUCTOS ──────────────────────────────── -->
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Imagen</th>
                <th>Nombre</th>
                <th>Precio</th>
                <th>Origen</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($products)): ?>
                <tr><td colspan="6" style="text-align:center; color:var(--muted)">
                    <?= $search !== '' ? 'No se encontraron productos para "' . htmlspecialchars($search) . '".' : 'Sin productos' ?>
                </td></tr>
            <?php else: ?>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td>#<?= $p['id'] ?></td>
                        <td>
                            <img src="<?= htmlspecialchars($p['image']) ?>"
                                 style="width:45px;height:45px;object-fit:contain;background:#2a2a2a;border-radius:6px;padding:4px"
                                 onerror="this.src='https://placehold.co/45x45/1a1a1a/888?text=📱'"
                                 alt="">
                        </td>
                        <td><?= htmlspecialchars($p['name']) ?></td>
                        <td style="color:var(--orange); font-weight:700">$<?= number_format($p['price'], 2) ?></td>
                        <td>
                            <?php if ($p['source'] === 'api'): ?>
                                <span class="badge-api">API</span>
                            <?php else: ?>
                                <span class="badge-manual">Manual</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <!-- Botón editar -->
                            <a href="products.php?edit=<?= $p['id'] ?>" class="btn btn-outline btn-sm">✏️ Editar</a>

                            <!-- Botón eliminar -->
                            <a href="products.php?delete=<?= $p['id'] ?>"
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('¿Eliminar «<?= htmlspecialchars($p['name'], ENT_QUOTES) ?>»?')">
                               🗑 Borrar
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Paginación -->
<?php renderPagination($page, $totalPages, 'products.php', $search !== '' ? ['search' => $search] : []); ?>

<script>
// ─── VALIDACIÓN: FORMULARIO DE PRODUCTOS ───────────────────────────────────
document.getElementById('productForm').addEventListener('submit', function (e) {
    let valid = true;

    const name  = document.getElementById('product-name');
    const price = document.getElementById('product-price');

    clearError(name);
    clearError(price);

    if (!isNotEmpty(name.value)) {
        showError(name, 'El nombre es obligatorio.');
        valid = false;
    }

    if (!isPositiveNumber(price.value)) {
        showError(price, 'El precio debe ser un número mayor a 0.');
        valid = false;
    }

    if (!valid) e.preventDefault();
});
</script>

<?php include '../includes/footer.php'; ?>
