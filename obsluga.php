<?php
require_once 'db.php';

if (!isset($kategorieTestow) || !is_array($kategorieTestow)) {
    $kategorieTestow = require 'kategorie_testow.php';
}

function dodajWpisHistorii($pdo, $zlecenieId, $typZmiany, $opis) {
    $uzytkownik = $_SESSION['user_login'] ?? 'System';
    $stmt = $pdo->prepare("INSERT INTO historia_zmian (zlecenie_id, uzytkownik, typ_zmiany, opis) VALUES (:zlecenie_id, :uzytkownik, :typ_zmiany, :opis)");
    $stmt->execute([
        ':zlecenie_id' => $zlecenieId,
        ':uzytkownik' => $uzytkownik,
        ':typ_zmiany' => $typZmiany,
        ':opis' => $opis
    ]);
}

$message = '';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($id === null || $id === false) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
}

if ($id === null || $id === false) {
    die("<div class='die-message'>Brak ID zgłoszenia. Wróć do <a href='lista.php'>listy</a>.</div>");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    $isAjax = (isset($_POST['ajax']) && $_POST['ajax'] === '1')
        || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

    if (!csrf_verify()) {
        if ($isAjax) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Nieprawidłowy token CSRF']);
            exit;
        }

        $message = "<div class='alert error'>❌ Nieprawidłowe żądanie. Odśwież stronę i spróbuj ponownie.</div>";
    } else {
        try {
            $stmt_chk = $pdo->prepare("SELECT orderType FROM zlecenia WHERE id = :id");
            $stmt_chk->execute([':id' => $id]);
            $row_chk = $stmt_chk->fetch(PDO::FETCH_ASSOC);
            $isClient = ($row_chk && $row_chk['orderType'] === 'client');

            if ($action === 'section1') {
                if ($isClient) {
                    $sql = "UPDATE zlecenia SET clientName = :clientName, clientPhone = :clientPhone, clientEmail = :clientEmail, price = :price WHERE id = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':id' => $id,
                        ':clientName' => $_POST['clientName'] ?? null,
                        ':clientPhone' => $_POST['clientPhone'] ?? null,
                        ':clientEmail' => $_POST['clientEmail'] ?? null,
                        ':price' => !empty($_POST['price']) ? $_POST['price'] : null
                    ]);
                } else {
                    $sql = "UPDATE zlecenia SET purchasePrice = :purchasePrice, additionalCost = :additionalCost, purchaseSource = :purchaseSource WHERE id = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':id' => $id,
                        ':purchasePrice' => !empty($_POST['purchasePrice']) ? $_POST['purchasePrice'] : null,
                        ':additionalCost' => !empty($_POST['additionalCost']) ? $_POST['additionalCost'] : null,
                        ':purchaseSource' => $_POST['purchaseSource'] ?? null
                    ]);
                }
                dodajWpisHistorii($pdo, $id, 'Dane podstawowe', 'Zaktualizowano dane podstawowe / zakup');
                $message = "<div class='alert success'>✅ Dane podstawowe/zakupu zostały zaktualizowane!</div>";
            } elseif ($action === 'section_sale') {
                $salePrice = !empty($_POST['salePrice']) ? $_POST['salePrice'] : null;
                $saleDate = !empty($_POST['saleDate']) ? $_POST['saleDate'] : null;
            
                $extraSql = "";
                $params = [
                    ':id' => $id,
                    ':salePrice' => $salePrice,
                    ':saleDate' => $saleDate
                ];
            
                if ($salePrice !== null && $saleDate !== null) {
                    $extraSql = ", initialStatus = 'Sprzedane'";
                }

                $sql = "UPDATE zlecenia SET salePrice = :salePrice, saleDate = :saleDate $extraSql WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                dodajWpisHistorii($pdo, $id, 'Sprzedaż', 'Zaktualizowano dane sprzedaży i zysk');
                $message = "<div class='alert success'>✅ Dane sprzedaży zostały zaktualizowane!</div>";
            } elseif ($action === 'section2') {
                $lockType = $_POST['lockType'] ?? 'Brak';
                $lockCodeVal = 'Brak';
                if ($lockType === 'PIN/Hasło') {
                    $lockCodeVal = 'PIN: ' . trim($_POST['lockPinValue'] ?? '');
                } elseif ($lockType === 'Wzór') {
                    $lockCodeVal = 'Wzór: ' . trim($_POST['patternCode'] ?? '');
                }

                $sql = "UPDATE zlecenia SET deviceCategory = :deviceCategory, deviceBrandModel = :deviceBrandModel, serialNumber = :serialNumber, lockCode = :lockCode, admissionDate = :admissionDate, accessories = :accessories, visualCondition = :visualCondition, faultDescription = :faultDescription WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':id' => $id,
                    ':deviceCategory' => $_POST['deviceCategory'] ?? null,
                    ':deviceBrandModel' => $_POST['deviceBrandModel'] ?? null,
                    ':serialNumber' => $_POST['serialNumber'] ?? null,
                    ':lockCode' => $lockCodeVal,
                    ':admissionDate' => $_POST['admissionDate'] ?? null,
                    ':accessories' => $_POST['accessories'] ?? null,
                    ':visualCondition' => $_POST['visualCondition'] ?? null,
                    ':faultDescription' => $_POST['faultDescription'] ?? null
                ]);
                dodajWpisHistorii($pdo, $id, 'Sprzęt', 'Zaktualizowano informacje o sprzęcie i usterce');
                $message = "<div class='alert success'>✅ Informacje o sprzęcie i usterce zostały zaktualizowane!</div>";
            } elseif ($action === 'section3') {
                $stmt_old = $pdo->prepare("SELECT initialStatus FROM zlecenia WHERE id = :id");
                $stmt_old->execute([':id' => $id]);
                $oldData = $stmt_old->fetch(PDO::FETCH_ASSOC);
                $staryStatus = $oldData['initialStatus'] ?? 'Przyjęto';
                $nowyStatus = $_POST['initialStatus'] ?? 'Przyjęto';

                $sql = "UPDATE zlecenia SET repairNotes = :repairNotes, initialStatus = :initialStatus WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':id' => $id,
                    ':repairNotes' => $_POST['repairNotes'] ?? null,
                    ':initialStatus' => $nowyStatus
                ]);

                if ($staryStatus !== $nowyStatus) {
                    dodajWpisHistorii($pdo, $id, 'Status', "Zmieniono status z $staryStatus na $nowyStatus");
                } else {
                    dodajWpisHistorii($pdo, $id, 'Naprawa', "Zaktualizowano wykonane prace / uwagi");
                }
                $message = "<div class='alert success'>✅ Status i wykonane prace zostały zaktualizowane!</div>";
            } elseif ($action === 'add_part') {
                $partName = trim($_POST['part_name'] ?? '');
                $partPrice = !empty($_POST['part_price']) ? $_POST['part_price'] : 0;
                if (!empty($partName)) {
                    $stmt = $pdo->prepare("INSERT INTO czesci_naprawy (zlecenie_id, nazwa, cena) VALUES (:id, :nazwa, :cena)");
                    $stmt->execute([':id' => $id, ':nazwa' => $partName, ':cena' => $partPrice]);
                    dodajWpisHistorii($pdo, $id, 'Części', 'Dodano część: ' . $partName . ' (' . number_format((float)$partPrice, 2, ',', ' ') . ' zł)');
                }
                if ($isAjax) {
                    $stmt_p = $pdo->prepare("SELECT * FROM czesci_naprawy WHERE zlecenie_id = :id");
                    $stmt_p->execute([':id' => $id]);
                    $parts = $stmt_p->fetchAll(PDO::FETCH_ASSOC);
                
                    $totalPartsPrice = 0;
                    foreach ($parts as $p) { $totalPartsPrice += (float)$p['cena']; }

                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'parts' => $parts,
                        'totalPartsPrice' => number_format($totalPartsPrice, 2, ',', ' ')
                    ]);
                    exit;
                }
            } elseif ($action === 'delete_part') {
                $partId = $_POST['part_id'] ?? null;
                if ($partId) {
                    $stmt_pname = $pdo->prepare("SELECT nazwa FROM czesci_naprawy WHERE id = :pid AND zlecenie_id = :id");
                    $stmt_pname->execute([':pid' => $partId, ':id' => $id]);
                    $pData = $stmt_pname->fetch(PDO::FETCH_ASSOC);
                    $pNameUsunieta = $pData['nazwa'] ?? 'nieznana';

                    $stmt = $pdo->prepare("DELETE FROM czesci_naprawy WHERE id = :pid AND zlecenie_id = :id");
                    $stmt->execute([':pid' => $partId, ':id' => $id]);
                    dodajWpisHistorii($pdo, $id, 'Części', 'Usunięto część: ' . $pNameUsunieta);
                }
                if ($isAjax) {
                    $stmt_p = $pdo->prepare("SELECT * FROM czesci_naprawy WHERE zlecenie_id = :id");
                    $stmt_p->execute([':id' => $id]);
                    $parts = $stmt_p->fetchAll(PDO::FETCH_ASSOC);
                
                    $totalPartsPrice = 0;
                    foreach ($parts as $p) { $totalPartsPrice += (float)$p['cena']; }

                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'parts' => $parts,
                        'totalPartsPrice' => number_format($totalPartsPrice, 2, ',', ' ')
                    ]);
                    exit;
                }
            }
        } catch(PDOException $e) {
            error_log("Błąd obsługi zgłoszenia: " . $e->getMessage());
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Wystąpił błąd podczas przetwarzania żądania.']);
                exit;
            }
            $message = "<div class='alert error'>❌ Wystąpił błąd podczas przetwarzania żądania.</div>";
        }
    }
}

