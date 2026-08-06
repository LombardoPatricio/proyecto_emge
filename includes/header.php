<?php
// includes/header.php
// Requiere que config.php ya fue incluido antes de llamar a este archivo
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PhoneShop</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="/assets/js/validation.js"></script>
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="logo">📱 PhoneShop</a>

    <div class="nav-links">
        <a href="index.php">Inicio</a>

        <?php if (isLoggedIn()): ?>
            <?php if (isAdmin()): ?>
                <a href="admin/products.php">Productos</a>
                <a href="admin/users.php">Usuarios</a>
            <?php endif; ?>
            <a href="cart.php" class="cart-btn">
                🛒 Carrito
                <span class="cart-count"><?= count($_SESSION['cart'] ?? []) ?></span>
            </a>
            <span class="nav-user">👤 <?= htmlspecialchars($_SESSION['name']) ?></span>
            <a href="logout.php" class="btn-outline">Salir</a>
        <?php else: ?>
            <a href="login.php" class="btn-outline">Iniciar Sesión</a>
        <?php endif; ?>
    </div>
</nav>

<main class="container">
<?php flash(); ?>
