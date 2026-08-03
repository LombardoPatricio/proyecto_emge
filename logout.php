<?php
require_once 'includes/config.php';

// Destruir la sesión completamente
$_SESSION = [];
session_destroy();

redirect('login.php', 'Sesión cerrada correctamente.');
?>
