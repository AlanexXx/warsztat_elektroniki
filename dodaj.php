<?php
require_once 'db.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!csrf_verify()) {
        $message = "<div class='alert error'>❌ Nieprawidłowe żądanie. Odśwież stronę i spróbuj ponownie.</div>";
    } else {
        try {
            $lockType = $_POST['lockType'] ?? 'Brak';
            $lockCodeVal = 'Brak';
            if ($lockType === 'PIN/Hasło') {
                $lockCodeVal = 'PIN: ' . trim($_POST['lockPinValue'] ?? '');
            } elseif ($lockType === 'Wzór') {
                $lockCodeVal = 'Wzór: ' . trim($_POST['patternCode'] ?? '');
            }

            $sql = "INSERT INTO zlecenia (
                orderType, clientName, clientPhone, clientEmail, price, 
                purchasePrice, additionalCost, purchaseSource, deviceCategory, 
                deviceBrandModel, serialNumber, lockCode, accessories, visualCondition, 
                faultDescription, initialStatus, admissionDate
            ) VALUES (
                :orderType, :clientName, :clientPhone, :clientEmail, :price, 
                :purchasePrice, :additionalCost, :purchaseSource, :deviceCategory, 
                :deviceBrandModel, :serialNumber, :lockCode, :accessories, :visualCondition, 
                :faultDescription, :initialStatus, :admissionDate
            )";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':orderType' => $_POST['orderType'] ?? null,
                ':clientName' => $_POST['clientName'] ?? null,
                ':clientPhone' => $_POST['clientPhone'] ?? null,
                ':clientEmail' => $_POST['clientEmail'] ?? null,
                ':price' => !empty($_POST['price']) ? $_POST['price'] : null,
                ':purchasePrice' => !empty($_POST['purchasePrice']) ? $_POST['purchasePrice'] : null,
                ':additionalCost' => !empty($_POST['additionalCost']) ? $_POST['additionalCost'] : null,
                ':purchaseSource' => $_POST['purchaseSource'] ?? null,
                ':deviceCategory' => $_POST['deviceCategory'] ?? null,
                ':deviceBrandModel' => $_POST['deviceBrandModel'] ?? null,
                ':serialNumber' => $_POST['serialNumber'] ?? null,
                ':lockCode' => $lockCodeVal,
                ':accessories' => $_POST['accessories'] ?? null,
                ':visualCondition' => $_POST['visualCondition'] ?? null,
                ':faultDescription' => $_POST['faultDescription'] ?? null,
                ':initialStatus' => 'Przyjęto',
                ':admissionDate' => $_POST['admissionDate'] ?? null
            ]);

            $noweId = $pdo->lastInsertId();
            $uzytkownik = $_SESSION['user_login'] ?? 'System';
            $stmtH = $pdo->prepare("INSERT INTO historia_zmian (zlecenie_id, uzytkownik, typ_zmiany, opis) VALUES (:id, :usr, 'Utworzenie', 'Utworzono zlecenie ze statusem Przyjęto')");
            $stmtH->execute([':id' => $noweId, ':usr' => $uzytkownik]);

            $message = "<div class='alert success'>✅ Zgłoszenie zostało poprawnie zapisane w bazie danych!</div>";
        } catch(PDOException $e) {
            $message = "<div class='alert error'>❌ Wystąpił błąd podczas zapisywania danych.</div>";
            error_log("Błąd dodawania: " . $e->getMessage());
        }
    }
}

require_once 'header.php';
?>

<h1>Dodaj Nowe Zgłoszenie / Sprzęt</h1>

<?php if(!empty($message)) echo $message; ?>

