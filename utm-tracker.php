<?php
/**
 * UTM Tracker - Відстеження UTM-міток
 * Цей файл зберігає UTM-мітки з URL в сесію та cookies
 */

session_start();

// Термін зберігання cookies - 30 днів
$period_cookie = 2592000; // 30 днів (2592000 секунд)

// Зберігаємо UTM-мітки в cookies якщо вони передані в URL
if ($_GET) {
    if (isset($_GET['utm_source'])) {
        setcookie("utm_source", $_GET['utm_source'], time() + $period_cookie, '/');
    }
    if (isset($_GET['utm_medium'])) {
        setcookie("utm_medium", $_GET['utm_medium'], time() + $period_cookie, '/');
    }
    if (isset($_GET['utm_term'])) {
        setcookie("utm_term", $_GET['utm_term'], time() + $period_cookie, '/');
    }
    if (isset($_GET['utm_content'])) {
        setcookie("utm_content", $_GET['utm_content'], time() + $period_cookie, '/');
    }
    if (isset($_GET['utm_campaign'])) {
        setcookie("utm_campaign", $_GET['utm_campaign'], time() + $period_cookie, '/');
    }
}

// Ініціалізація масиву UTM в сесії якщо його ще немає
if (!isset($_SESSION['utms'])) {
    $_SESSION['utms'] = array();
    $_SESSION['utms']['utm_source'] = '';
    $_SESSION['utms']['utm_medium'] = '';
    $_SESSION['utms']['utm_term'] = '';
    $_SESSION['utms']['utm_content'] = '';
    $_SESSION['utms']['utm_campaign'] = '';
}

// Заповнюємо сесію значеннями з GET або з cookies
$_SESSION['utms']['utm_source'] = isset($_GET['utm_source']) && !empty($_GET['utm_source']) 
    ? $_GET['utm_source'] 
    : (isset($_COOKIE['utm_source']) ? $_COOKIE['utm_source'] : '');

$_SESSION['utms']['utm_medium'] = isset($_GET['utm_medium']) && !empty($_GET['utm_medium']) 
    ? $_GET['utm_medium'] 
    : (isset($_COOKIE['utm_medium']) ? $_COOKIE['utm_medium'] : '');

$_SESSION['utms']['utm_term'] = isset($_GET['utm_term']) && !empty($_GET['utm_term']) 
    ? $_GET['utm_term'] 
    : (isset($_COOKIE['utm_term']) ? $_COOKIE['utm_term'] : '');

$_SESSION['utms']['utm_content'] = isset($_GET['utm_content']) && !empty($_GET['utm_content']) 
    ? $_GET['utm_content'] 
    : (isset($_COOKIE['utm_content']) ? $_COOKIE['utm_content'] : '');

$_SESSION['utms']['utm_campaign'] = isset($_GET['utm_campaign']) && !empty($_GET['utm_campaign']) 
    ? $_GET['utm_campaign'] 
    : (isset($_COOKIE['utm_campaign']) ? $_COOKIE['utm_campaign'] : '');
?>

