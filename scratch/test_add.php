<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'admin';
require 'database/CSRF.php';
$token = CSRF::generate();
$cookie = session_name() . '=' . session_id();
session_write_close();

$ch = curl_init('http://localhost/WEBDEV01%20-%20Copy/admin/product_action.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'action' => 'add',
    'csrf_token' => $token,
    'name' => 'Curl Product 2',
    'description' => 'Test',
    'price' => '99.99',
    'stock' => '50',
    'category' => 'Uncategorized',
    'status' => 'Active'
]));
curl_setopt($ch, CURLOPT_COOKIE, $cookie);
$response = curl_exec($ch);
$info = curl_getinfo($ch);
echo "HTTP Status: " . $info['http_code'] . "\n";
echo "Location: " . $info['redirect_url'] . "\n";
echo "Response: " . $response . "\n";
