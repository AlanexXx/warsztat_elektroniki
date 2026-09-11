<?php
session_start();

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