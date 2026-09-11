<?php
require_once 'db.php';

if (isset($_SESSION['zalogowany']) && $_SESSION['zalogowany'] === true) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!csrf_verify()) {
        $error = "Nieprawidłowe żądanie. Odśwież stronę i spróbuj ponownie.";
    } else {
        $login = trim($_POST['login'] ?? '');
        $haslo = $_POST['haslo'] ?? '';

        if (!empty($login) && !empty($haslo)) {
            $stmt = $pdo->prepare("SELECT id, login, haslo FROM uzytkownicy WHERE login = :login");
            $stmt->execute([':login' => $login]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($haslo, $user['haslo'])) {
                session_regenerate_id(true);
                $_SESSION['zalogowany'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_login'] = $user['login'];
                header("Location: index.php");
                exit;
            } else {
                $error = "Nieprawidłowy login lub hasło.";
            }
        } else {
            $error = "Wypełnij wszystkie pola.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logowanie - Warsztat</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script>
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-header">
            <h2>⚡ Warsztat Elektroniki</h2>
            <p>Zaloguj się, aby uzyskać dostęp</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert error" style="margin-bottom: 20px; padding: 12px; font-size: 0.9rem;">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <?= csrf_field(); ?>
            <div class="form-group" style="margin-bottom: 15px;">
                <label>Login</label>
                <input type="text" name="login" required autofocus>
            </div>
            <div class="form-group" style="margin-bottom: 25px;">
                <label>Hasło</label>
                <input type="password" name="haslo" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Zaloguj się</button>
        </form>
    </div>
    <script src="script.js"></script>
</body>
</html>
