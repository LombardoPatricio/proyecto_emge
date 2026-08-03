/**
 * assets/js/validation.js
 *
 * Funciones reutilizables de validación para los formularios.
 * Se usa en: login.php, admin/products.php, admin/users.php
 *
 * Patrón general:
 *   1. Escuchamos el evento "submit" del formulario
 *   2. Validamos cada campo con las funciones de abajo
 *   3. Si hay errores, los mostramos y cancelamos el envío (preventDefault)
 *   4. Si todo está OK, dejamos que el formulario se envíe normalmente al PHP
 */

// ─── Muestra un mensaje de error debajo de un campo ───────────────────────
function showError(input, message) {
    clearError(input);

    const errorEl = document.createElement('div');
    errorEl.className = 'field-error';
    errorEl.textContent = message;

    input.classList.add('input-error');
    input.parentElement.appendChild(errorEl);
}

// ─── Borra el mensaje de error de un campo ─────────────────────────────────
function clearError(input) {
    input.classList.remove('input-error');
    const existing = input.parentElement.querySelector('.field-error');
    if (existing) existing.remove();
}

// ─── Validaciones individuales (devuelven true si es VÁLIDO) ──────────────

function isNotEmpty(value) {
    return value.trim().length > 0;
}

function isValidEmail(value) {
    // Expresión simple: algo@algo.algo
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(value.trim());
}

function hasMinLength(value, min) {
    return value.trim().length >= min;
}

function isPositiveNumber(value) {
    const num = parseFloat(value);
    return !isNaN(num) && num > 0;
}
