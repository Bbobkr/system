<?php
require __DIR__ . '/../../app/config/config.php';
require_post();
csrf_verify();

$lang = $_POST['lang'] ?? 'ar';
$_SESSION['lang'] = in_array($lang, ['ar', 'en'], true) ? $lang : 'ar';

$next = $_POST['next'] ?? '';
redirect($next !== '' ? $next : app_path('dashboard.php'));
