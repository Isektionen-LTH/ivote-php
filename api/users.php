<?php

session_start();

// Restricted to admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die('Forbidden');
}

use Ramsey\Uuid\Uuid;

require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
require_once 'database.php';

// GET all users
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $pdo = Database::getConnection();
    $stmt = $pdo->query('SELECT uuid, name, email FROM users ORDER BY name ASC;');
    $users = $stmt->fetchAll();

    header('Content-Type: application/json');
    echo json_encode($users);
}

// POST create/update single user
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Confirm presence of all fields
    if (!isset($_POST['name']) ||
        !isset($_POST['email'])) {
        http_response_code(400);
        die('All user fields must be specified');
    }

    // Validate all fields
    if (strlen($_POST['name']) === 0 ||
        strlen($_POST['name']) > 256 ||
        strlen($_POST['email']) === 0 ||
        strlen($_POST['email']) > 256) {
        http_response_code(400);
        die('Invalid field');
    }

    // Transfer data to local variables and html escape
    $uuid = htmlspecialchars(isset($_POST['uuid']) ? $_POST['uuid'] : '');
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);

    $user = [
        'uuid' => $uuid,
        'name' => $name,
        'email' => $email
    ];

    // Init db connection
    $pdo = Database::getConnection();

    if (strlen($uuid) === 0) { // Insert
        // Generate new uuid v4
        try {
            $user['uuid'] = Uuid::uuid4()->toString();
        } catch (Exception $e) {
            http_response_code(500);
            die('Error while creating new uuid, probably missing some dependencies');
        }

        $stmt = $pdo->prepare('INSERT INTO users (uuid, name, email) VALUES (:uuid, :name, :email);');
        $stmt->execute($user);

        // Send credentials via email
        require_once 'emailservice.php';

        $emailService = new EmailService();
        $emailService->sendEmail($uuid, $name, $email);

        http_response_code(201);
    } else { // Update existing
        $stmt = $pdo->prepare('UPDATE users
                                             SET name = :name, email = :email
                                             WHERE uuid = :uuid;');
        $stmt->execute($user);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            die('No such user found');
        } else {
            http_response_code(200);
        }
    }

    // Return the created/updated user
    header('Content-Type: application/json');
    echo json_encode($user);
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {

    if (isset($_GET['uuid'])) { // Delete a single user
        $uuid = $_GET['uuid'];

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM users WHERE uuid = :uuid');
        $stmt->execute(['uuid' => $uuid]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            die("No user with uuid '$uuid' found'");
        }
    } else { // Delete all users
        $pdo = Database::getConnection();
        $stmt = $pdo->exec('TRUNCATE TABLE users;');
    }

    http_response_code(200);
}
