<?php
/**
 * Скрипт для очищення сесії та cookies
 * Використовуйте для тестування форм
 * Просто відкрийте: http://localhost:8000/clear-session.php
 */

session_start();

// Очищення всієї сесії
$_SESSION = array();

// Видалення cookie сесії
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Видалення UTM cookies
$utm_cookies = ['utm_source', 'utm_medium', 'utm_term', 'utm_content', 'utm_campaign'];
foreach ($utm_cookies as $cookie) {
    if (isset($_COOKIE[$cookie])) {
        setcookie($cookie, '', time() - 3600, '/');
    }
}

// Знищення сесії
session_destroy();
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Сесія очищена</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            text-align: center;
            background: #f5f5f5;
        }
        .success {
            background: #4CAF50;
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .info {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: left;
        }
        button {
            background: #2196F3;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px;
        }
        button:hover {
            background: #0b7dda;
        }
        .clear-btn {
            background: #ff9800;
        }
        .clear-btn:hover {
            background: #e68900;
        }
    </style>
</head>
<body>
    <div class="success">
        <h2>✅ Сесію та cookies очищено!</h2>
        <p>Тепер ви можете знову тестувати форми</p>
    </div>
    
    <div class="info">
        <h3>📋 Що було очищено:</h3>
        <ul>
            <li>PHP сесія ($_SESSION)</li>
            <li>UTM-мітки (cookies)</li>
            <li>Cookie сесії</li>
        </ul>
        
        <h3>💡 Також потрібно очистити в браузері:</h3>
        <p>Відкрийте консоль браузера (F12) і виконайте:</p>
        <pre style="background: #f0f0f0; padding: 10px; border-radius: 5px;">sessionStorage.clear();
localStorage.clear();</pre>
        
        <p style="text-align: center; margin-top: 20px;">
            <button onclick="clearBrowserStorage()">Очистити браузерні дані</button>
            <button onclick="location.href='/index.php'" class="clear-btn">Повернутися на головну</button>
        </p>
    </div>
    
    <script>
        function clearBrowserStorage() {
            try {
                sessionStorage.clear();
                localStorage.clear();
                alert('✅ SessionStorage та localStorage очищено!\n\nТепер оновіть сторінку.');
                setTimeout(() => {
                    location.href = '/index.php';
                }, 1500);
            } catch(e) {
                alert('❌ Помилка: ' + e.message);
            }
        }
        
        // Автоматично очищуємо при завантаженні
        window.onload = function() {
            if (confirm('Також очистити sessionStorage та localStorage браузера?')) {
                clearBrowserStorage();
            }
        }
    </script>
</body>
</html>
```


