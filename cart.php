<?php
require_once 'includes/config.php';

// Solo usuarios logueados pueden usar el carrito
if (!isLoggedIn()) {
    redirect('login.php', 'Iniciá sesión para usar el carrito.', 'error');
}

// Inicializar carrito en sesión si no existe
// El carrito es un array: ['product_id' => quantity]
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// ─── PROCESAR ACCIONES DEL CARRITO ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf($_POST['csrf_token'] ?? '');
}

if (isset($_GET['action']) && in_array($_GET['action'], ['remove', 'clear'], true)) {
    requireCsrf($_GET['csrf_token'] ?? '');
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'add') {
    $id = (int)$_POST['product_id'];
    // Sumar 1 al producto (o iniciar en 1)
    $_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + 1;
    redirect('cart.php', 'Producto agregado al carrito.');
}

if ($action === 'remove') {
    $id = (int)$_GET['id'];
    unset($_SESSION['cart'][$id]);
    redirect('cart.php', 'Producto eliminado del carrito.');
}

if ($action === 'clear') {
    $_SESSION['cart'] = [];
    redirect('cart.php', 'Carrito vaciado.');
}

if ($action === 'checkout') {
    if (!empty($_SESSION['cart'])) {
        // 1. Calcular el total
        $ids      = implode(',', array_map('intval', array_keys($_SESSION['cart'])));
        $prods    = $db->query("SELECT * FROM products WHERE id IN ($ids)")->fetchAll(PDO::FETCH_ASSOC);
        $total    = 0;
        foreach ($prods as $p) {
            $total += $p['price'] * $_SESSION['cart'][$p['id']];
        }

        // 2. Insertar la orden en la tabla orders
        $stmt = $db->prepare("INSERT INTO orders (user_id, total, status) VALUES (?, ?, 'completed')");
        $stmt->execute([$_SESSION['user_id'], $total]);
        $orderId = $db->lastInsertId();

        // 3. Insertar cada producto en order_items
        $stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
        foreach ($prods as $p) {
            $qty = $_SESSION['cart'][$p['id']];
            $stmt->execute([$orderId, $p['id'], $qty, $p['price']]);
        }
    }

    // 4. Vaciar el carrito
    $_SESSION['cart'] = [];
    redirect('index.php', '¡Compra realizada con éxito! Gracias por tu pedido. 🎉');
}

// ─── OBTENER PRODUCTOS DEL CARRITO ───────────────────────────────────────
$cartItems = [];
$total = 0;

if (!empty($_SESSION['cart'])) {
    // Obtener IDs del carrito
    $ids = array_map('intval', array_keys($_SESSION['cart']));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $db->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($products as $p) {
        $qty = $_SESSION['cart'][$p['id']];
        $subtotal = $p['price'] * $qty;
        $total += $subtotal;
        $cartItems[] = array_merge($p, ['qty' => $qty, 'subtotal' => $subtotal]);
    }
}

include 'includes/header.php';
?>

<h2 class="page-title">🛒 Tu <span>Carrito</span></h2>

<?php if (empty($cartItems)): ?>
    <div class="empty-state">
        <div class="icon">🛒</div>
        <p>Tu carrito está vacío.</p>
        <a href="index.php" class="btn btn-primary" style="margin-top:1rem">Ver productos</a>
    </div>
<?php else: ?>

    <div class="table-wrap">
        <table class="cart-table">
            <thead>
                <tr>
                    <th>Imagen</th>
                    <th>Producto</th>
                    <th>Precio</th>
                    <th>Cant.</th>
                    <th>Subtotal</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cartItems as $item): ?>
                    <tr>
                        <td>
                            <img
                                src="<?= htmlspecialchars($item['image']) ?>"
                                alt=""
                                onerror="this.src='https://placehold.co/50x50/1a1a1a/888?text=📱'"
                            >
                        </td>
                        <td><?= htmlspecialchars($item['name']) ?></td>
                        <td>$<?= number_format($item['price'], 2) ?></td>
                        <td><?= $item['qty'] ?></td>
                        <td><strong style="color:var(--orange)">$<?= number_format($item['subtotal'], 2) ?></strong></td>
                        <td>
                            <a href="cart.php?action=remove&id=<?= $item['id'] ?>&csrf_token=<?= urlencode(csrfToken()) ?>"
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('¿Eliminar este producto?')">
                               ✕ Quitar
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Resumen y checkout -->
    <div class="cart-summary">
        <h3>Resumen del pedido</h3>
        <div class="cart-total">Total: <span style="color:var(--orange)">$<?= number_format($total, 2) ?></span></div>

        <form method="POST">
            <input type="hidden" name="action" value="checkout">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="btn btn-primary" style="width:100%; margin-bottom:.5rem">
                ✅ Confirmar compra
            </button>
        </form>

        <a href="cart.php?action=clear&csrf_token=<?= urlencode(csrfToken()) ?>"
           class="btn btn-outline"
           style="width:100%; text-align:center"
           onclick="return confirm('¿Vaciar el carrito?')">
           🗑 Vaciar carrito
        </a>
    </div>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>
