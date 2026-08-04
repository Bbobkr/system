<?php
/** @var string $pageTitle مُعرَّف اختياريًا قبل تضمين هذا الملف */
$pageTitle = $pageTitle ?? t('app_name');
$lang = current_lang();
$dir = is_rtl() ? 'rtl' : 'ltr';
$user = current_user();
?>
<!doctype html>
<html lang="<?= e($lang) ?>" dir="<?= $dir ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> · <?= e(t('app_name')) ?></title>
<link rel="stylesheet" href="<?= e(app_path('assets/css/app.css')) ?>">
<?php if (is_rtl()): ?><link rel="stylesheet" href="<?= e(app_path('assets/css/rtl.css')) ?>"><?php endif; ?>
<script src="<?= e(app_path('assets/js/app.js')) ?>"></script>
</head>
<body class="<?= $user ? 'has-sidebar' : 'auth-page' ?>">
<?php if ($user): ?>
<div class="layout">
    <aside class="sidebar">
        <div class="sidebar-brand"><?= e(t('app_name')) ?></div>
        <nav class="sidebar-nav">
            <a href="<?= e(app_path('dashboard.php')) ?>"><?= e(t('nav_dashboard')) ?></a>
            <?php if (has_permission('products')): ?><a href="<?= e(app_path('products/index.php')) ?>"><?= e(t('nav_products')) ?></a><?php endif; ?>
            <?php if (has_permission('categories')): ?><a href="<?= e(app_path('products/categories.php')) ?>"><?= e(t('nav_categories')) ?></a><?php endif; ?>
            <?php if (has_permission('branches')): ?><a href="<?= e(app_path('branches/index.php')) ?>"><?= e(t('nav_branches')) ?></a><?php endif; ?>
            <?php if (has_permission('stock')): ?><a href="<?= e(app_path('stock/movements.php')) ?>"><?= e(t('nav_stock')) ?></a><?php endif; ?>
            <?php if (has_permission('customers')): ?><a href="<?= e(app_path('customers/index.php')) ?>"><?= e(t('nav_customers')) ?></a><?php endif; ?>
            <?php if (has_permission('suppliers')): ?><a href="<?= e(app_path('suppliers/index.php')) ?>"><?= e(t('nav_suppliers')) ?></a><?php endif; ?>
            <?php if (has_permission('sales')): ?><a href="<?= e(app_path('sales/index.php')) ?>"><?= e(t('nav_sales')) ?></a><?php endif; ?>
            <?php if (has_permission('purchases')): ?><a href="<?= e(app_path('purchases/index.php')) ?>"><?= e(t('nav_purchases')) ?></a><?php endif; ?>
            <?php if (has_permission('products')): ?><a href="<?= e(app_path('barcode/generate.php')) ?>"><?= e(t('nav_barcode')) ?></a><?php endif; ?>
            <?php if (has_permission('reports')): ?><a href="<?= e(app_path('reports/sales.php')) ?>"><?= e(t('nav_reports')) ?></a><?php endif; ?>
            <?php if (has_permission('users')): ?><a href="<?= e(app_path('users/index.php')) ?>"><?= e(t('nav_users')) ?></a><?php endif; ?>
            <?php if (has_permission('settings')): ?><a href="<?= e(app_path('settings/index.php')) ?>"><?= e(t('nav_settings')) ?></a><?php endif; ?>
        </nav>
    </aside>
    <div class="main">
        <header class="topbar">
            <div class="topbar-title"><?= e($pageTitle) ?></div>
            <div class="topbar-user">
                <form method="post" action="<?= e(app_path('settings/language.php')) ?>" class="lang-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="lang" value="<?= $lang === 'ar' ? 'en' : 'ar' ?>">
                    <input type="hidden" name="next" value="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>">
                    <button type="submit" class="btn-link"><?= $lang === 'ar' ? 'English' : 'العربية' ?></button>
                </form>
                <span><?= e(t('welcome')) ?>, <?= e($user['full_name']) ?> (<?= e($lang === 'ar' ? $user['role_name_ar'] : $user['role_name']) ?>)</span>
                <a href="<?= e(app_path('logout.php')) ?>" class="btn-link"><?= e(t('nav_logout')) ?></a>
            </div>
        </header>
        <main class="content">
            <?php foreach (flash_pull() as $f): ?>
                <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
            <?php endforeach; ?>
<?php else: ?>
<main class="auth-main">
<?php endif; ?>
