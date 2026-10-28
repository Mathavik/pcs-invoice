<?php
function ensureSessionStarted()
{
    if (session_status() === PHP_SESSION_NONE) {
        $https = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
                && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');

        if (!headers_sent()) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => $https,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');

        session_start();
    }

    if (!headers_sent()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
}

/**
 * Email last used on this device, used only to pre-fill the login and
 * forgot-password forms. Never contains a password or token.
 */
function rememberedEmail(): string
{
    $email = $_COOKIE['pcs_last_email'] ?? '';

    if (!is_string($email)) {
        return '';
    }

    $email = strtolower(trim($email));

    return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
}

function rememberEmailCookie(string $email): void
{
    $email = strtolower(trim($email));

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || headers_sent()) {
        return;
    }

    $https = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
            && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');

    setcookie('pcs_last_email', $email, [
        'expires' => time() + 60 * 60 * 24 * 30,
        'path' => '/',
        'domain' => '',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    $_COOKIE['pcs_last_email'] = $email;
}
