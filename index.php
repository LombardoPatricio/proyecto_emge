<?php
require_once 'includes/config.php';

// ─── CARGAR TELÉFONOS DESDE API (dummyjson.com) ──────────────────────────
// Solo si no hay productos de API ya en la base de datos
$hasApiProducts = $db->query("SELECT COUNT(*) FROM products WHERE source='api'")->fetchColumn();

if (!$hasApiProducts) {
    $json = @file_get_contents('https://dummyjson.com/products/category/smartphones?limit=6');
    if ($json) {
        $data = json_decode($json, true);
        foreach ($data['products'] as $p) {
            $stmt = $db->prepare("INSERT INTO products (name, description, price, image, source)
                                  VALUES (?, ?, ?, ?, 'api')");
            $stmt->execute([
                $p['title'],
                $p['description'],
                $p['price'],
                $p['thumbnail']
            ]);
        }
    }
}

// ─── BÚSQUEDA ─────────────────────────────────────────────────────────────
// Si el usuario escribió algo en el buscador, lo guardamos
$search = trim($_GET['search'] ?? '');

// ─── PAGINACIÓN ───────────────────────────────────────────────────────────
$perPage = 6;                                  // productos por página
$page    = max(1, (int)($_GET['page'] ?? 1));  // página actual (mínimo 1)
$offset  = ($page - 1) * $perPage;

// ─── CONTAR Y OBTENER PRODUCTOS (con o sin búsqueda) ─────────────────────
if ($search !== '') {
    // Buscar por nombre (LIKE = "contiene")
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
    // Sin búsqueda: todos los productos paginados
    $total = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();

    $stmt = $db->prepare("SELECT * FROM products ORDER BY id DESC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $perPage, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$totalPages = (int)ceil($total / $perPage);

require_once 'includes/pagination.php';
include 'includes/header.php';
?>

<!-- Hero -->
<div class="hero">
    <h1>Los mejores <span>smartphones</span></h1>
    <p>Explorá nuestra selección. Encontrá el tuyo al mejor precio.</p>
</div>

<!-- Buscador -->
<form method="GET" class="search-bar">
    <div class="search-input-wrap">
        <input type="text" name="search" placeholder="Buscar teléfono por nombre..."
               value="<?= htmlspecialchars($search) ?>">
    </div>
    <button type="submit" class="btn btn-primary">Buscar</button>
    <?php if ($search !== ''): ?>
        <a href="index.php" class="btn btn-outline">✕ Limpiar</a>
    <?php endif; ?>
</form>

<!-- Grilla de productos -->
<?php if (empty($products)): ?>
    <div class="empty-state">
        <div class="icon">📦</div>
        <p><?= $search !== '' ? 'No se encontraron resultados para "' . htmlspecialchars($search) . '".' : 'No hay productos todavía.' ?></p>
    </div>
<?php else: ?>
    <h2 class="page-title">Nuestros <span>Teléfonos</span></h2>
    <div class="products-grid">
        <?php foreach ($products as $p): ?>
            <div class="product-card">
                <img
                    class="product-img"
                    src="<?= htmlspecialchars($p['image']) ?>"
                    alt="<?= htmlspecialchars($p['name']) ?>"
                    onerror="this.src='https://placehold.co/300x200/1a1a1a/888?text=📱'"
                >
                <div class="product-info">
                    <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
                    <div class="product-desc"><?= htmlspecialchars(substr($p['description'], 0, 80)) ?>...</div>
                    <div class="product-price">$<?= number_format($p['price'], 2) ?></div>
                </div>
                <div class="product-actions">
                    <?php if (isLoggedIn()): ?>
                        <!-- Formulario para agregar al carrito -->
                        <form method="POST" action="cart.php">
                            <input type="hidden" name="action"     value="add">
                            <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                            <button type="submit" class="btn btn-primary btn-sm">🛒 Agregar</button>
                        </form>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline btn-sm">Iniciar sesión</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Paginación -->
    <?php renderPagination($page, $totalPages, 'index.php', $search !== '' ? ['search' => $search] : []); ?>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
