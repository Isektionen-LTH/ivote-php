<?php

session_start();

// TODO Increase session length

require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';
require_once 'database.php';

if (isset($_SESSION['role']) && $_SESSION['role'] !== 'public') {
    header('Location: /');
    die();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') { // Voting user login
    if (!isset($_GET['uuid'])) {
        http_response_code(400);
        die('User uuid must be specified');
    }

    $uuid = $_GET['uuid'];

    $pdo = Database::getConnection();
    $stmt = $pdo->prepare('SELECT uuid FROM users WHERE uuid = :uuid;');
    $stmt->execute(['uuid' => $uuid]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        die('User not found');
    } else {
        $_SESSION['role'] = 'user';
        $_SESSION['uuid'] = $uuid;
        header('Location: /');
    }
} else { // Admin login
    if (!isset($_POST['password'])) {
        http_response_code(400);
        die('Password must be specified');
    }

    $password = $_POST['password'];

    // Validate password
    $valid = password_verify($password, ADMIN_PASSWORD);
    if ($valid) {
        $_SESSION['role'] = 'admin';
        header('Location: /');
    } else {
        http_response_code(401);
        die('Invalid password');
    }
}
