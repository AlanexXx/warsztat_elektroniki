<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⚡ Warsztat Elektroniki</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script>
        // Domyślnie wymuszamy ciemny motyw dla lepszego efektu szkła, ale zachowujemy wybór
        const savedTheme = localStorage.getItem('theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
</head>
<body class="glass-body">

    <!-- Pływająca nawigacja "Pigułka" -->
    <nav class="glass-navbar">
        <a href="index.php" class="navbar-brand">⚡ Warsztat</a>
        <ul class="navbar-nav">
            <li><a href="index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">Strona główna</a></li>
            <li><a href="lista.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'lista.php' ? 'active' : '' ?>">Lista Zgłoszeń</a></li>
            <li><a href="dodaj.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dodaj.php' ? 'active' : '' ?>">+ Dodaj Nowe</a></li>
            <li class="nav-divider">
                <button id="themeToggle" class="theme-toggle-btn" title="Przełącz motyw">
                    <span id="themeIcon">☀️</span>
                </button>
            </li>
            <li class="nav-divider">
                <form action="logout.php" method="POST" style="display:inline; margin: 0; padding: 0;">
                    <?= csrf_field(); ?> 
                    <button type="submit" class="nav-link nav-logout-text" style="background: none; border: none; cursor: pointer; font-family: inherit; font-size: inherit; font-weight: 600;">Wyloguj (<?= htmlspecialchars($_SESSION['user_login'] ?? '', ENT_QUOTES, 'UTF-8') ?>)</button>
                </form>
            </li>
        </ul>
    </nav>

    <!-- Główny kontener zaczyna się poniżej nawigacji -->
    <div class="container <?= isset($containerClass) ? $containerClass : '' ?>">