try {
    $stmt = $pdo->prepare("SELECT * FROM zlecenia WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $dane = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dane) {
        die("<div class='die-message'>Zgłoszenie nie istnieje w bazie.</div>");
    }

    $stmt_parts = $pdo->prepare("SELECT * FROM czesci_naprawy WHERE zlecenie_id = :id");
    $stmt_parts->execute([':id' => $id]);
    $parts = $stmt_parts->fetchAll(PDO::FETCH_ASSOC);

    $stmt_historia = $pdo->prepare("SELECT * FROM historia_zmian WHERE zlecenie_id = :id ORDER BY data_zmiany DESC");
    $stmt_historia->execute([':id' => $id]);
    $historiaZmian = $stmt_historia->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Błąd odczytu bazy danych: " . $e->getMessage());
}

$kat = $dane['deviceCategory'] ?? 'Inne';
$wszystkichTestow = count($kategorieTestow[$kat] ?? $kategorieTestow['Inne']);

$stmt_testy = $pdo->prepare("SELECT COUNT(CASE WHEN status != 'nie_sprawdzono' THEN 1 END) as sprawdzone, COUNT(CASE WHEN status = 'uszkodzone' THEN 1 END) as uszkodzone FROM zlecenie_testy WHERE zlecenie_id = :id");
$stmt_testy->execute([':id' => $id]);
$statyTestow = $stmt_testy->fetch(PDO::FETCH_ASSOC);

