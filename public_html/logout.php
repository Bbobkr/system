<?php
require __DIR__ . '/app/config/config.php';

logout();
redirect(app_path('login.php'));
