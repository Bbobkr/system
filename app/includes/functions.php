<?php
declare(strict_types=1);

/** تعقيم المخرجات لمنع XSS */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/** اللغة الحالية للجلسة (أو لغة النظام الافتراضية من الإعدادات إن لم تُحدَّد بعد) */
function current_lang(): string
{
    $lang = $_SESSION['lang'] ?? setting_get('default_language', 'ar');
    return in_array($lang, ['ar', 'en'], true) ? $lang : 'ar';
}

/** يقرأ قيمة إعداد من جدول settings مع تخزين مؤقت داخل الطلب الواحد */
function setting_get(string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        try {
            $cache = db()->query('SELECT setting_key, setting_value FROM settings')
                ->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (Throwable $e) {
            $cache = [];
        }
    }
    return $cache[$key] ?? $default;
}

function setting_set(string $key, string $value): void
{
    $stmt = db()->prepare(
        'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );
    $stmt->execute([$key, $value]);
}

/** ترجمة مفتاح إلى نص باللغة الحالية */
function t(string $key): string
{
    static $strings = null;
    if ($strings === null) {
        $strings = require APP_DIR . '/lang/' . current_lang() . '.php';
    }
    return $strings[$key] ?? $key;
}

function is_rtl(): bool
{
    return current_lang() === 'ar';
}

function redirect(string $path): never
{
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/');
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
        header('Location: ' . $path);
    } else {
        header('Location: ' . $path);
    }
    exit;
}

function format_money(float $amount): string
{
    return number_format($amount, 2) . ' ' . setting_get('currency', DEFAULT_CURRENCY);
}

function format_date(?string $datetime, string $format = 'Y-m-d H:i'): string
{
    if (!$datetime) {
        return '';
    }
    $ts = strtotime($datetime);
    return $ts ? date($format, $ts) : '';
}

/** توليد رقم فاتورة تسلسلي بادئته $prefix (مثال: S-000123) */
function generate_invoice_number(string $prefix, string $table): string
{
    $stmt = db()->query("SELECT COUNT(*) AS c FROM {$table}");
    $count = (int)$stmt->fetch()['c'] + 1;
    return $prefix . str_pad((string)$count, 6, '0', STR_PAD_LEFT);
}

function old(string $key, string $default = ''): string
{
    return e($_SESSION['_old'][$key] ?? $default);
}

function clear_old(): void
{
    unset($_SESSION['_old']);
}

function input(string $key, string $default = ''): string
{
    return trim((string)($_POST[$key] ?? $default));
}

function input_float(string $key, float $default = 0): float
{
    $v = str_replace(',', '', (string)($_POST[$key] ?? ''));
    return is_numeric($v) ? (float)$v : $default;
}

function input_int(string $key, int $default = 0): int
{
    $v = $_POST[$key] ?? '';
    return is_numeric($v) ? (int)$v : $default;
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/** يمنع تنفيذ هذه الدالة إلا عبر طلب POST (حماية بسيطة إضافية) */
function require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        die('Method Not Allowed');
    }
}

/**
 * يتحقق من صورة مرفوعة عبر $_FILES[$field] ويخزّنها باسم عشوائي داخل مجلد الرفع.
 * يعيد المسار النسبي (لاستخدامه في عمود image_path) أو null إذا لم يُرفع ملف.
 * يرمي RuntimeException إذا كان الملف غير صالح (نوع/حجم).
 */
function handle_product_image_upload(string $field): ?string
{
    if (empty($_FILES[$field]['name']) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload error');
    }

    $tmpPath = $_FILES[$field]['tmp_name'];
    $maxSize = 2 * 1024 * 1024;
    if ($_FILES[$field]['size'] > $maxSize) {
        throw new RuntimeException('File too large (max 2MB)');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $tmpPath);
    finfo_close($finfo);

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Unsupported image type');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    $destDir = APP_ROOT . '/public_html/assets/uploads/products';
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }
    if (!move_uploaded_file($tmpPath, $destDir . '/' . $filename)) {
        throw new RuntimeException('Failed to save uploaded file');
    }

    return 'assets/uploads/products/' . $filename;
}
