<?php

session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    die('Forbidden');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['uuid'])) {
    http_response_code(400);
    die('Invalid request');
}

require_once 'emailservice.php';
require_once 'database.php';


$uuid = $_POST['uuid'];

$pdo = Database::getConnection();

$stmt = $pdo->prepare('SELECT name, email FROM users WHERE uuid = :uuid;');
$stmt->execute(['uuid' => $uuid]);

if ($stmt->rowCount() === 0) {
    http_response_code(404);
    die('User not found');
}

// Get email address
$row = $stmt->fetch();
$name = $row['name'];
$email = $row['email'];

// Send mail
$emailService = new EmailService();
$emailService->sendEmail($uuid, $name, $email);

http_response_code(204);
