<?php

if (is_file('config.php')) {
    require_once 'config.php';
} else {
    exit('Для начала работы необходимо сконфигурировать приложение');
}

$token = defined('KMA_ACCESS_TOKEN') ? KMA_ACCESS_TOKEN : 'access token';
$channel = defined('KMA_CHANNEL') ? KMA_CHANNEL : 'channel';
$debug = defined('KMA_DEBUG') ? KMA_DEBUG : false;

if ($debug) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['order_status'])) {
    switch ($_GET['order_status']) {
        case 'success':
            include_once 'template/success.php';
            break;
        case 'error':
            include_once 'template/error.php';
            break;
        default:
            exit();
    }
    exit();
}

require_once 'KmaLead.php';

/** @var KmaLead $kma */
$kma = new KmaLead($token);

if (isset($_SERVER['HTTP_X_KMA_API']) && $_SERVER['HTTP_X_KMA_API'] === 'click') {
    echo $kma->getClick($channel);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit();
}

// Отримання даних з форми
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';

// Перевірка обов'язкових полів
if (empty($name) || empty($phone)) {
    header('Location: success.php?order_status=error');
    exit();
}

// === KMA CRM ЗАКОМЕНТОВАНО - НЕ ВИКОРИСТОВУЄТЬСЯ ===
/*
$data = [
    'channel' => $channel,
    'ip' => $kma->getIp(),
];

foreach (['name', 'phone', 'data1', 'data2', 'data3', 'data4', 'data5', 'fbp', 'click', 'referer', 'return_page', 'client_data', 'address', 'country', 'language', 'landing', 'transit', 'timezone'] as $item) {
    if (isset($_POST[$item]) && !empty($_POST[$item])) {
        $data[$item] = $_POST[$item];
    }
}

$kma->debug = $debug;

if (isset($_POST['return_page']) && !empty($_POST['return_page'])) {
    echo $kma->addLeadAndReturnPage($data);
    exit();
} else {
    $order = $kma->addLead($data);
    $name = $data['name'];
    $phone = $data['phone'];
}
*/
// === КІНЕЦЬ ЗАКОМЕНТОВАНОГО КОДУ KMA ===

// Відправка даних ТІЛЬКИ в LP-CRM систему
$crmResult = sendToCRM($name, $phone, $_POST);

// Генерація номера замовлення для сесії
$order = 'LP-' . date('Ymd') . '-' . rand(1000, 9999);

// Збереження даних в сесію
session_start();
$_SESSION['order'] = $order;
$_SESSION['name'] = $name;
$_SESSION['phone'] = $phone;
$_SESSION['language'] = isset($_POST['language']) ? $_POST['language'] : 'ru';
$_SESSION['fbp'] = isset($_POST['fbp']) ? $_POST['fbp'] : '';

// Перевірка результату відправки в CRM
$crmResponse = json_decode($crmResult, true);
if (isset($crmResponse['status']) && $crmResponse['status'] === 'ok') {
    // Успішна відправка в CRM
    header('Location: success.php?order_status=success');
} else {
    // Навіть якщо CRM не відповів, показуємо успіх (дані збережені локально)
    header('Location: success.php?order_status=success');
}

exit();

/**
 * Функція для визначення країни по IP адресі
 */
function getCountryByIP() {
    // Отримання IP адреси клієнта
    $ip = $_SERVER['REMOTE_ADDR'];
    
    // Якщо це localhost - повертаємо UA за замовчуванням
    if ($ip === '127.0.0.1' || $ip === '::1' || strpos($ip, '192.168.') === 0) {
        return 'UA'; // За замовчуванням для локального тестування
    }
    
    // Спроба визначити країну через безкоштовний API
    try {
        $geoData = @file_get_contents("http://ip-api.com/json/{$ip}?fields=countryCode");
        if ($geoData) {
            $geo = json_decode($geoData, true);
            if (isset($geo['countryCode'])) {
                return $geo['countryCode'];
            }
        }
    } catch (Exception $e) {
        // Якщо помилка - використовуємо UA за замовчуванням
    }
    
    // Якщо не вдалося визначити - повертаємо UA
    return 'UA';
}

/**
 * Функція для відправки даних в CRM систему
 */
