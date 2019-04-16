<?php

session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';

/*if (!isset($_SESSION['role']) || $_SESSION['role'] === 'admin') {
    http_response_code(401);
    die('You need to be signed in to change password');
}*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !isset($_POST['oldpass']) ||
    !isset($_POST['newpass'])) {
    http_response_code(400);
    die('Invalid request, missing parameters');
}

// Transfer to local vars
$old_pass = $_POST['oldpass'];
$new_pass = $_POST['newpass'];

// Validate password
if (!password_verify($old_pass, ADMIN_PASSWORD)) {
    http_response_code(401);
    die('Invalid password');
}

// Generate new password hash
$password_hash = password_hash($new_pass, PASSWORD_DEFAULT);

// Update password in file
$config_contents = file_get_contents( $_SERVER['DOCUMENT_ROOT'] . '/config.php');
$updated_contents = str_replace(ADMIN_PASSWORD, $password_hash, $config_contents);
file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/config.php', $updated_contents);

// Success, no content
http_response_code(204);
