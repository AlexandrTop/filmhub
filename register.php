<?php
/**
 * Регистрация нового пользователя.
 * включает серверную валидацию + клиентская валидация в js.
 */

require_once __DIR__ . '/includes/header.php';

// если уже залогинен - на главную
if (isLoggedIn()) {
    redirect('index.php');
}

$errors = [];
$old    = ['username' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // защита от csrf
    if (!checkCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Неверный CSRF-токен. Попробуйте отправить форму ещё раз.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['password_confirm'] ?? '';

        $old['username'] = $username;
        $old['email']    = $email;

        // серверная валидация
        if (mb_strlen($username) < 3 || mb_strlen($username) > 50) {
            $errors[] = 'Логин должен быть от 3 до 50 символов.';
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = 'Логин может содержать только латиницу, цифры и нижнее подчёркивание.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Введите корректный email.';
        }
        if (mb_strlen($password) < MIN_PASSWORD_LENGTH) {
            $errors[] = 'Пароль должен быть не короче ' . MIN_PASSWORD_LENGTH . ' символов.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Пароли не совпадают.';
        }

        // если ошибок нет - проверим уникальность
        if (empty($errors)) {
            $pdo = Database::getConnection();
            $check = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
            $check->execute([$username, $email]);
            if ($check->fetch()) {
                $errors[] = 'Пользователь с таким логином или email уже зарегистрирован.';
            }
        }

        // создаём пользователя
        if (empty($errors)) {
            $pdo = Database::getConnection();
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $insert = $pdo->prepare('INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)');
            $insert->execute([$username, $email, $hash, 'user']);

            $_SESSION['flash_success'] = 'Регистрация прошла успешно! Войдите в свой аккаунт.';
            redirect('login.php');
        }
    }
}

$pageTitle = 'Регистрация';
?>

<div class="form-card">
    <h1>Регистрация</h1>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $err): ?>
                <div><?= e($err) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form id="register-form" method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

        <div class="form-group">
            <label for="username">Логин *</label>
            <input type="text" id="username" name="username" value="<?= e($old['username']) ?>"
                   required minlength="3" maxlength="50">
        </div>

        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" value="<?= e($old['email']) ?>"
                   required maxlength="100">
        </div>

        <div class="form-group">
            <label for="password">Пароль * (минимум <?= MIN_PASSWORD_LENGTH ?> символов)</label>
            <input type="password" id="password" name="password" required minlength="<?= MIN_PASSWORD_LENGTH ?>">
        </div>

        <div class="form-group">
            <label for="password_confirm">Повторите пароль *</label>
            <input type="password" id="password_confirm" name="password_confirm" required>
        </div>

        <button type="submit" class="btn">Зарегистрироваться</button>
        <a href="<?= SITE_URL ?>/login.php" class="btn btn-secondary">Уже есть аккаунт</a>
    </form>
</div>

<script src="<?= SITE_URL ?>/assets/js/validation.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
