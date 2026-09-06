<?php
// Mock form data
$ch = curl_init('http://localhost/WEBDEV01%20-%20Copy/api/admin.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'action' => 'edit_product',
    'id' => 1,
    'name' => 'Test',
    'category' => 'Pour Homme',
    'price' => 10,
    'stock' => 10,
    'status' => 'Active',
    'csrf_token' => 'dummy' // CSRF will fail, but we'll see if it returns JSON or HTML error
]);
// Ignore cookies to bypass CSRF, wait, we need admin session to not get 403.
// So let's just run it in the browser? Or I can temporarily comment out `require_admin()` in api/admin.php? No.
