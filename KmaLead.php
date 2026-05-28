<?php

class KmaLead
{
    private $leadUrl = 'https://api.kma.biz/lead/add';
    private $clickUrl = 'https://api.kma.biz/click/make';
    private $token;
    private $headers = [];
    public $debug = false;

    /**
     * @param string $token
     */
    public function __construct($token = '')
    {
        $this->token = $token;
    }

    /**
     * @param string $channel
     * @return bool|string
     */
    public function getClick($channel)
    {
        $this->setHeaders();
        $this->setClickHeaders($channel);
        if ($curl = curl_init()) {
            curl_setopt($curl, CURLOPT_URL, $this->clickUrl . "?" . http_build_query($_GET));
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $this->getHeaders());
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($curl);
            curl_close($curl);
            header('Content-Type: application/json');
            return $result;
        }
        return "{}";
    }

    /**
     * @param array $data
     * @return bool|string
     */
    public function addLead($data)
    {
        $result = $this->sendRequest($data);
        $array = json_decode($result, true);
        if (isset($array['order'])) {
            return $array['order'];
        }
        if (isset($array['code'], $array['message'])) {
            $this->echoDebugMessage("<strong>Код ошибки: {$array['code']}. Текст ошибки: {$array['message']}</strong>");
        }
        return false;
    }

    /**
     * @param array $data
     * @return string
     */
    public function addLeadAndReturnPage($data)
    {
        return $this->sendRequest($data);
    }

    /**
     * @param array $data
     * @return bool|string
     */
    private function sendRequest($data)
    {
        $this->setHeaders();
        if ($curl = curl_init()) {
            $headers = $this->getHeaders();
            curl_setopt($curl, CURLOPT_URL, $this->leadUrl);
            curl_setopt($curl, CURLOPT_HEADER, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLINFO_HEADER_OUT, true);
            curl_setopt($curl, CURLOPT_TIMEOUT,15);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($curl);
            $header = substr($response, 0, curl_getinfo($curl, CURLINFO_HEADER_SIZE));
            $result = substr($response, curl_getinfo($curl, CURLINFO_HEADER_SIZE));
            if (in_array(curl_errno($curl), [CURLE_OPERATION_TIMEDOUT]) || curl_getinfo($curl, CURLINFO_RESPONSE_CODE) != 200) {
                try {
                    $fp = fopen(__DIR__ . '/lead-' . sha1(KMA_ACCESS_TOKEN . KMA_CHANNEL) . '.txt', 'a+');
                    fwrite($fp, json_encode(['ts' => time(), 'data' => $data, 'headers' => $headers], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\r\n");
                    fclose($fp);
                } catch (Exception $e) {}
                $result = json_encode(['order' => 'X', 'code' => 0, 'message' => 'LOCAL SAVE']);
            }
            $this->echoDebugMessage(curl_getinfo($curl, CURLINFO_HEADER_OUT));
            $this->echoDebugMessage($data);
            $this->echoDebugMessage($header);
            $this->echoDebugMessage($result);
            curl_close($curl);
            return $result;
        }
        return false;
    }

    public function resendRequest($data, $headers)
    {
        if ($curl = curl_init()) {
            curl_setopt($curl, CURLOPT_URL, $this->leadUrl);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLINFO_HEADER_OUT, true);
            curl_setopt($curl, CURLOPT_TIMEOUT,5);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            $result = curl_exec($curl);
            curl_close($curl);
            return $result;
        }
        return false;
    }

    /**
     * @return array
     */
    private function getHeaders()
    {
        array_walk($this->headers, function (&$value, $key) {
            $value = "$key: $value";
        });
        return array_values($this->headers);
    }

    private function setHeaders()
    {
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $this->headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        if (isset($this->headers['Host']) && !empty($this->headers['Host'])) {
            $rs = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http';
            $this->headers['X-Host-Url'] = $rs . "://" . $this->headers['Host'] . $_SERVER['REQUEST_URI'];
        }
        $this->filterHeaders();
        $this->headers['Accept'] = 'application/json';
        $this->headers['Authorization'] = "Bearer {$this->token}";
    }

    private function filterHeaders()
    {
        unset($this->headers['Host']);
        unset($this->headers['Cookie']);
        unset($this->headers['Content-Type']);
        unset($this->headers['Content-Length']);
        unset($this->headers['Referer']);
    }

    /**
     * @param string $channel
     */
    private function setClickHeaders($channel)
    {
        $this->headers['X-Forwarded-For'] = $this->getIp();
        $this->headers['X-Kma-Channel'] = $channel;
        if (isset($this->headers['X-Referer']) && !empty($this->headers['X-Referer'])) {
            $this->headers['Referer'] = $this->headers['X-Referer'];
            unset($this->headers['X-Referer']);
        }
    }

    private function echoDebugMessage($data)
    {
        if ($this->debug) {
            if (is_array($data)) {
                echo '<pre>';
                print_r($data);
                echo '</pre>';
            } else {
                echo "<pre>$data</pre>";
            }
        }
    }

    /**
     * @return string
     */
    public function getIp()
    {
        foreach ([
                     'HTTP_CF_CONNECTING_IP',
                     'HTTP_X_FORWARDED_FOR',
                     'REMOTE_ADDR',
                 ] as $key) {
            if (array_key_exists($key, $_SERVER)) {
                $ips = explode(',', $_SERVER[$key]);
                $ips = array_map('trim', $ips);
                $ips = array_filter($ips);
                foreach ($ips as $ip) {
                    $ip = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
                    if (!empty($ip)) {
                        return $ip;
                    }
                    $ip = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
                    if (!empty($ip)) {
                        return $ip;
                    }
                }
            }
        }
        return '127.0.0.1';
    }
}
