<?php
/**
 * Core helper functions: sanitization, validation, settings, logs,
 * CRUD helpers, stats, and utility functions.
 */
require_once __DIR__ . '/database.php';

/* ----------------------- Output & input helpers ----------------------- */

/** Escape output for HTML (XSS protection). */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/** Sanitize a raw string input. */
function clean(?string $value): string
{
    return trim($value ?? '');
}

/** Generate a CSRF token and store in session. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Render a hidden CSRF input. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/** Verify a CSRF token (request vs session). */
function csrf_verify(?string $token): bool
{
    return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/* ----------------------- Validation ----------------------------------- */

function validate_email(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validate_phone(string $phone): bool
{
    return (bool) preg_match('/^[0-9\+\-\(\)\s]{7,20}$/', $phone);
}

/** Return password strength score 0-4. */
function password_strength(string $pw): int
{
    $score = 0;
    if (strlen($pw) >= 8) $score++;
    if (preg_match('/[A-Z]/', $pw)) $score++;
    if (preg_match('/[0-9]/', $pw)) $score++;
    if (preg_match('/[^A-Za-z0-9]/', $pw)) $score++;
    return $score;
}

/* ----------------------- Settings ------------------------------------- */

function get_setting(string $key, $default = null)
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (db()->query("SELECT setting_key, setting_value FROM settings") as $r) {
            $cache[$r['setting_key']] = $r['setting_value'];
        }
    }
    return $cache[$key] ?? $default;
}

function update_setting(string $key, string $value): void
{
    $stmt = db()->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?)
        ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
    $stmt->execute([$key, $value]);
}

/* ----------------------- Activity logs -------------------------------- */

function log_activity(string $action, ?int $userId = null, string $userType = 'admin'): void
{
    $stmt = db()->prepare("INSERT INTO activity_logs (user_id, user_type, action) VALUES (?,?,?)");
    $stmt->execute([$userId, $userType, $action]);
}

/* ----------------------- Members helpers ------------------------------ */

/** Generate a unique member number like M-2026-0001 */
function generate_member_no(): string
{
    $year = date('Y');
    $stmt = db()->prepare("SELECT COUNT(*) AS c FROM members WHERE member_no LIKE ?");
    $stmt->execute(["M-$year-%"]);
    $count = (int) $stmt->fetchColumn();
    return sprintf("M-%s-%04d", $year, $count + 1);
}

/** Generate a unique receipt number like RCP-20260727-0001 */
function generate_receipt_no(): string
{
    $prefix = 'RCP-' . date('Ymd') . '-';
    $stmt = db()->prepare("SELECT COUNT(*) FROM payments WHERE receipt_no LIKE ?");
    $stmt->execute(["RCP-" . date('Ymd') . "-%"]);
    $count = (int) $stmt->fetchColumn();
    return $prefix . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
}

/* ----------------------- Stats for dashboard -------------------------- */

function stat_total_members(): int
{
    return (int) db()->query("SELECT COUNT(*) FROM members")->fetchColumn();
}

function stat_active_members(): int
{
    $stmt = db()->prepare("SELECT COUNT(*) FROM members WHERE status='active'");
    $stmt->execute();
    return (int) $stmt->fetchColumn();
}

function stat_expired_memberships(): int
{
    $stmt = db()->prepare("SELECT COUNT(*) FROM members WHERE expiry_date < CURDATE() AND status<>'suspended'");
    $stmt->execute();
    return (int) $stmt->fetchColumn();
}

function stat_today_birthdays(): int
{
    $stmt = db()->prepare("SELECT COUNT(*) FROM members WHERE MONTH(date_of_birth)=MONTH(CURDATE()) AND DAY(date_of_birth)=DAY(CURDATE())");
    $stmt->execute();
    return (int) $stmt->fetchColumn();
}

function stat_upcoming_events(): int
{
    $stmt = db()->prepare("SELECT COUNT(*) FROM events WHERE event_date >= CURDATE() AND status='upcoming'");
    $stmt->execute();
    return (int) $stmt->fetchColumn();
}

