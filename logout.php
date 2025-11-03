<?php
declare(strict_types=1);

// Cerramos sesión CPS local y volvemos al login

// siempre iniciar sesión antes de tocar $_SESSION
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// limpiar todos los datos de sesión
$_SESSION = [];

// destruir la cookie de sesión si existe
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'] ?? '/',
        $params['domain'] ?? '',
        $params['secure'] ?? false,
        $params['httponly'] ?? true
    );
}

// destruir la sesión en sí
session_destroy();

// redirigir al login de TU app con el mensajito ?out=1
header('Location: /ParteOficialTurno/login.php?out=1');
exit;
