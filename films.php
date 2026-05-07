<?php
/**
 * Страница каталога фильмов (публичная).
 * с поддержкой постраничного вывода.
 */

require_once __DIR__ . '/includes/header.php';

$pdo = Database::getConnection();

// настройки пагинации
$perPage = 12;
$page    = max(1, getInt($_GET, 'page', 1, 1, 9999));
$offset  = ($page - 1) * $perPage;

// общее количество фильмов
$totalFilms = (int)$pdo->query('SELECT COUNT(*) FROM films')->fetchColumn();
$totalPages = max(1, (int)ceil($totalFilms / $perPage));

// получаем список фильмов с жанром и средним рейтингом
$sql = "
    SELECT f.id, f.title, f.release_year, f.poster, g.name AS genre_name,
           COALESCE(ROUND(AVG(r.rating), 1), 0) AS avg_rating,
           COUNT(r.id) AS reviews_count
    FROM films f
    LEFT JOIN genres g ON g.id = f.genre_id
    LEFT JOIN reviews r ON r.film_id = f.id
    GROUP BY f.id
    ORDER BY f.created_at DESC
    LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$films = $stmt->fetchAll();

$pageTitle = 'Каталог фильмов';
?>

<h1>📽 Каталог фильмов</h1>
<p>Всего фильмов: <strong><?= $totalFilms ?></strong>. Страница <?= $page ?> из <?= $totalPages ?>.</p>

<?php if (empty($films)): ?>
    <div class="alert alert-info">В каталоге пока нет фильмов.</div>
<?php else: ?>
    <div class="films-grid">
        <?php foreach ($films as $film): ?>
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
                    <div class="film-rating">
                        ⭐ <?= e($film['avg_rating']) ?>/10
                        <span style="color:#888;font-weight:normal;font-size:13px;">
                            (<?= (int)$film['reviews_count'] ?> отз.)
                        </span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- пагинация -->
    <div style="margin-top: 25px; text-align: center;">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php if ($i === $page): ?>
                <span class="btn btn-small" style="margin: 2px;"><?= $i ?></span>
            <?php else: ?>
                <a class="btn btn-small btn-secondary" style="margin: 2px;" href="?page=<?= $i ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
