<?php
declare(strict_types=1);

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return isset($_SESSION['user']);
}

function is_admin(): bool
{
    return (current_user()['role_slug'] ?? '') === 'admin';
}

/** الفرع الذي يقيّد به المستخدم (null = يرى كل الفروع، للأدمن/المدير) */
function user_branch_id(): ?int
{
    $u = current_user();
    if (!$u) {
        return null;
    }
    if (in_array($u['role_slug'], ['admin', 'manager'], true)) {
        return null;
    }
    return $u['branch_id'] !== null ? (int)$u['branch_id'] : null;
}

function require_login(): void
{
    if (!is_logged_in()) {
        $_SESSION['_redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '';
        redirect(app_path('login.php'));
    }
}

/**
 * يتحقق من صلاحية المستخدم لوحدة معينة وإجراء معين.
 * $action: view|create|edit|delete
 */
function has_permission(string $module, string $action = 'view'): bool
{
    if (!is_logged_in()) {
        return false;
    }
    if (is_admin()) {
        return true;
    }

    $perms = $_SESSION['permissions'] ?? [];
    if (!isset($perms[$module])) {
        return false;
    }
    $col = 'can_' . $action;
    return !empty($perms[$module][$col]);
}

function require_permission(string $module, string $action = 'view'): void
{
    require_login();
    if (!has_permission($module, $action)) {
        http_response_code(403);
        require APP_DIR . '/includes/header.php';
        echo '<div class="alert alert-error">' . e(t('no_permission')) . '</div>';
        require APP_DIR . '/includes/footer.php';
        exit;
    }
}

/** يحمّل صلاحيات دور المستخدم في الجلسة بعد تسجيل الدخول */
function load_user_permissions(int $roleId): array
{
    $stmt = db()->prepare('SELECT module, can_view, can_create, can_edit, can_delete FROM permissions WHERE role_id = ?');
    $stmt->execute([$roleId]);
    $perms = [];
    foreach ($stmt->fetchAll() as $row) {
        $perms[$row['module']] = [
            'can_view' => (bool)$row['can_view'],
            'can_create' => (bool)$row['can_create'],
            'can_edit' => (bool)$row['can_edit'],
            'can_delete' => (bool)$row['can_delete'],
        ];
    }
    return $perms;
}

function attempt_login(string $username, string $password): bool
{
    $stmt = db()->prepare(
        'SELECT u.id, u.username, u.password_hash, u.full_name, u.role_id, u.branch_id, u.is_active,
                r.slug AS role_slug, r.name AS role_name, r.name_ar AS role_name_ar
         FROM users u JOIN roles r ON r.id = u.role_id
         WHERE u.username = ? LIMIT 1'
    );
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || !$user['is_active'] || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'username' => $user['username'],
        'full_name' => $user['full_name'],
        'role_id' => (int)$user['role_id'],
        'role_slug' => $user['role_slug'],
        'role_name' => $user['role_name'],
        'role_name_ar' => $user['role_name_ar'],
        'branch_id' => $user['branch_id'] !== null ? (int)$user['branch_id'] : null,
    ];
    $_SESSION['permissions'] = load_user_permissions((int)$user['role_id']);

    db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([$user['id']]);
    log_activity('login', 'auth', (int)$user['id'], 'تسجيل دخول');

    return true;
}

function logout(): void
{
    if (is_logged_in()) {
        log_activity('logout', 'auth', current_user()['id'], 'تسجيل خروج');
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function log_activity(string $action, string $module, ?int $recordId = null, string $details = ''): void
{
    try {
        $stmt = db()->prepare(
            'INSERT INTO activity_log (user_id, action, module, record_id, details, ip_address, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([
            current_user()['id'] ?? null,
            $action,
            $module,
            $recordId,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    } catch (Throwable $e) {
        error_log('activity_log failed: ' . $e->getMessage());
    }
}

/** يبني مسارًا نسبيًا لصفحة داخل public_html بغض النظر عن عمق المجلد الحالي */
function app_path(string $path): string
{
    return base_url() . '/' . ltrim($path, '/');
}

function base_url(): string
{
    if (defined('APP_URL') && APP_URL !== '') {
        return APP_URL;
    }
    return '';
}
