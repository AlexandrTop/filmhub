<?php
/**
 * Главная страница FilmHub (публичный компонент).
 * содержит 3 динамических блока: статистика, рекомендуемые фильмы, последние отзывы.
 */

require_once __DIR__ . '/includes/header.php';

$pdo = Database::getConnection();

// блок 1: общая статистика (динамика из бд)
$stats = [
    'films'   => (int)$pdo->query('SELECT COUNT(*) FROM films')->fetchColumn(),
    'reviews' => (int)$pdo->query('SELECT COUNT(*) FROM reviews')->fetchColumn(),
    'users'   => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn(),
];

// блок 2: рекомендуемые фильмы (отмечены админом как is_featured)
$featuredStmt = $pdo->query("
    SELECT f.id, f.title, f.release_year, f.poster, g.name AS genre_name,
           COALESCE(ROUND(AVG(r.rating), 1), 0) AS avg_rating
    FROM films f
    LEFT JOIN genres g ON g.id = f.genre_id
    LEFT JOIN reviews r ON r.film_id = f.id
    WHERE f.is_featured = 1
    GROUP BY f.id
    ORDER BY f.created_at DESC
    LIMIT 4
");
$featured = $featuredStmt->fetchAll();

// блок 3: последние отзывы
$reviewsStmt = $pdo->query("
    SELECT r.id, r.rating, r.comment, r.created_at,
           u.username, f.title AS film_title, f.id AS film_id
    FROM reviews r
    JOIN users u ON u.id = r.user_id
    JOIN films f ON f.id = r.film_id
    ORDER BY r.created_at DESC
    LIMIT 4
");
$recentReviews = $reviewsStmt->fetchAll();
?>

<section class="hero">
    <h1>Добро пожаловать в <span class="accent">FilmHub</span></h1>
    <p>Каталог фильмов и платформа для отзывов от зрителей.</p>
</section>

<!-- блок статистики -->
<div class="stats-row">
    <div class="stat-card">
        <div class="number"><?= $stats['films'] ?></div>
        <div class="label">Фильмов в каталоге</div>
    </div>
    <div class="stat-card">
        <div class="number"><?= $stats['reviews'] ?></div>
        <div class="label">Отзывов от зрителей</div>
    </div>
    <div class="stat-card">
        <div class="number"><?= $stats['users'] ?></div>
        <div class="label">Зарегистрированных пользователей</div>
    </div>
</div>

<!-- рекомендуемые фильмы -->
<h2>🎯 Рекомендуем посмотреть</h2>
<?php if (empty($featured)): ?>
    <p class="alert alert-info">Пока нет рекомендуемых фильмов. Загляните позже.</p>
<?php else: ?>
    <div class="films-grid">
        <?php foreach ($featured as $film): ?>
            <div class="film-card">
                <div class="film-poster">
                    <?php if (!empty($film['poster'])): ?>
                        <img src="<?= e($film['poster']) ?>" alt="<?= e($film['title']) ?>">
                    <?php else: ?>
                        <span>Постер отсутствует</span>
                    <?php endif; ?>
                </div>
                <div class="film-info">
                    <a class="film-title" href="<?= SITE_URL ?>/film.php?id=<?= (int)$film['id'] ?>">
                        <?= e($film['title']) ?>
                    </a>
                    <div class="film-meta">
                        <?= e($film['release_year']) ?> · <?= e($film['genre_name'] ?? 'Без жанра') ?>
                    </div>
                    <div class="film-rating">⭐ <?= e($film['avg_rating']) ?>/10</div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- последние отзывы -->
<section class="recent-reviews">
    <h2>📝 Последние отзывы</h2>
    <?php if (empty($recentReviews)): ?>
        <p class="alert alert-info">Пока нет отзывов. Будьте первым!</p>
    <?php else: ?>
        <?php foreach ($recentReviews as $rev): ?>
            <div class="review-card">
                <div class="review-head">
                    <span class="review-author">
                        <?= e($rev['username']) ?> о фильме
                        <a href="<?= SITE_URL ?>/film.php?id=<?= (int)$rev['film_id'] ?>"><?= e($rev['film_title']) ?></a>
                    </span>
                    <span class="review-rating">⭐ <?= (int)$rev['rating'] ?>/10</span>
                </div>
                <div class="review-date"><?= e(date('d.m.Y H:i', strtotime($rev['created_at']))) ?></div>
                <?php if (!empty($rev['comment'])): ?>
                    <p class="review-text"><?= e(shorten($rev['comment'], 220)) ?></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