$sprawdzone = $statyTestow['sprawdzone'] ?? 0;
$uszkodzone = $statyTestow['uszkodzone'] ?? 0;

$badgeClass = "test-status-default";
$tekstStatusu = "Testy: $sprawdzone / $wszystkichTestow";

if ($sprawdzone > 0 && $sprawdzone < $wszystkichTestow) {
    $badgeClass = "test-status-partial";
} elseif ($sprawdzone == $wszystkichTestow) {
    if ($uszkodzone > 0) {
        $badgeClass = "test-status-error";
        $tekstStatusu = "Testy: $sprawdzone/$wszystkichTestow (Usterki: $uszkodzone)";
    } else {
        $badgeClass = "test-status-ok";
        $tekstStatusu = "Testy: OK ($sprawdzone/$wszystkichTestow)";
    }
}

require_once 'header.php';
?>

<div class="obsluga-header">
    <h1 class="obsluga-h1">
        Obsługa Zgłoszenia #<?php echo htmlspecialchars($dane['id']); ?>
        <span class="badge-type">
            <?php echo ($dane['orderType'] === 'client') ? '🛠️ Zlecenie Klienta' : '🛒 Własny Sprzęt'; ?>
        </span>
    </h1>
    <div class="obsluga-header-actions">
        <span class="obsluga-status-badge <?php echo $badgeClass; ?>">
            <?php echo $tekstStatusu; ?>
        </span>
        <a href="raport.php?id=<?php echo htmlspecialchars($dane['id']); ?>" target="_blank" class="btn btn-secondary btn-test-link">
            🖨️ Generuj raport
        </a>
        <a href="test.php?id=<?php echo htmlspecialchars($dane['id']); ?>" class="btn btn-primary btn-test-link">
            🧪 Testy urządzenia
        </a>
    </div>
