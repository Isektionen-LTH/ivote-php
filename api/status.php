<?php

require_once 'database.php';

$pdo = Database::getConnection();

$stmt = $pdo->query('SELECT name, COUNT(votesession_uuid) AS vote_count
                               FROM votesessions
                               LEFT JOIN votes
                                   ON votes.votesession_uuid = votesessions.uuid
                               WHERE open = TRUE
                               GROUP BY name, votesession_uuid');

$voteSessions = $stmt->fetchAll();

$stmt = $pdo->query('SELECT COUNT(uuid) AS user_count FROM users;');
$userCount = $stmt->fetch()['user_count'];

header('Content-Type: application/json');
echo json_encode(['voteSessions' => $voteSessions, 'userCount' => $userCount]);