<form method="POST" action="">
    <?= csrf_field(); ?>
    <div class="type-selector">
        <div class="type-option">
            <input type="radio" id="typeClient" name="orderType" value="client" checked>
            <label for="typeClient">🛠️ Zlecenie Klienta</label>
        </div>
        <div class="type-option">
            <input type="radio" id="typeOwn" name="orderType" value="own">
            <label for="typeOwn">🛒 Własny Sprzęt</label>
        </div>
    </div>

    <div id="clientSection" class="dynamic-section">
        <div class="section-title">👤 Dane Klienta</div>
        <div class="form-grid">
            <div class="form-group"><label>Imię i Nazwisko / Firma</label><input type="text" name="clientName" placeholder="Jan Kowalski"></div>
            <div class="form-group"><label>Numer Telefonu</label><input type="tel" name="clientPhone" placeholder="123 456 789"></div>
            <div class="form-group"><label>Adres E-mail</label><input type="email" name="clientEmail" placeholder="klient@example.com"></div>
            <div class="form-group"><label>Cena (zł)</label><input type="number" name="price" step="0.01" placeholder="np. 300"></div>
        </div>
    </div>

    <div id="ownSection" class="dynamic-section hidden">
        <div class="section-title">💰 Szczegóły Zakupu</div>
        <div class="form-grid">
            <div class="form-group"><label>Cena zakupu (zł)</label><input type="number" name="purchasePrice" step="0.01" placeholder="150.00"></div>
            <div class="form-group"><label>Koszt dodatkowy (zł)</label><input type="number" name="additionalCost" step="0.01" placeholder="np. 15.99"></div>
            <div class="form-group"><label>Gdzie kupiono</label><input type="text" name="purchaseSource" placeholder="np. OLX, Allegro, Giełda"></div>
        </div>
    </div>

    <div class="section-title">💻 Informacje o Sprzęcie i Usterce</div>
    <div class="form-grid">
        <div class="form-group">
            <label>Kategoria *</label>
            <select name="deviceCategory" required>
                <option value="">-- Wybierz kategorię --</option>
                <option value="Laptop / Komputer">Laptop / Komputer</option>
                <option value="Smartfon / Tablet">Smartfon / Tablet</option>
                <option value="Audio / Wzmacniacz">Audio / Wzmacniacz</option>
                <option value="Konsola">Konsola</option>
                <option value="Inne">Inne</option>
            </select>
        </div>
        <div class="form-group"><label>Marka i Model *</label><input type="text" name="deviceBrandModel" placeholder="np. Lenovo ThinkPad T480" required></div>
        <div class="form-group"><label>Numer Seryjny / VIN / IMEI</label><input type="text" name="serialNumber" placeholder="SN / ID modułu"></div>
        <div class="form-group"><label>Data przyjęcia *</label><input type="date" name="admissionDate" value="<?= date('Y-m-d'); ?>" required></div>

        <div class="form-group full-width">
            <label>BLOKADA EKRANU</label>
            <div class="lock-type-selector">
                <label class="lock-option"><input type="radio" name="lockType" value="Brak" checked onchange="toggleLockInputs()"> Brak</label>
                <label class="lock-option"><input type="radio" name="lockType" value="PIN/Hasło" onchange="toggleLockInputs()"> PIN/Hasło</label>
                <label class="lock-option"><input type="radio" name="lockType" value="Wzór" onchange="toggleLockInputs()"> Wzór</label>
            </div>

            <div id="pinContainer" class="hidden"><input type="text" name="lockPinValue" placeholder="Wpisz hasło lub PIN"></div>

            <div id="patternContainer" class="pattern-container hidden">
                <div class="pattern-instruction">Narysuj wzór (kliknij i przeciągnij)</div>
                <div class="pattern-grid" id="patternGrid">
                    <?php for($i=1; $i<=9; $i++): ?><div class="pattern-dot" data-id="<?= $i; ?>"></div><?php endfor; ?>
                </div>
                <input type="hidden" name="patternCode" id="patternCodeInput">
                <button type="button" class="btn btn-secondary btn-clear-pattern" id="clearPatternBtn">Wyczyść wzór</button>
            </div>
        </div>

        <div class="form-group full-width"><label>Dołączone akcesoria</label><input type="text" name="accessories" placeholder="np. Brak zasilacza, sama płyta, kabel HDMI, pilot"></div>
        <div class="form-group full-width"><label>Opis usterki / Objawy *</label><textarea name="faultDescription" placeholder="np. Nie włącza się, pobór prądu 0.01A..." required></textarea></div>
        <div class="form-group full-width"><label>Stan wizualny / Ślady napraw</label><textarea name="visualCondition" placeholder="np. Rysa na matrycy, brak 2 śrubek..."></textarea></div>
    </div>

    <div class="actions">
        <button type="submit" class="btn btn-primary">Zapisz Zgłoszenie</button>
    </div>
</form>

<?php require_once 'footer.php'; ?>
