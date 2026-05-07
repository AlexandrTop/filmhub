<?php
/**
 * Удаление собственного отзыва.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

$pdo = Database::getConnection();
$reviewId = getInt($_GET, 'id', 0, 1);

if ($reviewId === 0) {
    $_SESSION['flash_error'] = 'Не указан отзыв.';
    redirect('profile.php');
}

$stmt = $pdo->prepare('SELECT user_id, film_id FROM reviews WHERE id = ?');
$stmt->execute([$reviewId]);
$review = $stmt->fetch();

if (!$review) {
    $_SESSION['flash_error'] = 'Отзыв не найден.';
    redirect('profile.php');
}
// удаление - либо владелец, либо админ
if ((int)$review['user_id'] !== (int)currentUserId() && !isAdmin()) {
    $_SESSION['flash_error'] = 'Нет прав на удаление этого отзыва.';
    redirect('profile.php');
}

$del = $pdo->prepare('DELETE FROM reviews WHERE id = ?');
$del->execute([$reviewId]);

$_SESSION['flash_success'] = 'Отзыв удалён.';
redirect('film.php?id=' . (int)$review['film_id']);
