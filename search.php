<?php
// search.php

// Инициализация переменной поиска
$q = trim($_GET['q'] ?? '');

// Подключение к БД
$host = 'localhost';
$db   = 'blogtest';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}

// Подготовка результатов поиска
$results = [];
$message = '';

if ($q !== '') {
    if (strlen($q) < 3) {
        $message = 'Введите минимум 3 символа.';
    } else {
        $sql = "
            SELECT p.title, c.body, c.post_id
            FROM comments c
            JOIN posts p ON c.post_id = p.id
            WHERE c.body LIKE ?
            LIMIT 50
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(["%$q%"]);
        $results = $stmt->fetchAll();

        if (empty($results)) {
            $message = 'Ничего не найдено по запросу: <strong>' . htmlspecialchars($q) . '</strong>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Поиск по комментариям</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .result { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        .highlight { background-color: yellow; }
        input[type="text"] { width: 300px; padding: 8px; font-size: 16px; }
        button { padding: 8px 16px; font-size: 16px; }
        .message { color: #d00; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Поиск записей по тексту комментария</h1>

    <form method="GET">
        <input
            type="text"
            name="q"
            placeholder="Введите текст (минимум 3 символа)"
            value="<?= htmlspecialchars($q) ?>"
        >
        <button type="submit">Найти</button>
    </form>

    <?php if ($message): ?>
        <p class="message"><?= $message ?></p>
    <?php endif; ?>

    <?php if (!empty($results)): ?>
        <h2>Результаты поиска:</h2>
        <?php foreach ($results as $row): ?>
            <?php
            $highlighted_body = preg_replace(
                '/' . preg_quote($q, '/') . '/i',
                '<span class="highlight">$0</span>',
                htmlspecialchars($row['body'])
            );
            ?>
            <div class="result">
                <h3><?= htmlspecialchars($row['title']) ?></h3>
                <p><strong>Комментарий:</strong> <?= $highlighted_body ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>