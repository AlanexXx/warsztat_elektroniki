<?php
require_once 'db.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    die("Brak ID zgłoszenia.");
}

try {
    $stmt = $pdo->prepare("SELECT * FROM zlecenia WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $dane = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dane) {
        die("Zgłoszenie nie istnieje.");
    }

    $stmt_testy = $pdo->prepare("SELECT klucz_testu, status FROM zlecenie_testy WHERE zlecenie_id = :id");
    $stmt_testy->execute([':id' => $id]);
    $wynikiTestow = $stmt_testy->fetchAll(PDO::FETCH_KEY_PAIR);

} catch(PDOException $e) {
    error_log("Błąd bazy danych (raport.php): " . $e->getMessage());
    die("Wystąpił wewnętrzny błąd komunikacji z bazą danych.");
}

if (!isset($kategorieTestow) || !is_array($kategorieTestow)) {
    $kategorieTestow = require 'kategorie_testow.php';
}
$kategoria = $dane['deviceCategory'] ?? 'Inne';
$dostepneTesty = $kategorieTestow[$kategoria] ?? $kategorieTestow['Inne'];

$containerClass = 'container-large';
require_once 'header.php';
?>

<div class="no-print" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
    <a href="obsluga.php?id=<?php echo htmlspecialchars($id); ?>" class="btn btn-secondary">← Powrót do zlecenia</a>
    <button onclick="window.print()" class="btn btn-primary">🖨️ Drukuj / Zapisz jako PDF</button>
</div>

<div class="report-container">
    <div class="report-header">
        <div class="report-title">
            <h2>⚡ Warsztat Elektroniki</h2>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.85rem;">Raport Serwisowy Urządzenia</p>
        </div>
        <div style="text-align: right;">
            <h3 style="margin: 0; color: var(--accent-color);">Zgłoszenie #<?php echo htmlspecialchars($dane['id']); ?></h3>
            <span class="badge <?php echo ($dane['orderType'] === 'client') ? 'badge-client' : 'badge-own'; ?>">
                <?php echo ($dane['orderType'] === 'client') ? 'Zlecenie Klienta' : 'Własny Sprzęt'; ?>
            </span>
        </div>
    </div>

    <div class="report-section">
        <h4>Informacje o Sprzęcie</h4>
        <div class="info-grid">
            <div class="info-item"><label>Marka i Model</label><span><?php echo htmlspecialchars($dane['deviceBrandModel']); ?></span></div>
            <div class="info-item"><label>Numer Seryjny / VIN / IMEI</label><span><?php echo htmlspecialchars($dane['serialNumber'] ?: 'Brak'); ?></span></div>
            <div class="info-item"><label>Data przyjęcia</label><span><?php echo htmlspecialchars($dane['admissionDate']); ?></span></div>
            <?php if ($dane['orderType'] === 'client'): ?>
                <div class="info-item"><label>Koszt / Cena usługi</label><span style="font-weight: 700; color: var(--accent-color);"><?php echo !empty($dane['price']) ? number_format($dane['price'], 2, ',', ' ') . ' zł' : 'Nie podano'; ?></span></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="report-section">
        <h4>Opis Usterki i Stan Wizualny</h4>
        <div class="info-grid">
            <div class="info-item info-item-full"><label>Zgłoszona usterka</label><span><?php echo nl2br(htmlspecialchars($dane['faultDescription'] ?: 'Brak')); ?></span></div>
            <div class="info-item info-item-full"><label>Stan wizualny / Akcesoria</label><span><?php echo htmlspecialchars(($dane['visualCondition'] ?: 'Brak') . ' | Akcesoria: ' . ($dane['accessories'] ?: 'Brak')); ?></span></div>
        </div>
    </div>

    <div class="report-section">
        <h4>Wykonane Prace i Diagnoza Serwisowa</h4>
        <div class="info-grid">
            <div class="info-item info-item-full">
                <span style="white-space: pre-line;"><?php echo htmlspecialchars($dane['repairNotes'] ?: 'Brak wpisanych prac serwisowych.'); ?></span>
            </div>
        </div>
    </div>

    <div class="report-section">
        <h4>Wyniki Testów Urządzenia</h4>
        <table class="parts-table report-test-table">
            <thead>
                <tr>
                    <th>Element / Funkcja</th>
                    <th style="width: 150px; text-align: center;">Status testu</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dostepneTesty as $klucz => $nazwa): 
                    $statusTestu = $wynikiTestow[$klucz] ?? 'nie_sprawdzono';
                    $statusTekst = '⚪ Nie sprawdzono';
                    $statusStyl = 'color: var(--text-muted);';
                    if ($statusTestu === 'ok') {
                        $statusTekst = '🟢 Sprawne (OK)';
                        $statusStyl = 'color: var(--success-color); font-weight: 600;';
                    } elseif ($statusTestu === 'uszkodzone') {
                        $statusTekst = '🔴 Uszkodzone';
                        $statusStyl = 'color: var(--error-color); font-weight: 600;';
                    }
                ?>
                    <tr>
                        <td><b><?php echo htmlspecialchars($nazwa); ?></b></td>
                        <td style="text-align: center; <?php echo $statusStyl; ?>"><?php echo $statusTekst; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="report-section report-signatures" style="margin-top: 25px; display: flex; justify-content: space-between;">
        <div style="text-align: center; width: 180px; border-top: 1px solid var(--border-color); padding-top: 6px;">
            <span style="font-size: 0.75rem; color: var(--text-muted);">Podpis serwisu</span>
        </div>
        <div style="text-align: center; width: 180px; border-top: 1px solid var(--border-color); padding-top: 6px;">
            <span style="font-size: 0.75rem; color: var(--text-muted);">Podpis odbiorcy</span>
        </div>
    </div>
</div>

<?php require_once 'footer.php';?>
