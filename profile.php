<?php
/**
 * Личный кабинет пользователя (защищённый компонент).
 * показывает данные пользователя и его отзывы.
 */

require_once __DIR__ . '/includes/header.php';

requireLogin();

$pdo = Database::getConnection();
$userId = currentUserId();

// данные пользователя
$userStmt = $pdo->prepare('SELECT username, email, role, created_at FROM users WHERE id = ?');
$userStmt->execute([$userId]);
$user = $userStmt->fetch();

// отзывы пользователя
$reviewsStmt = $pdo->prepare("
    SELECT r.id, r.rating, r.comment, r.created_at,
           f.id AS film_id, f.title AS film_title
    FROM reviews r
    JOIN films f ON f.id = r.film_id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
");
$reviewsStmt->execute([$userId]);
$reviews = $reviewsStmt->fetchAll();

$pageTitle = 'Личный кабинет';
?>

<h1>Личный кабинет</h1>

<div class="form-card" style="max-width: 100%;">
    <h2>Мои данные</h2>
    <p><strong>Логин:</strong> <?= e($user['username']) ?></p>
    <p><strong>Email:</strong> <?= e($user['email']) ?></p>
    <p><strong>Роль:</strong> <?= $user['role'] === 'admin' ? '👑 Администратор' : 'Пользователь' ?></p>
    <p><strong>Дата регистрации:</strong> <?= e(date('d.m.Y', strtotime($user['created_at']))) ?></p>
</div>

<h2 style="margin-top: 25px;">Мои отзывы (<?= count($reviews) ?>)</h2>

<?php if (empty($reviews)): ?>
    <div class="alert alert-info">
        Вы пока не оставили ни одного отзыва.
        <a href="<?= SITE_URL ?>/films.php">Перейти в каталог</a>.
    </div>
<?php else: ?>
    <?php foreach ($reviews as $rev): ?>
        <div class="review-card">
            <div class="review-head">
                <span class="review-author">
                    <a href="<?= SITE_URL ?>/film.php?id=<?= (int)$rev['film_id'] ?>">
                        <?= e($rev['film_title']) ?>
                    </a>
                </span>
                <span class="review-rating">⭐ <?= (int)$rev['rating'] ?>/10</span>
            </div>
            <div class="review-date"><?= e(date('d.m.Y H:i', strtotime($rev['created_at']))) ?></div>
            <?php if (!empty($rev['comment'])): ?>
                <p class="review-text"><?= nl2br(e($rev['comment'])) ?></p>
            <?php endif; ?>
            <div style="margin-top: 8px;">
                <a class="btn btn-small btn-secondary" href="<?= SITE_URL ?>/review_edit.php?id=<?= (int)$rev['id'] ?>">
                    Редактировать
                </a>
                <a class="btn btn-small btn-danger" href="<?= SITE_URL ?>/review_delete.php?id=<?= (int)$rev['id'] ?>"
                   onclick="return confirm('Удалить отзыв?');">Удалить</a>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
