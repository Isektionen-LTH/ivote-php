<?php

session_start();

$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'public';

use Ramsey\Uuid\Uuid;

require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
require_once 'database.php';

if ($role === 'user') {

    $user_uuid = $_SESSION['uuid'];

    $pdo = Database::getConnection();
    $stmt = $pdo->prepare('SELECT votesessions.uuid, name, choices, COUNT(CASE user_uuid WHEN :user_uuid THEN 1 ELSE NULL END) AS voted
                                     FROM votesessions
                                     LEFT JOIN votes
                                     ON votesessions.uuid = votes.votesession_uuid
                                     WHERE open = TRUE
                                     GROUP BY votesessions.uuid, name, open, choices, votesession_uuid;');

    $stmt->execute(['user_uuid' => $user_uuid]);
    $votesessions = $stmt->fetchAll();

    // Deserialize choices
    for ($i = 0; $i < sizeof($votesessions); $i++) {
        $votesessions[$i]['choices'] = unserialize($votesessions[$i]['choices']);
    }

    header('Content-Type: application/json');
    echo json_encode($votesessions);
    die();
}

// Restricted to admin
if ($role !== 'admin') {
    http_response_code(403);
    die('Forbidden');
}

// GET all or one voting sessions
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    if (isset($_GET['uuid'])) {
        $uuid = $_GET['uuid'];
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT votesessions.uuid, name, open, choices, COUNT(votesession_uuid) AS vote_count
                                       FROM votesessions
                                       LEFT JOIN votes
                                       ON votesessions.uuid = votes.votesession_uuid
                                       WHERE votesessions.uuid = :uuid
                                       GROUP BY votesessions.uuid, name, open, choices, votesession_uuid;');
        $stmt->execute(['uuid' => $uuid]);
        $votesession = $stmt->fetch();

        // Deserialize choices
        $votesession['choices'] = unserialize($votesession['choices']);


        header('Content-Type: application/json');
        echo json_encode($votesession);
    } else {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT votesessions.uuid, name, open, choices, COUNT(votesession_uuid) AS vote_count
                                       FROM votesessions
                                       LEFT JOIN votes
                                       ON votesessions.uuid = votes.votesession_uuid
                                       GROUP BY votesessions.uuid, name, open, choices, votesession_uuid;');
        $votesessions = $stmt->fetchAll();

        // Deserialize choices
        for ($i = 0; $i < sizeof($votesessions); $i++) {
            $votesessions[$i]['choices'] = unserialize($votesessions[$i]['choices']);
        }


        header('Content-Type: application/json');
        echo json_encode($votesessions);
    }
}

// POST create/update votesession
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Confirm presence of required fields
    if (!isset($_POST['name'])) {
        http_response_code(400);
        die('Missing required parameters');
    }

    // Validate all fields
    if (strlen($_POST['name']) === 0 ||
        strlen($_POST['name']) > 249) {
        http_response_code(400);
        die('Invalid field');
    }

    // Transfer data to local variables and html escape
    $uuid = htmlspecialchars(isset($_POST['uuid']) ? $_POST['uuid'] : '');
    $name = htmlspecialchars($_POST['name']);

    $votesession = [
        'uuid' => $uuid,
        'name' => $name,
    ];

    // Init db connection
    $pdo = Database::getConnection();

    if (strlen($uuid) === 0) { // Insert
        // Generate new uuid v4
        try {
            $votesession['uuid'] = Uuid::uuid4()->toString();
        } catch (Exception $e) {
            http_response_code(500);
            die('Error while creating new uuid, probably missing some dependencies');
        }

        $stmt = $pdo->prepare('INSERT INTO votesessions (uuid, name) VALUES (:uuid, :name);');
        try {
            $stmt->execute($votesession);
        } catch (Exception $e) {
            http_response_code(409);
            die('Vote session with that name already exists');
        }

        http_response_code(201);
    } else { // Update existing

        if (!isset($_POST['choices']) || !is_array($_POST['choices'])) {
            http_response_code(400);
            die('Parameter "choices" missing or not an array');
        }

        // TODO Sanitize input (html escape)
        $votesession['choices'] = serialize($_POST['choices']);

        // Only accept changes when vote session has no votes
        $stmt = $pdo->prepare('SELECT COUNT(votesession_uuid) as vote_count FROM votes WHERE votesession_uuid = :uuid;');
        $stmt->execute(['uuid' => $votesession['uuid']]);
        if ($stmt->fetch()['vote_count'] !== 0) {
            http_response_code(401);
            die('Not allowed change vote session when it has votes');
        }

        $stmt = $pdo->prepare('UPDATE votesessions
                                             SET name = :name, choices = :choices
                                             WHERE uuid = :uuid;');
        $stmt->execute($votesession);

        if ($stmt->rowCount() === 0) {
            $stmt = $pdo->prepare('SELECT uuid FROM votesessions WHERE uuid = :uuid;');
            $stmt->execute(['uuid' => $uuid]);

            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                die('No such votesession found');
            }
        } else {
            http_response_code(200);
        }
    }

    // Get updated object
    $stmt = $pdo->prepare('SELECT votesessions.uuid, name, open, choices, COUNT(votesession_uuid) AS vote_count
                                     FROM votesessions
                                     LEFT JOIN votes
                                     ON votesessions.uuid = votes.votesession_uuid
                                     WHERE votesessions.uuid = :uuid
                                     GROUP BY votes.votesession_uuid;');
    $stmt->execute(['uuid' => $votesession['uuid']]);

    $votesession = $stmt->fetch();

    // Deserialize choices
    $votesession['choices'] = unserialize($votesession['choices']);

    // Return the created/updated user
    header('Content-Type: application/json');
    echo json_encode($votesession);
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {

    if (isset($_GET['uuid'])) { // Delete a single votesession and votes
        $uuid = $_GET['uuid'];

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM votes WHERE votesession_uuid = :uuid;');
        $stmt->execute(['uuid' => $uuid]);
        $stmt = $pdo->prepare('DELETE FROM votesessions WHERE uuid = :uuid;');
        $stmt->execute(['uuid' => $uuid]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            die("No votesession with uuid '$uuid' found");
        }
    } else { // Delete all vote sessions and votes
        $pdo = Database::getConnection();
        $pdo->exec('TRUNCATE TABLE votes;');
        $pdo->exec('TRUNCATE TABLE votesessions;');
    }

    http_response_code(200);
}
