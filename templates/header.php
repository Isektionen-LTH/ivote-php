<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>iVote</title>

    <link rel="stylesheet" href="/css/bootstrap.min.css">
    <link rel="stylesheet" href="/css/fontawesome.css">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<nav id="headNavbar" class="navbar navbar-expand-lg navbar-dark shadow-sm">
    <a class="navbar-brand" href="/">iVote</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav">
            <?php if ($role === 'admin') : ?>
            <li class="nav-item">
                <a class="nav-link" href="/status">Status</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/users">Användare</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/votesessions">Omröstningar</a>
            </li>
            <?php endif; ?>
            <?php if ($role !== 'public') : ?>
            <li class="nav-item">
                <a class="nav-link" href="/logout">Logga ut</a>
            </li>
            <?php endif; ?>
            <?php if ($role === 'public') : ?>
            <li class="nav-item">
                <a class="nav-link" href="/login">Logga in</a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>