<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $haslo = $_POST['haslo'] ?? '';
    $hash = password_hash($haslo, PASSWORD_DEFAULT);
    echo "<b>Wygenerowany hash (skopiuj do bazy danych):</b><br><br>";
    echo "<code style='background:#eee;padding:10px;display:block;'>$hash</code>";
}
?>
<form method="POST">
    Wpisz hasło do zaszyfrowania: <input type="text" name="haslo" required>
    <button type="submit">Generuj hash</button>
</form>