</div>

<?php if(!empty($message)) echo $message; ?>

<div class="section-header-wrapper">
    <div class="section-title">👤 Dane Podstawowe / Zakup</div>
    <button type="button" class="btn btn-secondary btn-sm-edit" onclick="toggleEdit('section1')" id="edit-btn-1">Edytuj</button>
</div>

<div class="info-card">
    <div id="view-section1" class="view-mode">
        <div class="info-grid">
            <?php if ($dane['orderType'] === 'client'): ?>
                <div class="info-item"><label>Klient / Firma</label><span><?php echo htmlspecialchars($dane['clientName'] ?: 'Brak'); ?></span></div>
                <div class="info-item"><label>Telefon</label><span><?php echo htmlspecialchars($dane['clientPhone'] ?: 'Brak'); ?></span></div>
                <div class="info-item"><label>E-mail</label><span><?php echo htmlspecialchars($dane['clientEmail'] ?: 'Brak'); ?></span></div>
                <div class="info-item"><label>Cena</label><span><?php echo $dane['price'] ? number_format($dane['price'], 2, ',', ' ') . ' zł' : 'Brak'; ?></span></div>
            <?php else: ?>
                <div class="info-item"><label>Cena zakupu</label><span><?php echo $dane['purchasePrice'] ? number_format($dane['purchasePrice'], 2, ',', ' ') . ' zł' : 'Brak'; ?></span></div>
                <div class="info-item"><label>Koszt dodatkowy</label><span><?php echo $dane['additionalCost'] ? number_format($dane['additionalCost'], 2, ',', ' ') . ' zł' : 'Brak'; ?></span></div>
                <div class="info-item"><label>Źródło zakupu</label><span><?php echo htmlspecialchars($dane['purchaseSource'] ?: 'Brak'); ?></span></div>
            <?php endif; ?>
        </div>
    </div>

    <div id="edit-section1" class="edit-mode hidden">
        <form method="POST" action="">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($dane['id']); ?>">
            <input type="hidden" name="action" value="section1">
            <?= csrf_field(); ?>
            <div class="form-grid">
                <?php if ($dane['orderType'] === 'client'): ?>
                    <div class="form-group"><label>Klient / Firma</label><input type="text" name="clientName" value="<?php echo htmlspecialchars($dane['clientName'] ?? ''); ?>"></div>
                    <div class="form-group"><label>Telefon</label><input type="tel" name="clientPhone" value="<?php echo htmlspecialchars($dane['clientPhone'] ?? ''); ?>"></div>
                    <div class="form-group"><label>E-mail</label><input type="email" name="clientEmail" value="<?php echo htmlspecialchars($dane['clientEmail'] ?? ''); ?>"></div>
                    <div class="form-group"><label>Cena (zł)</label><input type="number" name="price" step="0.01" value="<?php echo htmlspecialchars($dane['price'] ?? ''); ?>"></div>
                <?php else: ?>
                    <div class="form-group"><label>Cena zakupu (zł)</label><input type="number" name="purchasePrice" step="0.01" value="<?php echo htmlspecialchars($dane['purchasePrice'] ?? ''); ?>"></div>
                    <div class="form-group"><label>Koszt dodatkowy (zł)</label><input type="number" name="additionalCost" step="0.01" value="<?php echo htmlspecialchars($dane['additionalCost'] ?? ''); ?>"></div>
                    <div class="form-group"><label>Źródło zakupu</label><input type="text" name="purchaseSource" value="<?php echo htmlspecialchars($dane['purchaseSource'] ?? ''); ?>"></div>
                <?php endif; ?>
            </div>
            <div class="actions">
                <button type="button" class="btn btn-secondary" onclick="toggleEdit('section1')">Anuluj</button>
                <button type="submit" class="btn btn-primary">Zapisz</button>
            </div>
        </form>
    </div>
