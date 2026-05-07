<?php
/**
 * Страница поиска фильмов (публичная).
 * вторая обязательная форма по требованиям лабораторной работы.
 * позволяет искать по нескольким критериям одновременно.
 */

require_once __DIR__ . '/includes/header.php';

$pdo = Database::getConnection();

// получаем список жанров для выпадающего списка
$genres = $pdo->query('SELECT id, name FROM genres ORDER BY name')->fetchAll();

// читаем параметры поиска
$query    = trim((string)($_GET['q'] ?? ''));
$genreId  = getInt($_GET, 'genre_id', 0, 0);
$yearFrom = getInt($_GET, 'year_from', 0, 0, 9999);
$yearTo   = getInt($_GET, 'year_to', 0, 0, 9999);
$minRate  = getInt($_GET, 'min_rating', 0, 0, 10);

$results  = [];
$searched = isset($_GET['search']);

if ($searched) {
    // динамически строим запрос с параметрами
    $where  = [];
    $params = [];

    if ($query !== '') {
        $where[] = '(f.title LIKE ? OR f.director LIKE ?)';
        $like = '%' . $query . '%';
        $params[] = $like;
        $params[] = $like;
    }
    if ($genreId > 0) {
        $where[] = 'f.genre_id = ?';
        $params[] = $genreId;
    }
    if ($yearFrom > 0) {
        $where[] = 'f.release_year >= ?';
        $params[] = $yearFrom;
    }
    if ($yearTo > 0) {
        $where[] = 'f.release_year <= ?';
        $params[] = $yearTo;
    }

    $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // having для фильтрации по среднему рейтингу
    $having = $minRate > 0 ? 'HAVING avg_rating >= ' . $minRate : '';

    $sql = "
        SELECT f.id, f.title, f.release_year, f.poster, g.name AS genre_name,
               COALESCE(ROUND(AVG(r.rating), 1), 0) AS avg_rating
        FROM films f
        LEFT JOIN genres g ON g.id = f.genre_id
        LEFT JOIN reviews r ON r.film_id = f.id
        $whereSql
        GROUP BY f.id
        $having
        ORDER BY f.title
        LIMIT 50
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll();
}

$pageTitle = 'Поиск фильмов';
?>

<h1>🔎 Поиск фильмов</h1>

<form class="form-card" method="get" action="<?= SITE_URL ?>/search.php" style="max-width: 800px;">
    <input type="hidden" name="search" value="1">

    <div class="form-row">
        <div class="form-group">
            <label for="q">Название или режиссёр</label>
            <input type="text" id="q" name="q" value="<?= e($query) ?>" maxlength="100" placeholder="Например, Нолан">
        </div>
        <div class="form-group">
            <label for="genre_id">Жанр</label>
            <select id="genre_id" name="genre_id">
                <option value="0">Любой</option>
                <?php foreach ($genres as $g): ?>
                    <option value="<?= (int)$g['id'] ?>" <?= $genreId === (int)$g['id'] ? 'selected' : '' ?>>
                        <?= e($g['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="year_from">Год выпуска: от</label>
            <input type="number" id="year_from" name="year_from" min="1888" max="2100"
                   value="<?= $yearFrom > 0 ? $yearFrom : '' ?>" placeholder="1888">
        </div>
        <div class="form-group">
            <label for="year_to">до</label>
            <input type="number" id="year_to" name="year_to" min="1888" max="2100"
                   value="<?= $yearTo > 0 ? $yearTo : '' ?>" placeholder="<?= date('Y') ?>">
        </div>
        <div class="form-group">
            <label for="min_rating">Мин. рейтинг (0-10)</label>
            <input type="number" id="min_rating" name="min_rating" min="0" max="10"
                   value="<?= $minRate > 0 ? $minRate : '' ?>" placeholder="0">
        </div>
    </div>

    <button type="submit" class="btn">Найти</button>
    <a href="<?= SITE_URL ?>/search.php" class="btn btn-secondary">Сбросить</a>
</form>

<?php if ($searched): ?>
    <h2 style="margin-top: 25px;">Результаты поиска: <?= count($results) ?></h2>
    <?php if (empty($results)): ?>
        <div class="alert alert-info">По заданным критериям ничего не найдено.</div>
    <?php else: ?>
        <div class="films-grid">
            <?php foreach ($results as $film): ?>
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
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
