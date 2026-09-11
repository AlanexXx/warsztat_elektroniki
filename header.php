<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⚡ Warsztat Elektroniki</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script>
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
        const CSRF_TOKEN = <?= json_encode($_SESSION['csrf_token'] ?? ''); ?>;
    </script>
</head>
<body>
<div class="container <?= isset($containerClass) ? $containerClass : '' ?>">
    <nav class="navbar">
        <a href="index.php" class="navbar-brand">⚡ Warsztat Elektroniki</a>
        <ul class="navbar-nav">
            <li><a href="index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">Strona główna</a></li>
            <li><a href="lista.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'lista.php' ? 'active' : '' ?>">Lista Zgłoszeń</a></li>
            <li><a href="dodaj.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dodaj.php' ? 'active' : '' ?>">+ Dodaj Nowe</a></li>
            <li class="nav-divider">
                <button id="themeToggle" class="theme-toggle-btn" title="Przełącz motyw">
                    <span id="themeIcon">🌙</span>
                </button>
            </li>
            <li class="nav-divider">
                <a href="logout.php" class="nav-link nav-logout-text">Wyloguj (<?= htmlspecialchars($_SESSION['user_login'] ?? '') ?>)</a>
            </li>
        </ul>
    </nav>