function stat_monthly_revenue(): float
{
    $stmt = db()->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='paid' AND MONTH(payment_date)=MONTH(CURDATE()) AND YEAR(payment_date)=YEAR(CURDATE())");
    $stmt->execute();
    return (float) $stmt->fetchColumn();
}

function stat_attendance_today(): int
{
    $stmt = db()->prepare("SELECT COUNT(*) FROM attendance WHERE check_date=CURDATE()");
    $stmt->execute();
    return (int) $stmt->fetchColumn();
}

function stat_outstanding_payments(): float
{
    $stmt = db()->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='pending'");
    $stmt->execute();
    return (float) $stmt->fetchColumn();
}

function recent_activities(int $limit = 8): array
{
    $stmt = db()->prepare("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT ?");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/* ----------------------- Notification helpers ------------------------- */

function notifications_for_admin(): array
{
    $out = [];
    // Expiring soon (within 7 days)
    $stmt = db()->prepare("SELECT full_name, expiry_date FROM members WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) ORDER BY expiry_date ASC LIMIT 10");
    $stmt->execute();
    foreach ($stmt->fetchAll() as $r) {
        $out[] = ['type' => 'expiry', 'text' => $r['full_name'] . ' expires ' . $r['expiry_date']];
    }
    // Pending payments
    $stmt = db()->prepare("SELECT COUNT(*) FROM payments WHERE status='pending'");
    $stmt->execute();
    $pending = (int) $stmt->fetchColumn();
    if ($pending > 0) {
        $out[] = ['type' => 'payment', 'text' => "$pending pending payment(s)"];
    }
    // Upcoming events (next 7 days)
    $stmt = db()->prepare("SELECT title, event_date FROM events WHERE event_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) ORDER BY event_date ASC LIMIT 10");
    $stmt->execute();
    foreach ($stmt->fetchAll() as $r) {
        $out[] = ['type' => 'event', 'text' => $r['title'] . ' on ' . $r['event_date']];
    }
    return $out;
}

function notifications_for_member(int $memberId): array
{
    $out = [];
    $m = db()->prepare("SELECT full_name, expiry_date FROM members WHERE id=?");
    $m->execute([$memberId]);
    $member = $m->fetch();
    if ($member && $member['expiry_date']) {
        $days = (strtotime($member['expiry_date']) - time()) / 86400;
        if ($days <= 7) {
            $out[] = ['type' => 'expiry', 'text' => 'Your membership expires on ' . $member['expiry_date']];
        }
    }
    $ev = db()->prepare("SELECT e.title, e.event_date FROM events e
        INNER JOIN event_registration r ON r.event_id=e.id
        WHERE r.member_id=? AND e.event_date >= CURDATE() ORDER BY e.event_date ASC LIMIT 10");
    $ev->execute([$memberId]);
    foreach ($ev->fetchAll() as $r) {
        $out[] = ['type' => 'event', 'text' => $r['title'] . ' on ' . $r['event_date']];
    }
    return $out;
}

/* ----------------------- Date / format helpers ------------------------ */

function fmt_money($amount): string
{
    $cur = get_setting('currency', '$');
    return $cur . number_format((float) $amount, 2);
}

function fmt_date(?string $date, string $format = 'd M Y'): string
{
    if (!$date) return '-';
    $ts = strtotime($date);
    return $ts ? date($format, $ts) : '-';
}

function relative_time(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 2592000) return floor($diff / 86400) . 'd ago';
    return date('d M Y', strtotime($datetime));
}

/* ----------------------- File upload helper --------------------------- */

function handle_upload(string $field, string $old = null): ?string
{
    if (empty($_FILES[$field]['name']) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return $old;
    }
    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        return $old;
    }
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        return $old;
    }
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0775, true);
    }
    $name = uniqid('img_', true) . '.' . $ext;
    $dest = UPLOAD_DIR . $name;
    if (move_uploaded_file($_FILES[$field]['tmp_name'], $dest)) {
        if ($old && is_file(UPLOAD_DIR . basename($old))) {
            @unlink(UPLOAD_DIR . basename($old));
        }
        return UPLOAD_URL . $name;
    }
    return $old;
}

/* ----------------------- JSON response -------------------------------- */

function json_response($data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Read JSON body of an AJAX request. */
function json_input(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}
