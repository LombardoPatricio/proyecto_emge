-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 06-08-2026 a las 15:37:33
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `phoneshop`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `status` enum('pending','completed','cancelled') NOT NULL DEFAULT 'completed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total`, `status`, `created_at`) VALUES
(1, 4, 399.99, 'completed', '2026-06-16 12:18:59'),
(2, 4, 849.97, 'completed', '2026-06-16 12:21:50'),
(3, 3, 1649.98, 'completed', '2026-06-16 12:44:05'),
(4, 4, 123.00, 'completed', '2026-06-16 12:47:57');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `unit_price`) VALUES
(1, 1, 9, 1, 399.99),
(2, 2, 4, 1, 199.99),
(3, 2, 8, 1, 249.99),
(4, 2, 9, 1, 399.99),
(5, 3, 3, 1, 549.99),
(6, 3, 6, 1, 1099.99);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(500) DEFAULT NULL,
  `source` enum('api','manual') NOT NULL DEFAULT 'manual',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `image`, `source`, `created_at`) VALUES
(1, 'iPhone 15 Pro', 'Chip A17 Pro, cámara de 48MP con zoom óptico 5x, pantalla Super Retina XDR de 6.1\".', 1199.99, 'https://imgs.search.brave.com/xp96VMn8yDhQR77pQM-yWDfktKsMchnrb6ymOHip12s/rs:fit:860:0:0:0/g:ce/aHR0cHM6Ly9mZG4u/Z3NtYXJlbmEuY29t/L2ltZ3Jvb3QvcmV2/aWV3cy8yMy9hcHBs/ZS1pcGhvbmUtMTUt/cHJvL2xpZmVzdHls/ZS8tMTAyNHcyL2dz/bWFyZW5hXzAwNC5q/cGc', 'manual', '2026-06-10 10:47:42'),
(2, 'Samsung Galaxy S24 Ultra', 'Pantalla Dynamic AMOLED 6.8\", S Pen integrado, cámara de 200MP, batería de 5000 mAh.', 1299.99, 'https://imgs.search.brave.com/HRubHJ2Zisdo5LQWv_fCDnpx2-Q2ML1Xb3Yg9TRrPQE/rs:fit:860:0:0:0/g:ce/aHR0cHM6Ly9pbWFn/ZXMtbmEuc3NsLWlt/YWdlcy1hbWF6b24u/Y29tL2ltYWdlcy9J/LzYxQkFSaGtpeUFM/LmpwZw', 'manual', '2026-06-10 10:47:42'),
(3, 'Motorola Edge 50 Pro', 'Pantalla pOLED 6.7\" 144Hz, carga rápida 125W, cámara principal de 50MP con OIS.', 549.99, 'https://imgs.search.brave.com/BwZKdSD6DYdo4g825-v2W-HianO38yj5PjowW7cHTnI/rs:fit:860:0:0:0/g:ce/aHR0cHM6Ly9tLWNk/bi5waG9uZWFyZW5h/LmNvbS9pbWFnZXMv/cGhvbmVzLzg0NDYy/LTM1MC9Nb3Rvcm9s/YS1FZGdlLTUwLVBy/by53ZWJw', 'manual', '2026-06-10 10:47:42'),
(4, 'iPhone 5s', 'The iPhone 5s is a classic smartphone known for its compact design and advanced features during its release. While it\'s an older model, it still provides a reliable user experience.', 199.99, 'https://cdn.dummyjson.com/product-images/smartphones/iphone-5s/thumbnail.webp', 'api', '2026-06-10 11:02:23'),
(5, 'iPhone 6', 'The iPhone 6 is a stylish and capable smartphone with a larger display and improved performance. It introduced new features and design elements, making it a popular choice in its time.', 299.99, 'https://cdn.dummyjson.com/product-images/smartphones/iphone-6/thumbnail.webp', 'api', '2026-06-10 11:02:23'),
(6, 'iPhone 13 Pro', 'The iPhone 13 Pro is a cutting-edge smartphone with a powerful camera system, high-performance chip, and stunning display. It offers advanced features for users who demand top-notch technology.', 1099.99, 'https://cdn.dummyjson.com/product-images/smartphones/iphone-13-pro/thumbnail.webp', 'api', '2026-06-10 11:02:23'),
(7, 'iPhone X', 'The iPhone X is a flagship smartphone featuring a bezel-less OLED display, facial recognition technology (Face ID), and impressive performance. It represents a milestone in iPhone design and innovation.', 899.99, 'https://cdn.dummyjson.com/product-images/smartphones/iphone-x/thumbnail.webp', 'api', '2026-06-10 11:02:23'),
(8, 'Oppo A57', 'The Oppo A57 is a mid-range smartphone known for its sleek design and capable features. It offers a balance of performance and affordability, making it a popular choice.', 249.99, 'https://cdn.dummyjson.com/product-images/smartphones/oppo-a57/thumbnail.webp', 'api', '2026-06-10 11:02:23'),
(9, 'Oppo F19 Pro Plus', 'ta mal', 100.00, 'https://cdn.dummyjson.com/product-images/smartphones/oppo-f19-pro-plus/thumbnail.webp', 'api', '2026-06-10 11:02:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `active`, `created_at`) VALUES
(2, 'juan', 'juan@example.com', '$2y$10$TKh8H1.PfunSVCLMmvU7bOJQxMvzpMzQlAFhSW1a2o6b.XyMJe2Yy', 'admin', 1, '2026-06-10 10:47:42'),
(3, 'Gomez', 'r@gmail.com', '$2y$10$AriH43VWVea0vlifthJyXOcQg3r/Wl/GfSWnhuZ9o5058z5LFToyC', 'user', 1, '2026-06-10 11:02:59'),
(4, 'Administrador', 'admin@phoneshop.com', '$2y$10$0t8GbxxEvrd80j.6jf9lFOOCTnPqPz6loJGVw3BcDYILbT2K3vFhm', 'admin', 1, '2026-06-10 11:30:05'),
(5, 'Lomba', 'asd@gmail.com', '$2y$10$XKihS7xTdAgyOh74y1SiRunL.5Dg8eTUvE7ux5AQ1lIwUh7wlxwtG', 'user', 1, '2026-06-15 21:17:12'),
(6, 'Axel', 'axel@gmail.com', '$2y$10$X4eK/nmU7qBChthcb9z6X.f/0dqv9ebaP58/zYa//I0qUwML51qF6', 'user', 1, '2026-06-15 21:21:40'),
(7, 'Gabriel', 'g@gmail.com', '$2y$10$jyUc4c/eJxHH/Hw80BsxeObwv8.geENzqOMMecCUNVDoxgs3qlBOO', 'user', 0, '2026-06-16 11:45:41');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_orders_user` (`user_id`);

--
-- Indices de la tabla `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_items_order` (`order_id`),
  ADD KEY `fk_items_product` (`product_id`);

--
-- Indices de la tabla `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
