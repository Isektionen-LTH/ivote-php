<?php

session_start();

// Restricted to users
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    http_response_code(403);
    die('Forbidden');
}

use Ramsey\Uuid\Uuid;

require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
require_once 'database.php';

// Validate form
if (!isset($_POST['votesession_uuid']) ||
    !isset($_POST['value']) ||
    !is_numeric($_POST['value'])) {
    http_response_code(400);
    die('Invalid request');
}

$votesession_uuid = $_POST['votesession_uuid'];
$user_uuid = $_SESSION['uuid'];
$value = (int) $_POST['value'];

$pdo = Database::getConnection();

// TODO Could be done more efficiently, having problems with foreign keys though
// Check if user still exists
$stmt = $pdo->prepare('SELECT uuid FROM users WHERE uuid = :user_uuid;');
$stmt->execute(['user_uuid' => $user_uuid]);
if ($stmt->rowCount() === 0) {
    http_response_code(404);
    die('Session points to non-existing user');
}

// Check if vote session exists
$stmt = $pdo->prepare('SELECT uuid, choices FROM votesessions WHERE uuid = :votesession_uuid;');
$stmt->execute(['votesession_uuid' => $votesession_uuid]);
if ($stmt->rowCount() === 0) {
    http_response_code(404);
    die('Vote session not found');
}
$votesession = $stmt->fetch();

// Check if user has already voted
$stmt = $pdo->prepare('SELECT uuid FROM votes WHERE votesession_uuid = :votesession_uuid AND user_uuid = :user_uuid;');
$stmt->execute(['votesession_uuid' => $votesession_uuid, 'user_uuid' => $user_uuid]);
if ($stmt->rowCount() !== 0) {
    http_response_code(404);
    die('User has already voted');
}

// Validate value, -1 is a blank vote
$choices = unserialize($votesession['choices']);
if ($value >= sizeof($choices) || $value < -1) {
    http_response_code(400);
    die('Vote is not valid');
}

// Generate new uuid
$uuid = '';
try {
    $uuid = Uuid::uuid4()->toString();
} catch (Exception $e) {
    http_response_code(500);
    die('Error while generating uuid, probably missing dependencies');
}

$vote = [
    'uuid' => $uuid,
    'votesession_uuid' => $votesession_uuid,
    'user_uuid' => $user_uuid,
    'value' => $value
];

// Insert new vote
$stmt = $pdo->prepare('INSERT INTO votes (uuid, votesession_uuid, user_uuid, value)
                                 VALUES (:uuid, :votesession_uuid, :user_uuid, :value);');
$stmt->execute($vote);