</div>

<div class="section-header-wrapper">
    <div class="section-title">💻 Informacje o Sprzęcie i Usterce</div>
    <button type="button" class="btn btn-secondary btn-sm-edit" onclick="toggleEdit('section2')" id="edit-btn-2">Edytuj</button>
</div>

<div class="info-card">
    <div id="view-section2" class="view-mode">
        <div class="info-grid">
            <div class="info-item"><label>Kategoria</label><span><?php echo htmlspecialchars($dane['deviceCategory']); ?></span></div>
            <div class="info-item"><label>Marka i Model</label><span><?php echo htmlspecialchars($dane['deviceBrandModel']); ?></span></div>
            <div class="info-item"><label>Numer Seryjny / VIN / IMEI</label><span><?php echo htmlspecialchars($dane['serialNumber'] ?: 'Brak'); ?></span></div>
            <div class="info-item"><label>Blokada ekranu</label><span class="text-accent-bold"><?php echo htmlspecialchars($dane['lockCode'] ?: 'Brak'); ?></span></div>
            <div class="info-item"><label>Data przyjęcia</label><span><?php echo htmlspecialchars($dane['admissionDate']); ?></span></div>
            <div class="info-item info-item-full"><label>Opis usterki / Objawy</label><span><?php echo htmlspecialchars($dane['faultDescription'] ?: 'Brak'); ?></span></div>
            <div class="info-item info-item-full"><label>Dołączone akcesoria</label><span><?php echo htmlspecialchars($dane['accessories'] ?: 'Brak'); ?></span></div>
            <div class="info-item info-item-full"><label>Stan wizualny / Ślady napraw</label><span><?php echo htmlspecialchars($dane['visualCondition'] ?: 'Brak uwag'); ?></span></div>
        </div>
    </div>

    <div id="edit-section2" class="edit-mode hidden">
        <form method="POST" action="">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($dane['id']); ?>">
            <input type="hidden" name="action" value="section2">
            <?= csrf_field(); ?>
            <div class="form-grid">
                <div class="form-group">
                    <label>Kategoria *</label>
                    <select name="deviceCategory" required>
                        <option value="Laptop / Komputer" <?php echo ($dane['deviceCategory'] == 'Laptop / Komputer') ? 'selected' : ''; ?>>Laptop / Komputer</option>
                        <option value="Smartfon / Tablet" <?php echo ($dane['deviceCategory'] == 'Smartfon / Tablet') ? 'selected' : ''; ?>>Smartfon / Tablet</option>
                        <option value="Audio / Wzmacniacz" <?php echo ($dane['deviceCategory'] == 'Audio / Wzmacniacz') ? 'selected' : ''; ?>>Audio / Wzmacniacz</option>
                        <option value="Konsola" <?php echo ($dane['deviceCategory'] == 'Konsola') ? 'selected' : ''; ?>>Konsola</option>
                        <option value="Inne" <?php echo ($dane['deviceCategory'] == 'Inne') ? 'selected' : ''; ?>>Inne</option>
                    </select>
                </div>
                <div class="form-group"><label>Marka i Model *</label><input type="text" name="deviceBrandModel" value="<?php echo htmlspecialchars($dane['deviceBrandModel'] ?? ''); ?>" required></div>
                <div class="form-group"><label>Numer Seryjny / VIN / IMEI</label><input type="text" name="serialNumber" value="<?php echo htmlspecialchars($dane['serialNumber'] ?? ''); ?>"></div>
                <div class="form-group"><label>Data przyjęcia *</label><input type="date" name="admissionDate" value="<?php echo htmlspecialchars($dane['admissionDate'] ?? ''); ?>" required></div>

                <?php 
                    $currentLock = $dane['lockCode'] ?? 'Brak';
                    $isPin = str_starts_with($currentLock, 'PIN:');
                    $isPattern = str_starts_with($currentLock, 'Wzór:');
                    $isNone = (!$isPin && !$isPattern);
                    $pinVal = $isPin ? trim(substr($currentLock, 5)) : '';
                    $patternVal = $isPattern ? trim(substr($currentLock, 6)) : '';
                ?>
                <div class="form-group full-width">
                    <label>BLOKADA EKRANU</label>
                    <div class="lock-type-selector">
                        <label class="lock-option"><input type="radio" name="lockType" value="Brak" <?php echo $isNone ? 'checked' : ''; ?> onchange="toggleLockInputs('edit')"> Brak</label>
                        <label class="lock-option"><input type="radio" name="lockType" value="PIN/Hasło" <?php echo $isPin ? 'checked' : ''; ?> onchange="toggleLockInputs('edit')"> PIN/Hasło</label>
                        <label class="lock-option"><input type="radio" name="lockType" value="Wzór" <?php echo $isPattern ? 'checked' : ''; ?> onchange="toggleLockInputs('edit')"> Wzór</label>
                    </div>

                    <div id="editPinContainer" class="<?php echo $isPin ? '' : 'hidden'; ?>">
                        <input type="text" name="lockPinValue" value="<?php echo htmlspecialchars($pinVal); ?>" placeholder="Wpisz hasło lub PIN">
                    </div>

                    <div id="editPatternContainer" class="pattern-container <?php echo $isPattern ? '' : 'hidden'; ?>">
                        <div class="pattern-instruction">Narysuj wzór (kliknij/dotknij i przeciągnij)</div>
                        <div class="pattern-grid" id="editPatternGrid">
                            <?php for($i=1; $i<=9; $i++): ?>
                                <div class="pattern-dot" data-id="<?php echo $i; ?>"></div>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="patternCode" id="editPatternCodeInput" value="<?php echo htmlspecialchars($patternVal); ?>">
                        <button type="button" class="btn btn-secondary btn-clear-pattern" id="editClearPatternBtn">Wyczyść wzór</button>
                    </div>
                </div>

                <div class="form-group full-width"><label>Opis usterki / Objawy *</label><textarea name="faultDescription" required><?php echo htmlspecialchars($dane['faultDescription'] ?? ''); ?></textarea></div>
                <div class="form-group full-width"><label>Dołączone akcesoria</label><input type="text" name="accessories" value="<?php echo htmlspecialchars($dane['accessories'] ?? ''); ?>"></div>
                <div class="form-group full-width"><label>Stan wizualny / Ślady napraw</label><textarea name="visualCondition"><?php echo htmlspecialchars($dane['visualCondition'] ?? ''); ?></textarea></div>
            </div>
            <div class="actions">
                <button type="button" class="btn btn-secondary" onclick="toggleEdit('section2')">Anuluj</button>
                <button type="submit" class="btn btn-primary">Zapisz</button>
            </div>
        </form>
    </div>
