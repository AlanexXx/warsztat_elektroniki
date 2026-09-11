<?php
require_once 'db.php';

$id = null;
if (isset($_GET['id'])) {
    $id = $_GET['id'];
}

if (!$id) {
    die("Brak ID zgłoszenia. Wróć do <a href='lista.php'>listy</a>.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['klucz_testu']) && isset($_POST['status'])) {
    $klucz = $_POST['klucz_testu'];
    $status = $_POST['status'];

    $chk = $pdo->prepare("SELECT id FROM zlecenie_testy WHERE zlecenie_id = :id AND klucz_testu = :klucz");
    $chk->execute(array(':id' => $id, ':klucz' => $klucz));
    
    if ($chk->fetch()) {
        $upd = $pdo->prepare("UPDATE zlecenie_testy SET status = :status WHERE zlecenie_id = :id AND klucz_testu = :klucz");
        $upd->execute(array(':status' => $status, ':id' => $id, ':klucz' => $klucz));
    } else {
        $ins = $pdo->prepare("INSERT INTO zlecenie_testy (zlecenie_id, klucz_testu, status) VALUES (:id, :klucz, :status)");
        $ins->execute(array(':id' => $id, ':klucz' => $klucz, ':status' => $status));
    }

    header('Content-Type: application/json');
    echo json_encode(array('success' => true));
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM zlecenia WHERE id = :id");
$stmt->execute(array(':id' => $id));
$zlecenie = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$zlecenie) {
    die("Zgłoszenie nie istnieje.");
}

$kategorieTestow = array(
    'Laptop / Komputer' => array(
        'matryca' => 'Matryca',
        'kamerka' => 'Kamerka',
        'klawiatura' => 'Klawiatura',
        'touchpad' => 'Touchpad',
        'glosniki' => 'Głośniki',
        'porty' => 'Porty',
        'wifi' => 'WiFi',
        'bluetooth' => 'Bluetooth',
        'ladowanie_baterii' => 'Ładowanie baterii',
        'praca_na_baterii' => 'Praca na baterii',
        'klawisze_fizyczne' => 'Klawisze fizyczne',
        'chlodzenie' => 'Chłodzenie',
        'testy_obciazeniowe' => 'Testy obciążeniowe'
    ),
    'Smartfon / Tablet' => array(
        'wyswietlacz' => 'Wyświetlacz',
        'dotyk' => 'Dotyk',
        'glosniki' => 'Głośniki',
        'mikrofony' => 'Mikrofony',
        'kamery' => 'Kamery',
        'wifi' => 'WiFi',
        'bluetooth' => 'Bluetooth',
        'zasieg_gsm' => 'Zasięg GSM',
        'ladowanie_baterii' => 'Ładowanie baterii',
        'komunikacja_usb' => 'Komunikacja USB',
        'dzialanie_na_baterii' => 'Działanie na baterii',
        'biometria' => 'Biometria',
        'karta_sd' => 'Karta SD',
        'przyciski_fizyczne' => 'Przyciski fizyczne'
    ),
    'Audio / Wzmacniacz' => array(
        'wejscia_sygnalowe' => 'Wejścia sygnałowe',
        'wyjscia_glosnikow' => 'Wyjścia głośników',
        'potencjometry' => 'Potencjometry',
        'przyciski_fizyczne' => 'Przyciski fizyczne',
        'radio_fm' => 'Radio FM'
    ),
    'Konsola' => array(
        'naped_cd' => 'Napęd CD',
        'porty_usb' => 'Porty USB',
        'hdmi' => 'HDMI',
        'rozdzielczosci' => 'Rozdzielczości',
        'chlodzenie' => 'Chłodzenie',
        'komunikacja_z_padem' => 'Komunikacja z padem',
        'wifi' => 'WiFi',
        'bluetooth' => 'Bluetooth',
        'lan' => 'LAN',
        'obciazenie' => 'Obciążenie'
    ),
    'Inne' => array(
        'zasilanie' => 'Zasilanie',
        'komunikacja' => 'Komunikacja',
        'ogolna_poprawnosc' => 'Ogólna poprawność działania'
    )
);

$kategoria = 'Inne';
if (isset($zlecenie['deviceCategory'])) {
    $kategoria = $zlecenie['deviceCategory'];
}

$dostepneTesty = $kategorieTestow['Inne'];
if (isset($kategorieTestow[$kategoria])) {
    $dostepneTesty = $kategorieTestow[$kategoria];
}

$wynikiTestow = array();
$res = $pdo->prepare("SELECT klucz_testu, status FROM zlecenie_testy WHERE zlecenie_id = :id");
$res->execute(array(':id' => $id));
while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
    $wynikiTestow[$row['klucz_testu']] = $row['status'];
}

require_once 'header.php';
?>

<div class="header-bar">
    <h1>🔍 Check-lista Testowa: #<?php echo htmlspecialchars($zlecenie['id']); ?> (<?php echo htmlspecialchars($kategoria); ?>)</h1>
    <a href="obsluga.php?id=<?php echo htmlspecialchars($zlecenie['id']); ?>" class="btn btn-secondary">← Powrót do zlecenia</a>
</div>

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
                            <select onchange="zmienStatus(this, '<?php echo htmlspecialchars($klucz); ?>', <?php echo $id; ?>)" class="select-status-box">
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