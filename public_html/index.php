<?php
require __DIR__ . '/app/config/config.php';

redirect(is_logged_in() ? app_path('dashboard.php') : app_path('login.php'));
