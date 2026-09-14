<?php
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (csrf_verify()) {
        session_unset();
        session_destroy();
    }
}

header("Location: login.php");
exit;
?>