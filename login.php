<?php
/**
 * Страница входа в систему.
 */

require_once __DIR__ . '/includes/header.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$errors = [];
$oldUsername = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!checkCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Неверный CSRF-токен.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $oldUsername = $username;

        if ($username === '' || $password === '') {
            $errors[] = 'Введите логин и пароль.';
        } else {
            $user = loginUser($username, $password);
            if ($user) {
                $_SESSION['flash_success'] = 'Добро пожаловать, ' . $user['username'] . '!';
                if ($user['role'] === 'admin') {
                    redirect('admin/index.php');
                }
                redirect('index.php');
            } else {
                $errors[] = 'Неверный логин или пароль.';
            }
        }
    }
}

$pageTitle = 'Вход';
?>

<div class="form-card">
    <h1>Вход в систему</h1>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $err): ?>
                <div><?= e($err) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

        <div class="form-group">
            <label for="username">Логин</label>
            <input type="text" id="username" name="username" value="<?= e($oldUsername) ?>" required>
        </div>

        <div class="form-group">
            <label for="password">Пароль</label>
            <input type="password" id="password" name="password" required>
        </div>

        <button type="submit" class="btn">Войти</button>
        <a href="<?= SITE_URL ?>/register.php" class="btn btn-secondary">Регистрация</a>
    </form>

    <p style="margin-top: 18px; font-size: 14px; color: #6c7a89;">
        Тестовый администратор: <code>admin</code> / <code>admin123</code><br>
        Тестовый пользователь: <code>demo</code> / <code>demo123</code>
    </p>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
