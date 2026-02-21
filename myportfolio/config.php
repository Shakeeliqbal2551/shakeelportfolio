<?php
$db_host = 'localhost';
$db_user = 'u455412991_shakeel_portfo'; // CHANGE THIS
$db_pass = 'JuuS@C>l1'; // CHANGE THIS
$db_name = 'u455412991_shakeel_portfo'; // CHANGE THIS

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return trim($ip);
}

function getCountryFromIP($ip) {
    $url = "http://ip-api.com/json/{$ip}";
    $response = @file_get_contents($url);
    $data = json_decode($response, true);
    return $data['country'] ?? 'Unknown';
}

function getBrowserName($user_agent) {
    if (strpos($user_agent, 'Firefox') !== false) return 'Firefox';
    if (strpos($user_agent, 'Chrome') !== false) return 'Chrome';
    if (strpos($user_agent, 'Safari') !== false) return 'Safari';
    if (strpos($user_agent, 'Opera') !== false || strpos($user_agent, 'OPR') !== false) return 'Opera';
    if (strpos($user_agent, 'Trident') !== false) return 'Internet Explorer';
    if (strpos($user_agent, 'Edge') !== false) return 'Edge';
    return 'Unknown';
}

function getOS($user_agent) {
    if (preg_match('/windows|win32/i', $user_agent)) return 'Windows';
    if (preg_match('/macintosh|mac os x/i', $user_agent)) return 'MacOS';
    if (preg_match('/linux/i', $user_agent)) return 'Linux';
    if (preg_match('/iphone|ipad|ipod/i', $user_agent)) return 'iOS';
    if (preg_match('/android/i', $user_agent)) return 'Android';
    return 'Unknown';
}

function getDeviceType($user_agent) {
    if (preg_match('/mobile|android|iphone|ipod|blackberry|iemobile|opera mini/i', $user_agent)) return 'Mobile';
    if (preg_match('/tablet|ipad|playbook|silk|nexus 7|nexus 10|xoom/i', $user_agent)) return 'Tablet';
    return 'Desktop';
}
function getLocationFromIP($ip) {
    if ($ip === 'Unknown' || $ip === '127.0.0.1') {
        return ['city' => 'Local', 'region' => 'Local', 'latitude' => '', 'longitude' => ''];
    }
    
    try {
        // Try ipapi.co first
        $response = @file_get_contents("https://ipapi.co/{$ip}/json/");
        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['city']) && $data['city']) {
                return [
                    'city' => $data['city'] ?? 'Unknown',
                    'region' => $data['region'] ?? 'Unknown',
                    'latitude' => $data['latitude'] ?? '',
                    'longitude' => $data['longitude'] ?? ''
                ];
            }
        }
        
        // Fallback to ip-api.com
        $response = @file_get_contents("http://ip-api.com/json/{$ip}");
        if ($response) {
            $data = json_decode($response, true);
            if ($data['status'] === 'success') {
                return [
                    'city' => $data['city'] ?? 'Unknown',
                    'region' => $data['region'] ?? 'Unknown',
                    'latitude' => $data['lat'] ?? '',
                    'longitude' => $data['lon'] ?? ''
                ];
            }
        }
    } catch (Exception $e) {
        return ['city' => 'Unknown', 'region' => 'Unknown', 'latitude' => '', 'longitude' => ''];
    }
    
    return ['city' => 'Unknown', 'region' => 'Unknown', 'latitude' => '', 'longitude' => ''];
}
?>