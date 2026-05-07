<?php
/**
 * Выход из системы - очищает сессию и перенаправляет на главную.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

logoutUser();

session_start();
$_SESSION['flash_success'] = 'Вы вышли из системы.';

redirect('index.php');
