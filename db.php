<?php
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!function_exists('csrf_rotate')) {
    function csrf_rotate(): void {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field() {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('csrf_verify')) {
    function csrf_verify(): bool {
        $token = $_POST['csrf_token'] ?? '';
        $sessionToken = $_SESSION['csrf_token'] ?? '';

        return is_string($token)
            && is_string($sessionToken)
            && $token !== ''
            && $sessionToken !== ''
            && hash_equals($sessionToken, $token);
    }
}

$host = 'localhost';
$dbname = 'warsztat';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    error_log("Błąd bazy danych: " . $e->getMessage());
    die("<div class='db-error-message'><b>Krytyczny błąd:</b> Nie można połączyć się z bazą danych. Sprawdź konfigurację.</div>");
}

// Globalne zabezpieczenie sesji - przekierowanie na login.php
$currentPage = basename($_SERVER['PHP_SELF']);
if ($currentPage !== 'login.php' && !isset($_SESSION['zalogowany'])) {
    header("Location: login.php");
    exit;
}
?>
