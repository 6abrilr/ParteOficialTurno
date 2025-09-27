<?php
// php/db.php
declare(strict_types=1);

function db(): PDO {
  $dsn  = 'mysql:host=127.0.0.1;dbname=parteoficialturno;charset=utf8mb4';
  $user = 'root';
  $pass = ''; // ajustá si corresponde

  $pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
  ]);
  return $pdo;
}
