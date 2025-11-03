<?php
// login_cps.php
// Helpers para autenticar contra el CPS y poblar la sesión local.

declare(strict_types=1);

/**
 * 1) Enviar usuario/clave al CPS y obtener token.
 * Confirmado: https://apicps.ejercito.mil.ar/api/v1/login
 */
function cps_authenticate(string $username, string $password): array {
    $CPS_LOGIN_URL = "https://apicps.ejercito.mil.ar/api/v1/login";

    $ch = curl_init($CPS_LOGIN_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST            => true,
        CURLOPT_RETURNTRANSFER  => true,
        CURLOPT_POSTFIELDS      => [
            'username' => $username,
            'password' => $password,
        ],
        // DESARROLLO: SSL desactivado para evitar error de CA interna.
        // PRODUCCIÓN: poner true/true y cargar CA institucional.
        CURLOPT_SSL_VERIFYPEER  => false,
        CURLOPT_SSL_VERIFYHOST  => false,
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        $errno  = curl_errno($ch);
        $errmsg = curl_error($ch);
        curl_close($ch);
        throw new Exception("No se pudo contactar al servidor central ($errno: $errmsg)");
    }

    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, true);

    if ($httpcode < 200 || $httpcode >= 300) {
        // credenciales malas / usuario bloqueado / etc.
        $msg = "El usuario o la contraseña son incorrectos.";
        if (is_array($data)) {
            if (!empty($data['message'])) $msg = $data['message'];
            else if (!empty($data['error'])) $msg = $data['error'];
        }
        throw new Exception($msg);
    }

    if (!is_array($data)) {
        throw new Exception("Respuesta inválida del servidor central (no es JSON).");
    }

    if (
        !isset($data['access_token']) &&
        !isset($data['token']) &&
        !isset($data['jwt'])
    ) {
        throw new Exception("El servidor central no devolvió un token de sesión.");
    }

    return $data;
}


/**
 * 2) Consultar perfil al CPS con el token Bearer.
 * Confirmado: https://apicps.ejercito.mil.ar/api/v1/user/profile
 *
 * Debe devolver datos del usuario autenticado (dni, grado, unidad, etc.)
 * Ej: { "dni": "...", "first_name": "...", "last_name": "...", "rank": "...", ... }
 */
function cps_get_profile(string $bearerToken): array {
    $CPS_PROFILE_URL = "https://apicps.ejercito.mil.ar/api/v1/user/profile";

    $ch = curl_init($CPS_PROFILE_URL);
    curl_setopt_array($ch, [
        CURLOPT_HTTPGET        => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Accept: application/json',
            'Authorization: Bearer ' . $bearerToken,
        ],
        // DESARROLLO: SSL off
        CURLOPT_SSL_VERIFYPEER  => false,
        CURLOPT_SSL_VERIFYHOST  => false,
    ]);

    $resp = curl_exec($ch);
    if ($resp === false) {
        $errno  = curl_errno($ch);
        $errmsg = curl_error($ch);
        curl_close($ch);
        throw new Exception("No se pudo obtener el perfil del servidor central ($errno: $errmsg)");
    }

    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpcode < 200 || $httpcode >= 300) {
        // Esto incluye 401/403 → token no válido para perfil
        throw new Exception("Token inválido o expirado al consultar perfil.");
    }

    $profile = json_decode($resp, true);
    if (!is_array($profile)) {
        throw new Exception("El perfil devuelto no es JSON válido.");
    }

    return $profile;
}


/**
 * 3) Resolver rol interno a partir del DNI (o username si no hay DNI).
 *    Usa tu conexión db() y opcionalmente tabla roles_locales(dni, rol_app).
 *    Si la tabla no existe aún, devolvemos 'admin' como fallback para que puedas entrar.
 */
function map_local_role(string $dniOrUser): string {
    require_once __DIR__ . '/php/db.php'; // tu conexión PDO: db()

    $pdo = db();

    // ¿existe la tabla roles_locales?
    try {
        $check = $pdo->query("SHOW TABLES LIKE 'roles_locales'");
        $exists = $check && $check->fetchColumn();
    } catch (Throwable $e) {
        $exists = false;
    }

    if (!$exists) {
        // Mientras no creaste la tabla, te doy admin para que no te quedes afuera
        return 'admin';
    }

    // Buscamos por dni
    $stmt = $pdo->prepare("SELECT rol_app FROM roles_locales WHERE dni = ? LIMIT 1");
    $stmt->execute([$dniOrUser]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && !empty($row['rol_app'])) {
        return $row['rol_app'];
    }

    // Si la tabla existe pero ese dni no está cargado, rol básico.
    return 'usuario';
}


/**
 * 4) Esta es la función principal que usa login.php.
 *
 * - Loguea contra CPS -> obtiene token
 * - Con token pide perfil
 * - Define rol interno
 * - Guarda todo en $_SESSION
 *
 * Devuelve true si todo ok, Exception si cualquier paso falla.
 */
function auth_login_cps(string $username, string $password): bool {
    // Paso 1: pedir token
    $data = cps_authenticate($username, $password);

    // token puede venir como access_token, token o jwt
    $token = $data['access_token']
        ?? $data['token']
        ?? $data['jwt']
        ?? null;

    if ($token === null) {
        throw new Exception("No se pudo recuperar el token del servidor central.");
    }

    // Paso 2: pedir perfil con el token
    $perfil = cps_get_profile($token);
    // Ej esperado:
    // {
    //   "dni": 41742406,
    //   "first_name": "NESTOR GABRIEL",
    //   "last_name": "ROJAS",
    //   "rank": "ST SCD",
    //   "unit_description": "Batallón de Comunicaciones 602",
    //   "work_email": "...",
    //   ...
    // }

    // --- normalizamos tipos para que todo sea string ---
    $dniRaw = $perfil['dni'] ?? '';
    // forzamos a string siempre, aunque venga como int
    $dni = ($dniRaw === null) ? '' : (string)$dniRaw;

    $firstName = $perfil['first_name'] ?? $perfil['nombre']   ?? '';
    $lastName  = $perfil['last_name']  ?? $perfil['apellido'] ?? '';

    $fullName = trim($firstName . ' ' . $lastName);

    $rank = $perfil['rank'] ?? $perfil['grado'] ?? '';
    $unit = $perfil['unit_description']
         ?? $perfil['unit']
         ?? $perfil['unidad']
         ?? '';

    $emailLab = $perfil['work_email']
             ?? $perfil['email_laboral']
             ?? $perfil['email']
             ?? '';

    $resolvedUsername = $perfil['username'] ?? $perfil['user'] ?? $username;
    $resolvedUsername = (string)$resolvedUsername;

    // Paso 3: mapa de rol interno
    // Si no hay DNI usable, usamos el username. Ambos ya son string garantizado.
    $keyForRole = ($dni !== '' ? $dni : $resolvedUsername);
    $rolLocal = map_local_role($keyForRole);

    // Paso 4: guardar en sesión
    $_SESSION['cps_token'] = $token;
    $_SESSION['user'] = [
        'dni'       => $dni,
        'username'  => $resolvedUsername,
        'full_name' => ($fullName !== '' ? $fullName : $resolvedUsername),
        'rank'      => $rank,
        'unit'      => $unit,
        'email_lab' => $emailLab,
        'role_app'  => $rolLocal,
    ];

    return true;
}
