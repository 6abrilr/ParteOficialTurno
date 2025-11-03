<?php
// php/auth/bootstrap.php
declare(strict_types=1);

// --- SESIÓN -------------------------------------------------
if (session_status() !== PHP_SESSION_ACTIVE) {
    // podés personalizar el nombre de la cookie si querés
    session_name('parte_sess');
    session_start();
}

// --- CSRF helpers ------------------------------------------
// Si ya tenías otras versiones en tu proyecto, quedate con la más estricta.
// Estas son mínimas para que no rompa.
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_input(): string {
    $t = csrf_token();
    return '<input type="hidden" name="csrf_token" value="'.htmlspecialchars($t, ENT_QUOTES, 'UTF-8').'">';
}

function csrf_verify(): void {
    $expected = $_SESSION['csrf_token'] ?? null;
    $got = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? null;
    if (!$expected || !$got || !hash_equals($expected, $got)) {
        http_response_code(400);
        echo "CSRF inválido";
        exit;
    }
}

// --- USER helpers ------------------------------------------

// Devuelve el usuario logueado según la sesión.
// Esto tiene que coincidir EXACTAMENTE con lo que guardamos en auth_login_cps().
function user(): ?array {
    if (!empty($_SESSION['user']) && is_array($_SESSION['user'])) {
        return $_SESSION['user'];
    }
    return null;
}

// ¿Está logueado?
function is_logged_in(): bool {
    return user() !== null;
}

/**
 * require_login()
 *
 * Si NO está logueado:
 *   - arma la URL de login correcta dentro del proyecto
 *   - le pasa ?next=<ruta actual> para que, después de login, pueda volver
 *
 * IMPORTANTE:
 *   Ajustamos las rutas asumiendo que el proyecto vive en
 *   http://localhost/ParteOficialTurno/
 */
function require_login(): void {
    if (is_logged_in()) {
        return;
    }

    // Ruta base del proyecto en la URL
    $BASE_URL = '/ParteOficialTurno';

    // Detectar a dónde quería ir el usuario.
    // Por ej: /ParteOficialTurno/public/index.php
    $currentPath = $_SERVER['REQUEST_URI'] ?? ($BASE_URL . '/public/index.php');

    // Armamos login.php ABSOLUTO dentro del proyecto
    $loginUrl = $BASE_URL . '/login.php?next=' . urlencode($currentPath);

    header('Location: ' . $loginUrl);
    exit;
}
