<?php
// import.php

// Настройки подключения к БД
$host = 'localhost';
$db   = 'blogtest';
$user = 'root';  // по умолчанию в OpenServer
$pass = '';      // по умолчанию пустой

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}

// URL-ы для данных
$posts_url = 'https://jsonplaceholder.typicode.com/posts';
$comments_url = 'https://jsonplaceholder.typicode.com/comments';

function fetchJson($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // ⚠️ Отключает проверку SSL
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // ⚠️ Отключает проверку имени хоста
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

    $result = curl_exec($ch);
    
    if (curl_error($ch)) {
        die("cURL ошибка: " . curl_error($ch));
    }
    
    curl_close($ch);
    return json_decode($result, true);
}

// Используем
$posts = fetchJson($posts_url);
$comments = fetchJson($comments_url);

if (!$posts || !$comments) {
    die("Ошибка загрузки данных с API");
}

// Очищаем таблицы
$pdo->exec("DELETE FROM comments");
$pdo->exec("DELETE FROM posts");

// Подготовленные запросы
$stmt_post = $pdo->prepare("INSERT INTO posts (id, user_id, title, body) VALUES (?, ?, ?, ?)");
$stmt_comment = $pdo->prepare("INSERT INTO comments (id, post_id, name, email, body) VALUES (?, ?, ?, ?, ?)");

$count_posts = 0;
$count_comments = 0;

// Загружаем посты
foreach ($posts as $post) {
    $stmt_post->execute([
        $post['id'],
        $post['userId'],
        $post['title'],
        $post['body']
    ]);
    $count_posts++;
}

// Загружаем комментарии
foreach ($comments as $comment) {
    $stmt_comment->execute([
        $comment['id'],
        $comment['postId'],
        $comment['name'],
        $comment['email'],
        $comment['body']
    ]);
    $count_comments++;
}

// Вывод в консоль
echo "Загружено $count_posts записей и $count_comments комментариев\n";