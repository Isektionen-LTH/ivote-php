<?php

session_start();

// Restricted to admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die('Forbidden');
}

require_once 'database.php';

if (!isset($_POST['uuid'])) {
    http_response_code(400);
    die('Invalid request');
}

$uuid = $_POST['uuid'];

$pdo = Database::getConnection();

$stmt = $pdo->prepare('UPDATE votesessions SET open = TRUE WHERE uuid = :uuid;');
$stmt->execute(['uuid' => $uuid]);

if ($stmt->rowCount() === 0) {
    http_response_code(404);
    die('No such votesession found');
}

$stmt = $pdo->prepare('SELECT votesessions.uuid, name, open, choices, COUNT(votesession_uuid) AS vote_count
                                       FROM votesessions
                                       LEFT JOIN votes
                                       ON votesessions.uuid = votes.votesession_uuid
                                       WHERE votesessions.uuid = :uuid
                                       GROUP BY votesessions.uuid, name, open, choices, votesession_uuid;');
$stmt->execute(['uuid' => $uuid]);

$votesession = $stmt->fetch();
$votesession['choices'] = unserialize($votesession['choices']);

header('Content-Type: application/json');
echo json_encode($votesession);