</div>

<div class="section-title section-title-management">🔧 Zarządzanie Naprawą</div>
<div class="management-card">
    <form method="POST" action="" id="statusWorkForm">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($dane['id']); ?>">
        <input type="hidden" name="action" value="section3">
        <?= csrf_field(); ?>
        
        <div class="management-section">
            <label for="initialStatus" class="management-label">STATUS NAPRAWY:</label>
            <select id="initialStatus" name="initialStatus" class="status-select">
                <option value="Przyjęto" <?php echo ($dane['initialStatus'] == 'Przyjęto') ? 'selected' : ''; ?>>🔵 Przyjęto</option>
                <option value="Czeka na części" <?php echo ($dane['initialStatus'] == 'Czeka na części') ? 'selected' : ''; ?>>🟡 Czeka na części</option>
                <option value="W trakcie" <?php echo ($dane['initialStatus'] == 'W trakcie') ? 'selected' : ''; ?>>🟠 W trakcie</option>
                <option value="Gotowe" <?php echo ($dane['initialStatus'] == 'Gotowe') ? 'selected' : ''; ?>>🟢 Gotowe</option>
                <?php 
                    $finalStatus = ($dane['orderType'] === 'client') ? 'Odebrane' : 'Sprzedane'; 
                ?>
                <option value="<?php echo $finalStatus; ?>" <?php echo ($dane['initialStatus'] == 'Odebrane' || $dane['initialStatus'] == 'Sprzedane') ? 'selected' : ''; ?>>⚪ <?php echo $finalStatus; ?></option>
            </select>
        </div>

        <div class="management-section">
            <label class="management-label">WYMIENIONE CZĘŚCI:</label>
            <div id="partsContainer">
                <?php if (!empty($parts)): ?>
                    <table class="parts-table">
                        <thead>
                            <tr>
                                <th>Nazwa części</th>
                                <th>Cena</th>
                                <th class="th-action">Akcja</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                                $totalPartsPrice = 0;
                                foreach ($parts as $part): 
                                    $totalPartsPrice += $part['cena'];
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($part['nazwa']); ?></td>
                                    <td><b class="part-price-bold"><?php echo number_format($part['cena'], 2, ',', ' '); ?> zł</b></td>
                                    <td class="td-right">
                                        <button type="button" onclick="usunCzesci(<?php echo (int) $part['id']; ?>, <?php echo (int) $id; ?>)" class="btn-delete-part">Usuń</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="tr-total">
                                <td class="td-total-label">Suma części:</td>
                                <td colspan="2" class="td-total-value" id="totalPartsSum"><?php echo number_format($totalPartsPrice, 2, ',', ' '); ?> zł</td>
                            </tr>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="empty-parts" id="emptyPartsMsg">Brak dodanych części do tej naprawy.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="add-part-panel">
            <div class="add-part-name">
                <label class="add-part-label">Nazwa części</label>
                <input type="text" id="ajaxPartName" placeholder="np. Wyświetlacz OLED" class="add-part-input">
            </div>
            <div class="add-part-price">
                <label class="add-part-label">Cena (zł)</label>
                <input type="number" step="0.01" id="ajaxPartPrice" placeholder="0.00" class="add-part-input">
            </div>
            <div>
                <button type="button" onclick="dodajCzesc(<?php echo (int) $id; ?>)" class="btn btn-secondary btn-add-part">+ Dodaj</button>
            </div>
        </div>

        <div class="management-section-large">
            <label for="repairNotes" class="management-label">WYKONANE PRACE / DIAGNOZA / UWAGI:</label>
            <textarea id="repairNotes" name="repairNotes" placeholder="Wpisz diagnozę, zużyte materiały lub wykonane czynności..." class="repair-notes-textarea"><?php echo htmlspecialchars($dane['repairNotes'] ?? ''); ?></textarea>
        </div>

        <div>
            <button type="submit" class="btn btn-primary btn-save-management">
                💾 Zapisz status i prace
            </button>
        </div>
    </form>
