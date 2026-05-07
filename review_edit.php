<?php
/**
 * Редактирование собственного отзыва.
 */

require_once __DIR__ . '/includes/header.php';

requireLogin();

$pdo = Database::getConnection();
$reviewId = getInt($_GET, 'id', 0, 1);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reviewId = getInt($_POST, 'id', 0, 1);
}

if ($reviewId === 0) {
    $_SESSION['flash_error'] = 'Отзыв не найден.';
    redirect('profile.php');
}

// получаем отзыв и проверяем владельца
$stmt = $pdo->prepare('
    SELECT r.*, f.title AS film_title
    FROM reviews r JOIN films f ON f.id = r.film_id
    WHERE r.id = ? LIMIT 1
');
$stmt->execute([$reviewId]);
$review = $stmt->fetch();

if (!$review) {
    $_SESSION['flash_error'] = 'Отзыв не найден.';
    redirect('profile.php');
}
// разрешаем редактировать только владельцу или администратору
if ((int)$review['user_id'] !== (int)currentUserId() && !isAdmin()) {
    $_SESSION['flash_error'] = 'Нет прав на редактирование этого отзыва.';
    redirect('profile.php');
}

$errors = [];
$data = [
    'rating'          => (int)$review['rating'],
    'comment'         => $review['comment'] ?? '',
    'would_recommend' => (int)$review['would_recommend'],
    'watched_date'    => $review['watched_date'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!checkCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Неверный CSRF-токен.';
    } else {
        $rating         = getInt($_POST, 'rating', 0, 1, 10);
        $comment        = trim($_POST['comment'] ?? '');
        $wouldRecommend = isset($_POST['would_recommend']) ? 1 : 0;
        $watchedDate    = $_POST['watched_date'] ?? '';

        $data = compact('rating', 'comment', 'wouldRecommend', 'watchedDate');
        $data['would_recommend'] = $wouldRecommend;
        $data['watched_date']    = $watchedDate;

        if ($rating < 1 || $rating > 10) {
            $errors[] = 'Оценка должна быть от 1 до 10.';
        }
        if (mb_strlen($comment) > 1000) {
            $errors[] = 'Комментарий слишком длинный.';
        }
        if ($watchedDate !== '' && strtotime($watchedDate) > time()) {
            $errors[] = 'Дата просмотра не может быть в будущем.';
        }

        if (empty($errors)) {
            $upd = $pdo->prepare('UPDATE reviews SET rating=?, comment=?, would_recommend=?, watched_date=? WHERE id=?');
            $upd->execute([
                $rating,
                $comment !== '' ? $comment : null,
                $wouldRecommend,
                $watchedDate !== '' ? $watchedDate : null,
                $reviewId,
            ]);
            $_SESSION['flash_success'] = 'Отзыв обновлён.';
            redirect('film.php?id=' . (int)$review['film_id']);
        }
    }
}

$pageTitle = 'Редактирование отзыва';
?>

<div class="form-card">
    <h1>Редактирование отзыва</h1>
    <p>Фильм: <strong><?= e($review['film_title']) ?></strong></p>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form id="review-form" method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="id" value="<?= (int)$reviewId ?>">

        <div class="form-group">
            <label for="rating">Оценка (1-10) *</label>
            <input type="number" id="rating" name="rating" min="1" max="10" required
                   value="<?= e($data['rating']) ?>">
        </div>

        <div class="form-group">
            <label for="comment">Комментарий</label>
            <textarea id="comment" name="comment" maxlength="1000"><?= e($data['comment']) ?></textarea>
        </div>

        <div class="form-group">
            <label for="watched_date">Дата просмотра</label>
            <input type="date" id="watched_date" name="watched_date" max="<?= date('Y-m-d') ?>"
                   value="<?= e($data['watched_date']) ?>">
        </div>

        <div class="form-group checkbox-group">
            <input type="checkbox" id="would_recommend" name="would_recommend" value="1"
                   <?= !empty($data['would_recommend']) ? 'checked' : '' ?>>
            <label for="would_recommend">Рекомендую к просмотру</label>
        </div>

        <button type="submit" class="btn">Сохранить</button>
        <a href="<?= SITE_URL ?>/profile.php" class="btn btn-secondary">Отмена</a>
    </form>
</div>

<script src="<?= SITE_URL ?>/assets/js/validation.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