function sendToCRM($name, $phone, $postData) {
    // Генерація унікального ідентифікатора замовлення (11 цифр)
    $order_id = number_format(round(microtime(true) * 10), 0, '.', '') . rand(10000, 99999);
    
    // Автоматичне визначення країни по IP
    $country = getCountryByIP();
    
    // Підготовка даних для товарів
    $productId = defined('LP_CRM_PRODUCT_ID') ? LP_CRM_PRODUCT_ID : '1';
    $productPrice = defined('LP_CRM_PRODUCT_PRICE') ? LP_CRM_PRODUCT_PRICE : '0';
    
    $products_list = array(
        0 => array(
            'product_id' => isset($postData['product_id']) ? $postData['product_id'] : $productId,
            'price'      => isset($postData['product_price']) ? $postData['product_price'] : $productPrice,
            'count'      => '1',
        )
    );
    
    $products = urlencode(serialize($products_list));
    $sender = urlencode(serialize($_SERVER));
    
    // Отримання UTM-міток з сесії (якщо вони були збережені)
    $utm_source = '';
    $utm_medium = '';
    $utm_term = '';
    $utm_content = '';
    $utm_campaign = '';
    
    if (isset($_SESSION['utms'])) {
        $utm_source = isset($_SESSION['utms']['utm_source']) ? $_SESSION['utms']['utm_source'] : '';
        $utm_medium = isset($_SESSION['utms']['utm_medium']) ? $_SESSION['utms']['utm_medium'] : '';
        $utm_term = isset($_SESSION['utms']['utm_term']) ? $_SESSION['utms']['utm_term'] : '';
        $utm_content = isset($_SESSION['utms']['utm_content']) ? $_SESSION['utms']['utm_content'] : '';
        $utm_campaign = isset($_SESSION['utms']['utm_campaign']) ? $_SESSION['utms']['utm_campaign'] : '';
    }
    
    // Отримання ключа API з config.php
    $apiKey = defined('LP_CRM_API_KEY') ? LP_CRM_API_KEY : '';
    
    // Параметри для відправки в CRM
    $data = array(
        'key'             => $apiKey, // Ключ API CRM з config.php
        'order_id'        => $order_id,
        'country'         => $country, // Визначається автоматично по IP
        'office'          => '1',
        'products'        => $products,
        'bayer_name'      => $name,
        'phone'           => $phone,
        'email'           => isset($postData['email']) ? $postData['email'] : '',
        'comment'         => isset($postData['message']) ? $postData['message'] : '',
        'notification'    => isset($postData['notification']) ? $postData['notification'] : '',
        'delivery'        => isset($postData['delivery']) ? $postData['delivery'] : '',
        'delivery_adress' => isset($postData['delivery_adress']) ? $postData['delivery_adress'] : '',
        'payment'         => '',
        'sender'          => $sender,
        'utm_source'      => $utm_source,  // UTM з сесії
        'utm_medium'      => $utm_medium,  // UTM з сесії
        'utm_term'        => $utm_term,    // UTM з сесії
        'utm_content'     => $utm_content, // UTM з сесії
        'utm_campaign'    => $utm_campaign,// UTM з сесії
        'additional_1'    => '',
        'additional_2'    => '',
        'additional_3'    => '',
        'additional_4'    => ''
    );
    
    // ЛОГУВАННЯ: Збереження даних перед відправкою
    $logFile = __DIR__ . '/logs/crm-orders-' . date('Y-m-d') . '.log';
    $logDir = __DIR__ . '/logs';
    
    // Створення директорії для логів якщо не існує
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    // Збереження даних замовлення в лог файл (резервна копія)
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'order_id' => $order_id,
        'name' => $name,
        'phone' => $phone,
        'country' => $country . ' (авто-визначено по IP: ' . $_SERVER['REMOTE_ADDR'] . ')',
        'api_key_set' => !empty($apiKey) ? 'ТАК' : 'НІ (ПОМИЛКА!)',
        'full_data' => $data
    ];
    
    file_put_contents(
        $logFile, 
        "\n" . date('Y-m-d H:i:s') . " | NEW ORDER\n" . 
        json_encode($logData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n" . 
        str_repeat('-', 80) . "\n",
        FILE_APPEND
    );
    
    // Відправка запиту в LP-CRM
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, 'http://sadhelp.lp-crm.biz/api/addNewOrder.html');
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);
    
    // Логування результату
    $responseLog = [
        'timestamp' => date('Y-m-d H:i:s'),
        'order_id' => $order_id,
        'http_code' => $httpCode,
        'curl_error' => $curlError ? $curlError : 'немає',
        'response' => $response ? json_decode($response, true) : 'пуста відповідь'
    ];
    
    file_put_contents(
        $logFile,
        "RESPONSE: " . json_encode($responseLog, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n",
        FILE_APPEND
    );
    
    // Логування в error_log (якщо увімкнено debug)
    if (defined('KMA_DEBUG') && KMA_DEBUG) {
        error_log("=== LP-CRM Request ===");
        error_log("Country detected: " . $country . " (IP: " . $_SERVER['REMOTE_ADDR'] . ")");
        error_log("API Key set: " . (!empty($apiKey) ? 'YES' : 'NO (ERROR!)'));
        error_log("Product ID: " . $productId . ", Price: " . $productPrice);
        error_log("Data sent: " . print_r($data, true));
        error_log("HTTP Code: " . $httpCode);
        error_log("Response: " . $response);
        if ($curlError) {
            error_log("CURL Error: " . $curlError);
        }
        error_log("Log file: " . $logFile);
        error_log("======================");
    }
    
    // Якщо є помилка з'єднання, повертаємо помилку
    if ($response === false) {
        return json_encode([
            'status' => 'error',
            'message' => 'Помилка з\'єднання з CRM: ' . $curlError
        ]);
    }
    
    return $response;
}
