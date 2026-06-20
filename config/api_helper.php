<?php

function get_api_base_url()
{
    return 'https://unibilab.freehosting.dev/lab';
}

function write_api_log($method, $url, $data, $response, $http_code) {
    $log_file = __DIR__ . '/../api_log.txt';
    $time = date('Y-m-d H:i:s');
    $log = "[$time] $method $url - HTTP $http_code\n";
    if ($data) {
        $log .= "Request: " . json_encode($data) . "\n";
    }
    $log .= "Response: " . substr(str_replace("\n", " ", $response), 0, 1000) . "\n";
    $log .= "--------------------------------------------------\n";
    file_put_contents($log_file, $log, FILE_APPEND);
}

function get_cookie_file() {
    $session_id = session_id();
    if (empty($session_id)) {
        session_start();
        $session_id = session_id();
    }
    return sys_get_temp_dir() . '/cookie_' . $session_id . '.txt';
}

function call_api($method, $endpoint, $data = null)
{
    $url = get_api_base_url() . $endpoint;
    $curl = curl_init();

    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36'
    ];

    if (isset($_SESSION['token'])) {
        $headers[] = 'Authorization: Bearer ' . $_SESSION['token'];
    }

    $cookie_file = get_cookie_file();

    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_COOKIEJAR => $cookie_file,
        CURLOPT_COOKIEFILE => $cookie_file,
    ];

    if ($data !== null && strtoupper($method) !== 'GET') {
        $options[CURLOPT_POSTFIELDS] = json_encode($data);
    }

    if (isset($_SESSION['infinity_cookie'])) {
        $options[CURLOPT_COOKIE] = $_SESSION['infinity_cookie'];
    }

    curl_setopt_array($curl, $options);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    curl_close($curl);
    
    write_api_log($method, $url, $data, $response, $http_code);

    // InfinityFree Cookie Test Bypass
    if (strpos($response, 'aes.js') !== false && preg_match('/a=toNumbers\("([a-f0-9]+)"\)/', $response, $match_a)) {
        preg_match('/b=toNumbers\("([a-f0-9]+)"\)/', $response, $match_b);
        preg_match('/c=toNumbers\("([a-f0-9]+)"\)/', $response, $match_c);
        if (!empty($match_a[1]) && !empty($match_b[1]) && !empty($match_c[1])) {
            $key = hex2bin($match_a[1]);
            $iv = hex2bin($match_b[1]);
            $ciphertext = hex2bin($match_c[1]);
            $decrypted = openssl_decrypt($ciphertext, 'AES-128-CBC', $key, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING, $iv);
            $_SESSION['infinity_cookie'] = '__test=' . bin2hex($decrypted);
            return call_api($method, $endpoint, $data); // Retry request
        }
    }

    if ($err) {
        return ['status' => 'error', 'message' => 'cURL Error: ' . $err];
    } else {
        $decoded = json_decode($response, true);
        if ($decoded === null) {
            return ['status' => 'error', 'message' => 'Invalid JSON Response', 'raw_response' => $response, 'http_code' => $http_code];
        }
        $decoded['http_code'] = $http_code;
        return $decoded;
    }
}

function upload_file_api($endpoint, $file_path, $original_name = '', $mime_type = '', $field_name = 'foto')
{
    $url = get_api_base_url() . $endpoint;
    $curl = curl_init();

    $headers = [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36'
    ];
    if (isset($_SESSION['token'])) {
        $headers[] = 'Authorization: Bearer ' . $_SESSION['token'];
    }

    $cookie_file = get_cookie_file();
    $cfile = new CURLFile($file_path, $mime_type, $original_name);
    $post_data = [
        $field_name => $cfile
    ];

    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $post_data,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_COOKIEJAR => $cookie_file,
        CURLOPT_COOKIEFILE => $cookie_file,
    ];

    if (isset($_SESSION['infinity_cookie'])) {
        $options[CURLOPT_COOKIE] = $_SESSION['infinity_cookie'];
    }

    curl_setopt_array($curl, $options);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    curl_close($curl);
    
    write_api_log('POST', $url, 'FILE UPLOAD', $response, $http_code);

    // InfinityFree Cookie Test Bypass
    if (strpos($response, 'aes.js') !== false && preg_match('/a=toNumbers\("([a-f0-9]+)"\)/', $response, $match_a)) {
        preg_match('/b=toNumbers\("([a-f0-9]+)"\)/', $response, $match_b);
        preg_match('/c=toNumbers\("([a-f0-9]+)"\)/', $response, $match_c);
        if (!empty($match_a[1]) && !empty($match_b[1]) && !empty($match_c[1])) {
            $key = hex2bin($match_a[1]);
            $iv = hex2bin($match_b[1]);
            $ciphertext = hex2bin($match_c[1]);
            $decrypted = openssl_decrypt($ciphertext, 'AES-128-CBC', $key, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING, $iv);
            $_SESSION['infinity_cookie'] = '__test=' . bin2hex($decrypted);
            return upload_file_api($endpoint, $file_path, $original_name, $mime_type, $field_name); // Retry request
        }
    }

    if ($err) {
        return ['status' => 'error', 'message' => 'cURL Error: ' . $err];
    } else {
        $decoded = json_decode($response, true);
        if ($decoded === null) {
            return ['status' => 'error', 'message' => 'Invalid JSON Response', 'raw_response' => $response, 'http_code' => $http_code];
        }
        $decoded['http_code'] = $http_code;
        return $decoded;
    }
}
?>