<?php

set_time_limit(0);

if (is_file('config.php')) {
    require_once 'config.php';
} else {
    exit('Для начала работы необходимо сконфигурировать приложение');
}

$file = __DIR__ . '/lead-' . sha1(KMA_ACCESS_TOKEN . KMA_CHANNEL) . '.txt';

$resend = $result = '';

$fn = fopen($file,'r');
if (empty($fn)) {
    exit('Файл с неотправленными лидами не найден');
}
while(! feof($fn))  {
    $line = fgets($fn);
    $array = json_decode($line, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($array)) {
        require_once 'KmaLead.php';
        /** @var KmaLead $kma */
        $kma = new KmaLead($token);
        $response = $kma->resendRequest($array['data'], $array['headers']);
        $data = json_decode($response, true);
        if (!isset($data['order'])) {
            $resend .= $line . "\r\n";
            if (isset($data['message'])) {
                $result .= 'Лид не добавлен: ' . $data['message'] . "\r\n";
            } else {
                $result .= 'Ошибка добавления лида: ' . $response . "\r\n";
            }
        } else {
            $result .= 'Лид успешно добавлен: ' . $data['order'] . "\r\n";
        }
    }
}
fclose($fn);

if (empty($resend)) {
    @unlink($file);
} else {
    file_put_contents($file, $resend);
}

if ($result) {
    echo "<pre>" . $result . "</pre>";
}

exit;