</div>

<?php if ($dane['orderType'] === 'own' && ($dane['initialStatus'] === 'Gotowe' || $dane['initialStatus'] === 'Sprzedane')): ?>
    <div class="section-header-wrapper">
        <div class="section-title">💰 Dane Sprzedaży i Zysk</div>
        <button type="button" class="btn btn-secondary btn-sm-edit" onclick="toggleEdit('sale')" id="edit-btn-sale">Edytuj</button>
    </div>

    <div class="info-card info-card-secondary">
        <?php 
            $cenaZakupu = (float)($dane['purchasePrice'] ?? 0);
            $kosztDodatkowy = (float)($dane['additionalCost'] ?? 0);
            $sumaCzesci = 0;
            foreach ($parts as $p) { $sumaCzesci += (float)$p['cena']; }
            $kosztCalkowity = $cenaZakupu + $kosztDodatkowy + $sumaCzesci;
            $cenaSprzedazy = (float)($dane['salePrice'] ?? 0);
            $czystyZysk = $cenaSprzedazy > 0 ? ($cenaSprzedazy - $kosztCalkowity) : null;
            $zyskClass = ($czystyZysk !== null && $czystyZysk >= 0) ? 'text-success-bold' : 'text-error-bold';
        ?>
        <div id="view-sale" class="view-mode">
            <div class="info-grid">
                <div class="info-item"><label>Całkowity koszt (zakup + części)</label><span><?php echo number_format($kosztCalkowity, 2, ',', ' '); ?> zł</span></div>
                <div class="info-item"><label>Cena sprzedaży</label><span class="text-accent-bold"><?php echo $dane['salePrice'] ? number_format($dane['salePrice'], 2, ',', ' ') . ' zł' : 'Nie sprzedano'; ?></span></div>
                <div class="info-item"><label>Data sprzedaży</label><span><?php echo $dane['saleDate'] ? htmlspecialchars($dane['saleDate']) : 'Brak'; ?></span></div>
                <div class="info-item">
                    <label>Czysty zysk</label>
                    <span class="<?php echo $zyskClass; ?>">
                        <?php echo $czystyZysk !== null ? number_format($czystyZysk, 2, ',', ' ') . ' zł' : '-'; ?>
                    </span>
                </div>
            </div>
        </div>

        <div id="edit-sale" class="edit-mode hidden">
            <form method="POST" action="">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($dane['id']); ?>">
                <input type="hidden" name="action" value="section_sale">
                <?= csrf_field(); ?>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Cena sprzedaży (zł)</label>
                        <input type="number" name="salePrice" step="0.01" value="<?php echo htmlspecialchars($dane['salePrice'] ?? ''); ?>" placeholder="np. 1200.00">
                    </div>
                    <div class="form-group">
                        <label>Data sprzedaży</label>
                        <input type="date" name="saleDate" value="<?php echo htmlspecialchars($dane['saleDate'] ?: date('Y-m-d')); ?>">
                    </div>
                </div>
                <div class="actions">
                    <button type="button" class="btn btn-secondary" onclick="toggleEdit('sale')">Anuluj</button>
                    <button type="submit" class="btn btn-primary">Zapisz sprzedaż</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<div class="section-title">📜 HISTORIA ZMIAN ZLECENIA</div>
<div class="info-card">
    <?php if (empty($historiaZmian)): ?>
        <p class="empty-parts">Brak zarejestrowanych zmian dla tego zgłoszenia.</p>
    <?php else: ?>
        <ul class="history-list">
            <?php foreach ($historiaZmian as $wpis): ?>
                <li>
                    <span class="history-date"><?php echo date('Y-m-d H:i:s', strtotime($wpis['data_zmiany'])); ?></span> — 
                    <span class="history-desc"><?php echo htmlspecialchars($wpis['opis']); ?></span> 
                    <span class="history-user">(Użytkownik: <?php echo htmlspecialchars($wpis['uzytkownik']); ?>)</span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>
