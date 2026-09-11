<?php
require_once 'db.php';

if (!isset($kategorieTestow) || !is_array($kategorieTestow)) {
    $kategorieTestow = require 'kategorie_testow.php';
}
$dozwoloneStatusy = ['ok', 'uszkodzone', 'nie_sprawdzono'];

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($id === null || $id === false) {
    die("Brak ID zgłoszenia. Wróć do <a href='lista.php'>listy</a>.");
}

$stmt = $pdo->prepare("SELECT * FROM zlecenia WHERE id = :id");
$stmt->execute([':id' => $id]);
$zlecenie = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$zlecenie) {
    die("Zgłoszenie nie istnieje.");
}

$kategoria = $zlecenie['deviceCategory'] ?? 'Inne';
$dostepneTesty = $kategorieTestow[$kategoria] ?? $kategorieTestow['Inne'];
$dozwoloneKluczeTestow = array_keys($dostepneTesty);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['klucz_testu']) && isset($_POST['status'])) {
    header('Content-Type: application/json');

    if (!csrf_verify()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Nieprawidłowy token CSRF']);
        exit;
    }

    $klucz = $_POST['klucz_testu'];
    $status = $_POST['status'];

    if (!in_array($status, $dozwoloneStatusy, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Nieprawidłowy status']);
        exit;
    }

    if (!in_array($klucz, $dozwoloneKluczeTestow, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Nieprawidłowy klucz testu']);
        exit;
    }

    $chk = $pdo->prepare("SELECT id FROM zlecenie_testy WHERE zlecenie_id = :id AND klucz_testu = :klucz");
    $chk->execute([':id' => $id, ':klucz' => $klucz]);

    if ($chk->fetch()) {
        $upd = $pdo->prepare("UPDATE zlecenie_testy SET status = :status WHERE zlecenie_id = :id AND klucz_testu = :klucz");
        $upd->execute([':status' => $status, ':id' => $id, ':klucz' => $klucz]);
    } else {
        $ins = $pdo->prepare("INSERT INTO zlecenie_testy (zlecenie_id, klucz_testu, status) VALUES (:id, :klucz, :status)");
        $ins->execute([':id' => $id, ':klucz' => $klucz, ':status' => $status]);
    }

    echo json_encode(['success' => true]);
    exit;
}
$wynikiTestow = [];
$res = $pdo->prepare("SELECT klucz_testu, status FROM zlecenie_testy WHERE zlecenie_id = :id");
$res->execute([':id' => $id]);
while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
    $wynikiTestow[$row['klucz_testu']] = $row['status'];
}

require_once 'header.php';
?>

<div class="header-bar">
    <h1>🔍 Check-lista Testowa: #<?php echo htmlspecialchars($zlecenie['id']); ?> (<?php echo htmlspecialchars($kategoria); ?>)</h1>
    <a href="obsluga.php?id=<?php echo htmlspecialchars($zlecenie['id']); ?>" class="btn btn-secondary">← Powrót do zlecenia</a>
</div>

<input type="hidden" id="csrfTokenAjax" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

<div class="info-card">
    <p class="test-subtitle"><b>Sprzęt:</b> <?php echo htmlspecialchars($zlecenie['deviceBrandModel']); ?></p>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Funkcja / Element do sprawdzenia</th>
                    <th class="th-status-col">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dostepneTesty as $klucz => $nazwa) {
                    $aktualnyStatus = 'nie_sprawdzono';
                    if (isset($wynikiTestow[$klucz])) {
                        $aktualnyStatus = $wynikiTestow[$klucz];
                    }
                ?>
                    <tr>
                        <td><b><?php echo htmlspecialchars($nazwa); ?></b></td>
                        <td class="td-status-col">
                            <select onchange="zmienStatus(this, '<?php echo htmlspecialchars($klucz); ?>', <?php echo (int) $id; ?>)" class="select-status-box">
                                <option value="nie_sprawdzono" <?php if ($aktualnyStatus === 'nie_sprawdzono') { echo 'selected'; } ?>>⚪ Nie sprawdzono</option>
                                <option value="ok" <?php if ($aktualnyStatus === 'ok') { echo 'selected'; } ?>>🟢 Sprawne (OK)</option>
                                <option value="uszkodzone" <?php if ($aktualnyStatus === 'uszkodzone') { echo 'selected'; } ?>>🔴 Uszkodzone</option>
                            </select>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>
