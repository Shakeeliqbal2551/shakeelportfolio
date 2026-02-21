<?php
date_default_timezone_set('Asia/Karachi');
require_once '../config.php';

$ip_address = getClientIP();
$country = getCountryFromIP($ip_address);
$location = getLocationFromIP($ip_address);
$city = $location['city'] ?? 'Unknown';
$region = $location['region'] ?? 'Unknown';
$latitude = $location['latitude'] ?? '';
$longitude = $location['longitude'] ?? '';

$page_visited = $_GET['page'] ?? 'home';
$user_agent = $_SERVER['HTTP_USER_AGENT'];
$browser = getBrowserName($user_agent);
$os = getOS($user_agent);
$device_type = getDeviceType($user_agent);
$screen_resolution = $_GET['screen'] ?? 'Unknown';
$timestamp = date('Y-m-d H:i:s');

// Check if repeat visitor
$check_ip = $conn->query("SELECT id FROM visitor_logs WHERE ip_address = '$ip_address' LIMIT 1");
$is_repeat = ($check_ip->num_rows > 0) ? 1 : 0;

$sql = "INSERT INTO visitor_logs 
        (ip_address, country, city, region, latitude, longitude, page_visited, user_agent, browser, os, device_type, screen_resolution, is_repeat_visitor, visit_time) 
        VALUES 
        ('$ip_address', '$country', '$city', '$region', '$latitude', '$longitude', '$page_visited', '$user_agent', '$browser', '$os', '$device_type', '$screen_resolution', '$is_repeat', '$timestamp')";

if ($conn->query($sql) === TRUE) {
    echo "logged";
} else {
    echo "error";
}
?>