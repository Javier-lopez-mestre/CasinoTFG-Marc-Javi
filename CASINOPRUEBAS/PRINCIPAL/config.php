<?php
// Configuración centralizada (usar variables de entorno preferentemente)

declare(strict_types=1);

// ======= PHP/DEBUG =======
$ENV = getenv('APP_ENV') ?: 'dev';
$DEBUG = (getenv('APP_DEBUG') ?: '0') === '1';

if ($DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// ======= DB =======
$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_USER = getenv('DB_USER') ?: 'casino_user';
$DB_PASS = getenv('DB_PASS') ?: 'superlocal';
$DB_NAME = getenv('DB_NAME') ?: 'casino';

// ======= Sesión =======
$SESSION_COOKIE_SECURE = (getenv('SESSION_COOKIE_SECURE') ?: '0') === '1';
$SESSION_COOKIE_SAMESITE = getenv('SESSION_COOKIE_SAMESITE') ?: 'Lax'; // Lax|Strict|None

// ======= Stripe =======
// Importante: NO hardcodear claves en el repo.
$STRIPE_SECRET_KEY = getenv('STRIPE_SECRET_KEY') ?: '';
$STRIPE_WEBHOOK_SECRET = getenv('STRIPE_WEBHOOK_SECRET') ?: '';

// ======= Seguridad =======
// Ajusta si usas HTTPS.
if ($SESSION_COOKIE_SECURE) {
    ini_set('session.cookie_secure', '1');
}

$cookieSameSite = $SESSION_COOKIE_SAMESITE;
if (!empty($cookieSameSite)) {
    // PHP >= 7.3 soporta samesite en session cookie via ini_set (según build)
    @ini_set('session.cookie_samesite', $cookieSameSite);
}

?>
