<?php
/**
 * Добавление отзыва на фильм (только авторизованным).
 */

require_once __DIR__ . '/includes/header.php';

requireLogin();

$pdo = Database::getConnection();
$filmId = getInt($_GET, 'film_id', 0, 1);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filmId = getInt($_POST, 'film_id', 0, 1);
}

if ($filmId === 0) {
    $_SESSION['flash_error'] = 'Не указан фильм.';
    redirect('films.php');
}

// проверка существования фильма
$filmStmt = $pdo->prepare('SELECT id, title FROM films WHERE id = ?');
$filmStmt->execute([$filmId]);
$film = $filmStmt->fetch();
if (!$film) {
    $_SESSION['flash_error'] = 'Фильм не найден.';
    redirect('films.php');
}

// проверка - не оставлял ли уже отзыв
$existing = $pdo->prepare('SELECT id FROM reviews WHERE user_id = ? AND film_id = ?');
$existing->execute([currentUserId(), $filmId]);
if ($existing->fetch()) {
    $_SESSION['flash_error'] = 'Вы уже оставили отзыв на этот фильм.';
    redirect('film.php?id=' . $filmId);
}

$errors = [];
$old = ['rating' => 8, 'comment' => '', 'would_recommend' => 1, 'watched_date' => date('Y-m-d')];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!checkCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Неверный CSRF-токен.';
    } else {
        $rating         = getInt($_POST, 'rating', 0, 1, 10);
        $comment        = trim($_POST['comment'] ?? '');
        $wouldRecommend = isset($_POST['would_recommend']) ? 1 : 0;
        $watchedDate    = $_POST['watched_date'] ?? '';

        $old = compact('rating', 'comment', 'wouldRecommend', 'watchedDate');

        if ($rating < 1 || $rating > 10) {
            $errors[] = 'Оценка должна быть от 1 до 10.';
        }
        if (mb_strlen($comment) > 1000) {
            $errors[] = 'Комментарий слишком длинный (макс. 1000 символов).';
        }
        if ($comment !== '' && mb_strlen($comment) < 5) {
            $errors[] = 'Комментарий слишком короткий (мин. 5 символов).';
        }
        // проверка даты
        $dateValid = ($watchedDate === '') || (DateTime::createFromFormat('Y-m-d', $watchedDate) !== false);
        if (!$dateValid) {
            $errors[] = 'Неверный формат даты просмотра.';
        }
        if ($watchedDate !== '' && strtotime($watchedDate) > time()) {
            $errors[] = 'Дата просмотра не может быть в будущем.';
        }

        if (empty($errors)) {
            $sql = 'INSERT INTO reviews (user_id, film_id, rating, comment, would_recommend, watched_date)
                    VALUES (?, ?, ?, ?, ?, ?)';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                currentUserId(),
                $filmId,
                $rating,
                $comment !== '' ? $comment : null,
                $wouldRecommend,
                $watchedDate !== '' ? $watchedDate : null,
            ]);

            $_SESSION['flash_success'] = 'Отзыв опубликован, спасибо!';
            redirect('film.php?id=' . $filmId);
        }
    }
}

$pageTitle = 'Новый отзыв';
?>

<div class="form-card">
    <h1>Отзыв на фильм «<?= e($film['title']) ?>»</h1>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form id="review-form" method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="film_id" value="<?= (int)$film['id'] ?>">

        <div class="form-group">
            <label for="rating">Ваша оценка (1-10) *</label>
            <input type="number" id="rating" name="rating" min="1" max="10" required
                   value="<?= e($old['rating']) ?>">
        </div>

        <div class="form-group">
            <label for="comment">Комментарий</label>
            <textarea id="comment" name="comment" maxlength="1000"
                      placeholder="Поделитесь впечатлениями (необязательно)"><?= e($old['comment']) ?></textarea>
        </div>

        <div class="form-group">
            <label for="watched_date">Дата просмотра</label>
            <input type="date" id="watched_date" name="watched_date" max="<?= date('Y-m-d') ?>"
                   value="<?= e($old['watched_date']) ?>">
        </div>

        <div class="form-group checkbox-group">
            <input type="checkbox" id="would_recommend" name="would_recommend" value="1"
                   <?= !empty($old['would_recommend']) ? 'checked' : '' ?>>
            <label for="would_recommend">Рекомендую к просмотру</label>
        </div>

        <button type="submit" class="btn">Опубликовать отзыв</button>
        <a href="<?= SITE_URL ?>/film.php?id=<?= (int)$film['id'] ?>" class="btn btn-secondary">Отмена</a>
    </form>
</div>

<script src="<?= SITE_URL ?>/assets/js/validation.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
