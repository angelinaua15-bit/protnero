<?php
// Обробник відправки даних в CRM систему

// Перевірка методу запиту
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Метод не дозволено']);
    exit;
}

// Отримання даних з форми
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';

// Валідація обов'язкових полів
if (empty($name) || empty($phone)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Ім\'я та телефон обов\'язкові']);
    exit;
}

// Генерація унікального ідентифікатора замовлення (11 цифр)
$order_id = number_format(round(microtime(true) * 10), 0, '.', '') . rand(10000, 99999);

// Підготовка даних для товарів (якщо потрібно)
$products_list = array(
    0 => array(
        'product_id' => isset($_POST['product_id']) ? $_POST['product_id'] : '1',
        'price'      => isset($_POST['product_price']) ? $_POST['product_price'] : '0',
        'count'      => '1',
    )
);

$products = urlencode(serialize($products_list));
$sender = urlencode(serialize($_SERVER));

// Параметри для відправки в CRM
$data = array(
    'key'             => '', // Ключ API (якщо потрібен - заповніть тут)
    'order_id'        => $order_id,
    'country'         => 'UA', // Україна
    'office'          => '1',
    'products'        => $products,
    'bayer_name'      => $name,
    'phone'           => $phone,
    'email'           => isset($_POST['email']) ? $_POST['email'] : '',
    'comment'         => isset($_POST['message']) ? $_POST['message'] : '',
    'notification'    => isset($_POST['notification']) ? $_POST['notification'] : '',
    'delivery'        => isset($_POST['delivery']) ? $_POST['delivery'] : '',
    'delivery_adress' => isset($_POST['delivery_adress']) ? $_POST['delivery_adress'] : '',
    'payment'         => '',
    'sender'          => $sender,
    'utm_source'      => isset($_POST['utm_source']) ? $_POST['utm_source'] : '',
    'utm_medium'      => isset($_POST['utm_medium']) ? $_POST['utm_medium'] : '',
    'utm_term'        => isset($_POST['utm_term']) ? $_POST['utm_term'] : '',
    'utm_content'     => isset($_POST['utm_content']) ? $_POST['utm_content'] : '',
    'utm_campaign'    => isset($_POST['utm_campaign']) ? $_POST['utm_campaign'] : '',
    'additional_1'    => '',
    'additional_2'    => '',
    'additional_3'    => '',
    'additional_4'    => ''
);

// Відправка запиту в CRM
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, 'http://sadhelp.lp-crm.biz/api/addNewOrder.html');
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
curl_setopt($curl, CURLOPT_TIMEOUT, 30);
$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

// Обробка відповіді
if ($response === false) {
    // Помилка при відправці
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Помилка з\'єднання з CRM']);
    
    // Резервна копія - відправка на email (опціонально)
    $to = "test@mail.com"; // Замініть на вашу пошту
    $subject = 'Замовлення з сайту (резервна копія)';
    $message = "ПІБ: {$name}\nТелефон: {$phone}\nID замовлення: {$order_id}";
    mail($to, $subject, $message, "Content-type:text/plain;charset=utf-8\r\n");
    
    exit;
}

// Декодування відповіді від CRM
$crm_response = json_decode($response, true);

if (isset($crm_response['status']) && $crm_response['status'] === 'ok') {
    // Успішна відправка
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Замовлення успішно оформлено',
        'order_id' => $order_id
    ]);
} else {
    // Помилка від CRM
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => isset($crm_response['message']) ? $crm_response['message'] : 'Помилка при оформленні замовлення'
    ]);
}
?>

