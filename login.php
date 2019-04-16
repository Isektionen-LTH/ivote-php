<?php

if (isset($_GET['uuid'])) {
    header('Location: /api/login' . (isset($_GET['uuid']) ? "?uuid=${$_GET['uuid']}" : ''));
    die();
}

session_start();

$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'public';

require_once 'templates/header.php';
?>


<div class="loginWrapper">
    <h3>Logga in</h3>
    <hr>
    <form action="/api/login" method="GET">
        <div class="form-group">
            <label for="uuidField">Id</label>
            <input name="uuid" type="text" class="form-control" id="uuidField" placeholder="Ex. c46a7cb7-2100...">
        </div>
        <button type="submit" class="btn btn-primary">Logga in</button>
    </form>
    <hr>
    <span>eller:</span>
    <form action="/api/login" method="POST">
        <div class="form-group">
            <label for="passwordField">Lösenord</label>
            <input name="password" type="password" class="form-control" id="passwordField">
        </div>
        <button type="submit" class="btn btn-sm btn-outline-primary">Logga in</button>
    </form>
</div>


<?php

require_once 'templates/footer.php';
