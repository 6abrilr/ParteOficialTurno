<?php
declare(strict_types=1);
require_once __DIR__ . '/php/auth/bootstrap.php';

auth_logout(); // destruye sesión/cookies

// Volver al login del proyecto (no a /login.php del root del servidor)
header('Location: ' . url('login.php') . '?out=1');
exit;
