<?php
/**
 * Детальная страница фильма.
 * показывает информацию + список отзывов + форму добавления отзыва (для авторизованных).
 */

require_once __DIR__ . '/includes/header.php';

$pdo = Database::getConnection();
$filmId = getInt($_GET, 'id', 0, 1);

if ($filmId === 0) {
    $_SESSION['flash_error'] = 'Фильм не найден.';
    redirect('films.php');
}

// получаем сам фильм с жанром
$stmt = $pdo->prepare("
    SELECT f.*, g.name AS genre_name
    FROM films f
    LEFT JOIN genres g ON g.id = f.genre_id
    WHERE f.id = ? LIMIT 1
");
$stmt->execute([$filmId]);
$film = $stmt->fetch();

if (!$film) {
    $_SESSION['flash_error'] = 'Фильм не найден.';
    redirect('films.php');
}

// средний рейтинг и количество отзывов
$avgStmt = $pdo->prepare('SELECT ROUND(AVG(rating), 1) AS avg, COUNT(*) AS cnt FROM reviews WHERE film_id = ?');
$avgStmt->execute([$filmId]);
$rating = $avgStmt->fetch();

// список отзывов
$reviewsStmt = $pdo->prepare("
    SELECT r.*, u.username
    FROM reviews r
    JOIN users u ON u.id = r.user_id
    WHERE r.film_id = ?
    ORDER BY r.created_at DESC
");
$reviewsStmt->execute([$filmId]);
$reviews = $reviewsStmt->fetchAll();

// проверка - оставлял ли текущий пользователь отзыв
$myReview = null;
if (isLoggedIn()) {
    $myStmt = $pdo->prepare('SELECT * FROM reviews WHERE user_id = ? AND film_id = ? LIMIT 1');
    $myStmt->execute([currentUserId(), $filmId]);
    $myReview = $myStmt->fetch();
}

$pageTitle = $film['title'];
?>

<div class="film-detail">
    <div class="poster-big">
        <?php if (!empty($film['poster'])): ?>
            <img src="<?= e($film['poster']) ?>" alt="<?= e($film['title']) ?>">
        <?php else: ?>
            <div style="display:flex;align-items:center;justify-content:center;height:100%;color:#888;">
                Постер отсутствует
            </div>
        <?php endif; ?>
    </div>
    <div class="info-block">
        <h1><?= e($film['title']) ?></h1>
        <p><strong>Режиссёр:</strong> <?= e($film['director']) ?></p>
        <p><strong>Год выпуска:</strong> <?= (int)$film['release_year'] ?></p>
        <p><strong>Жанр:</strong> <?= e($film['genre_name'] ?? 'Не указан') ?></p>
        <p><strong>Длительность:</strong> <?= (int)$film['duration'] ?> мин.</p>
        <p><strong>Средний рейтинг:</strong>
            <span class="film-rating">
                ⭐ <?= e($rating['avg'] ?? '—') ?>/10
                (<?= (int)$rating['cnt'] ?> отз.)
            </span>
        </p>
        <?php if (!empty($film['description'])): ?>
            <h3 style="margin-top:14px;">Описание</h3>
            <p><?= nl2br(e($film['description'])) ?></p>
        <?php endif; ?>
    </div>
</div>

<section class="reviews-section">
    <h2>Отзывы зрителей (<?= count($reviews) ?>)</h2>

    <?php if (isLoggedIn()): ?>
        <?php if ($myReview): ?>
            <div class="alert alert-info">
                Вы уже оставили отзыв на этот фильм.
                <a href="<?= SITE_URL ?>/review_edit.php?id=<?= (int)$myReview['id'] ?>">Редактировать</a> или
                <a href="<?= SITE_URL ?>/review_delete.php?id=<?= (int)$myReview['id'] ?>"
                   onclick="return confirm('Удалить отзыв?');">удалить</a>.
            </div>
        <?php else: ?>
            <p>
                <a class="btn" href="<?= SITE_URL ?>/review_add.php?film_id=<?= (int)$film['id'] ?>">
                    Оставить отзыв
                </a>
            </p>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-info">
            Чтобы оставить отзыв, нужно <a href="<?= SITE_URL ?>/login.php">войти</a> или
            <a href="<?= SITE_URL ?>/register.php">зарегистрироваться</a>.
        </div>
    <?php endif; ?>

    <?php if (empty($reviews)): ?>
        <p>Отзывов пока нет.</p>
    <?php else: ?>
        <?php foreach ($reviews as $rev): ?>
            <div class="review-card">
                <div class="review-head">
                    <span class="review-author"><?= e($rev['username']) ?></span>
                    <span class="review-rating">⭐ <?= (int)$rev['rating'] ?>/10</span>
                </div>
                <div class="review-date">
                    <?= e(date('d.m.Y H:i', strtotime($rev['created_at']))) ?>
                    <?php if (!empty($rev['watched_date'])): ?>
                        · посмотрено <?= e(date('d.m.Y', strtotime($rev['watched_date']))) ?>
                    <?php endif; ?>
                    <?php if ((int)$rev['would_recommend'] === 1): ?>
                        · 👍 рекомендует
                    <?php else: ?>
                        · 👎 не рекомендует
                    <?php endif; ?>
                </div>
                <?php if (!empty($rev['comment'])): ?>
                    <p class="review-text"><?= nl2br(e($rev['comment'])) ?></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
