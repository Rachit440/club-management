<?php
/**
 * Authentication, session management and role-based access control.
 */
require_once __DIR__ . '/functions.php';

/** Regenerate session id to prevent fixation. */
function regenerate_session(): void
{
    session_regenerate_id(true);
}

/** Check timeout and logout if exceeded. */
function check_session_timeout(): void
{
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        logout();
    }
    $_SESSION['last_activity'] = time();
}

/** Attempt login by email + password. Returns array [ok, error]. */
function attempt_login(string $email, string $password): array
{
    $email = clean($email);
    if (!validate_email($email)) {
        return [false, 'Invalid email format.'];
    }
    // Check admins table
    $stmt = db()->prepare("SELECT * FROM admins WHERE email=? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => 'admin',
            'avatar' => $user['avatar'] ?? null,
        ];
        regenerate_session();
        log_activity('Admin logged in', (int) $user['id'], 'admin');
        return [true, null];
    }
    // Check members table
    $stmt = db()->prepare("SELECT * FROM members WHERE email=? LIMIT 1");
    $stmt->execute([$email]);
    $member = $stmt->fetch();
    if ($member && password_verify($password, $member['password'])) {
        if ($member['status'] === 'suspended') {
            return [false, 'Your account is suspended. Contact the administrator.'];
        }
        $_SESSION['user'] = [
            'id' => (int) $member['id'],
            'name' => $member['full_name'],
            'email' => $member['email'],
            'role' => 'member',
            'avatar' => $member['photo'] ?? null,
        ];
        regenerate_session();
        log_activity('Member logged in', (int) $member['id'], 'member');
        return [true, null];
    }
    return [false, 'Invalid credentials.'];
}

/** True if a user is logged in. */
function is_logged_in(): bool
{
    return !empty($_SESSION['user']);
}

/** Require login, else redirect. */
function require_login(): void
{
    check_session_timeout();
    if (!is_logged_in()) {
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
}

/** Require a specific role. */
function require_role(string $role): void
{
    require_login();
    if (($_SESSION['user']['role'] ?? '') !== $role) {
        header('Location: ' . APP_URL . '/dashboard.php');
        exit;
    }
}

/** Current logged in user array or null. */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

/** Redirect to a URL. */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/** Destroy session and redirect to login. */
function logout(): void
{
    if (!empty($_SESSION['user'])) {
        log_activity('User logged out', $_SESSION['user']['id'] ?? null, $_SESSION['user']['role'] ?? 'admin');
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    redirect(APP_URL . '/login.php');
}
