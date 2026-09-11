<?php
require_once 'db.php';

try {
    // --- 1. OGÓŁEM (Cała historia) ---
    $stmt_op = $pdo->query("SELECT initialStatus, COUNT(*) as ilosc FROM zlecenia GROUP BY initialStatus");
    $statyStatusy = $stmt_op->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $aktywnePrzyjeto = $statyStatusy['Przyjęto'] ?? 0;
    $aktywneCzeka = $statyStatusy['Czeka na części'] ?? 0;
    $aktywneWTrakcie = $statyStatusy['W trakcie'] ?? 0;
    $sumaAktywnych = $aktywnePrzyjeto + $aktywneCzeka + $aktywneWTrakcie;

    $ukonczoneOgolnie = $pdo->query("SELECT COUNT(*) FROM zlecenia WHERE initialStatus IN ('Gotowe', 'Odebrane', 'Sprzedane')")->fetchColumn();
    $sredniCzasOgolnieRaw = $pdo->query("SELECT AVG(DATEDIFF(COALESCE(saleDate, admissionDate), admissionDate)) FROM zlecenia WHERE initialStatus IN ('Gotowe', 'Odebrane', 'Sprzedane')")->fetchColumn();
    $sredniCzasOgolnie = round((float) ($sredniCzasOgolnieRaw ?? 0), 1);

    $przychodKlientowOgolnie = $pdo->query("SELECT SUM(price) FROM zlecenia WHERE orderType = 'client' AND price > 0")->fetchColumn() ?? 0;

    $stmt_wlasne_ogolnie = $pdo->query("
        SELECT z.id, z.salePrice, z.purchasePrice, z.additionalCost, 
        COALESCE((SELECT SUM(c.cena) FROM czesci_naprawy c WHERE c.zlecenie_id = z.id), 0) as suma_czesci
        FROM zlecenia z WHERE z.orderType = 'own' AND z.salePrice IS NOT NULL
    ")->fetchAll(PDO::FETCH_ASSOC);
    $zyskWlasnyOgolnie = 0;
    $sumaSprzedazyOgolnie = 0;
    foreach ($stmt_wlasne_ogolnie as $item) {
        $koszt = (float)$item['purchasePrice'] + (float)$item['additionalCost'] + (float)$item['suma_czesci'];
        $zyskWlasnyOgolnie += ((float)$item['salePrice'] - $koszt);
        $sumaSprzedazyOgolnie += (float)$item['salePrice'];
    }

    // --- 2. BIEŻĄCY MIESIĄC ---
    $przyjeteMiesiac = $pdo->query("SELECT COUNT(*) FROM zlecenia WHERE DATE_FORMAT(admissionDate, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')")->fetchColumn();
    $ukonczoneMiesiac = $pdo->query("SELECT COUNT(*) FROM zlecenia WHERE initialStatus IN ('Gotowe', 'Odebrane', 'Sprzedane') AND DATE_FORMAT(admissionDate, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')")->fetchColumn();
    $przychodKlientowMiesiac = $pdo->query("SELECT SUM(price) FROM zlecenia WHERE orderType = 'client' AND price > 0 AND DATE_FORMAT(admissionDate, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')")->fetchColumn() ?? 0;

    $stmt_wlasne_miesiac = $pdo->query("
        SELECT z.id, z.salePrice, z.purchasePrice, z.additionalCost, 
        COALESCE((SELECT SUM(c.cena) FROM czesci_naprawy c WHERE c.zlecenie_id = z.id), 0) as suma_czesci
        FROM zlecenia z WHERE z.orderType = 'own' AND z.salePrice IS NOT NULL AND DATE_FORMAT(z.admissionDate, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
    ")->fetchAll(PDO::FETCH_ASSOC);
    $zyskWlasnyMiesiac = 0;
    $sumaSprzedazyMiesiac = 0;
    foreach ($stmt_wlasne_miesiac as $item) {
        $koszt = (float)$item['purchasePrice'] + (float)$item['additionalCost'] + (float)$item['suma_czesci'];
        $zyskWlasnyMiesiac += ((float)$item['salePrice'] - $koszt);
        $sumaSprzedazyMiesiac += (float)$item['salePrice'];
    }

    // --- 3. BIEŻĄCY TYDZIEŃ ---
    $przyjeteTydzien = $pdo->query("SELECT COUNT(*) FROM zlecenia WHERE YEARWEEK(admissionDate, 1) = YEARWEEK(NOW(), 1)")->fetchColumn();
    $ukonczoneTydzien = $pdo->query("SELECT COUNT(*) FROM zlecenia WHERE initialStatus IN ('Gotowe', 'Odebrane', 'Sprzedane') AND YEARWEEK(admissionDate, 1) = YEARWEEK(NOW(), 1)")->fetchColumn();
    $przychodKlientowTydzien = $pdo->query("SELECT SUM(price) FROM zlecenia WHERE orderType = 'client' AND price > 0 AND YEARWEEK(admissionDate, 1) = YEARWEEK(NOW(), 1)")->fetchColumn() ?? 0;

    $stmt_wlasne_tydzien = $pdo->query("
        SELECT z.id, z.salePrice, z.purchasePrice, z.additionalCost, 
        COALESCE((SELECT SUM(c.cena) FROM czesci_naprawy c WHERE c.zlecenie_id = z.id), 0) as suma_czesci
        FROM zlecenia z WHERE z.orderType = 'own' AND z.salePrice IS NOT NULL AND YEARWEEK(z.admissionDate, 1) = YEARWEEK(NOW(), 1)
    ")->fetchAll(PDO::FETCH_ASSOC);
    $zyskWlasnyTydzien = 0;
    $sumaSprzedazyTydzien = 0;
    foreach ($stmt_wlasne_tydzien as $item) {
        $koszt = (float)$item['purchasePrice'] + (float)$item['additionalCost'] + (float)$item['suma_czesci'];
        $zyskWlasnyTydzien += ((float)$item['salePrice'] - $koszt);
        $sumaSprzedazyTydzien += (float)$item['salePrice'];
    }

} catch (PDOException $e) {
    error_log("Błąd statystyk: " . $e->getMessage());
}

$containerClass = 'container-large';
require_once 'header.php';
?>

<h1>📊 Panel Główny / Statystyki Serwisu</h1>

<h3 class="stats-section-title">📅 Statystyki Ogólne (Cała Historia)</h3>
<div class="stats-dashboard">
    <div class="stat-card">
        <div class="stat-title">🛠️ Aktywne Zlecenia</div>
        <div class="stat-value"><?php echo $sumaAktywnych; ?></div>
        <div class="stat-details">
            <span>Przyjęto: <b><?php echo $aktywnePrzyjeto; ?></b></span> • 
            <span>Czeka: <b><?php echo $aktywneCzeka; ?></b></span> • 
            <span>W toku: <b><?php echo $aktywneWTrakcie; ?></b></span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-title">✅ Ukończone Łącznie</div>
        <div class="stat-value"><?php echo $ukonczoneOgolnie; ?></div>
        <div class="stat-details">Średni czas: <b><?php echo $sredniCzasOgolnie; ?> dni</b></div>
    </div>
    <div class="stat-card">
        <div class="stat-title">👤 Przychód (Klienci)</div>
        <div class="stat-value text-accent-bold"><?php echo number_format($przychodKlientowOgolnie, 2, ',', ' '); ?> zł</div>
        <div class="stat-details">Naprawy klientów</div>
    </div>
    <div class="stat-card">
        <div class="stat-title">💰 Suma Sprzedaży</div>
        <div class="stat-value text-accent-bold"><?php echo number_format($sumaSprzedazyOgolnie, 2, ',', ' '); ?> zł</div>
        <div class="stat-details">Wartość sprzedanych towarów</div>
    </div>
    <div class="stat-card">
        <div class="stat-title">🛒 Zysk (Własny Sprzęt)</div>
        <div class="stat-value <?php echo ($zyskWlasnyOgolnie >= 0) ? 'text-success-bold' : 'text-error-bold'; ?>">
            <?php echo number_format($zyskWlasnyOgolnie, 2, ',', ' '); ?> zł
        </div>
        <div class="stat-details">Czysty zysk z towarów</div>
    </div>
</div>

<h3 class="stats-section-title">📆 Statystyki za Bieżący Miesiąc</h3>
<div class="stats-dashboard">
    <div class="stat-card">
        <div class="stat-title">📥 Przyjęte (Miesiąc)</div>
        <div class="stat-value"><?php echo $przyjeteMiesiac; ?></div>
        <div class="stat-details">Nowe zgłoszenia w tym miesiącu</div>
    </div>
    <div class="stat-card">
        <div class="stat-title">✅ Ukończone (Miesiąc)</div>
        <div class="stat-value"><?php echo $ukonczoneMiesiac; ?></div>
        <div class="stat-details">Zamknięte naprawy w tym miesiącu</div>
    </div>
    <div class="stat-card">
        <div class="stat-title">👤 Przychód (Klienci)</div>
        <div class="stat-value text-accent-bold"><?php echo number_format($przychodKlientowMiesiac, 2, ',', ' '); ?> zł</div>
        <div class="stat-details">Naprawy klientów</div>
    </div>
    <div class="stat-card">
        <div class="stat-title">💰 Suma Sprzedaży</div>
        <div class="stat-value text-accent-bold"><?php echo number_format($sumaSprzedazyMiesiac, 2, ',', ' '); ?> zł</div>
        <div class="stat-details">Wartość sprzedanych towarów</div>
    </div>
    <div class="stat-card">
        <div class="stat-title">🛒 Zysk (Własny Sprzęt)</div>
        <div class="stat-value <?php echo ($zyskWlasnyMiesiac >= 0) ? 'text-success-bold' : 'text-error-bold'; ?>">
            <?php echo number_format($zyskWlasnyMiesiac, 2, ',', ' '); ?> zł
        </div>
        <div class="stat-details">Czysty zysk z towarów</div>
    </div>
</div>

<h3 class="stats-section-title">⏱️ Statystyki za Bieżący Tydzień</h3>
<div class="stats-dashboard">
    <div class="stat-card">
        <div class="stat-title">📥 Przyjęte (Tydzień)</div>
        <div class="stat-value"><?php echo $przyjeteTydzien; ?></div>
        <div class="stat-details">Nowe zgłoszenia w tym tygodniu</div>
    </div>
    <div class="stat-card">
        <div class="stat-title">✅ Ukończone (Tydzień)</div>
        <div class="stat-value"><?php echo $ukonczoneTydzien; ?></div>
        <div class="stat-details">Zamknięte naprawy w tym tygodniu</div>
    </div>
    <div class="stat-card">
        <div class="stat-title">👤 Przychód (Klienci)</div>
        <div class="stat-value text-accent-bold"><?php echo number_format($przychodKlientowTydzien, 2, ',', ' '); ?> zł</div>
        <div class="stat-details">Naprawy klientów</div>
    </div>
    <div class="stat-card">
        <div class="stat-title">💰 Suma Sprzedaży</div>
        <div class="stat-value text-accent-bold"><?php echo number_format($sumaSprzedazyTydzien, 2, ',', ' '); ?> zł</div>
        <div class="stat-details">Wartość sprzedanych towarów</div>
    </div>
    <div class="stat-card">
        <div class="stat-title">🛒 Zysk (Własny Sprzęt)</div>
        <div class="stat-value <?php echo ($zyskWlasnyTydzien >= 0) ? 'text-success-bold' : 'text-error-bold'; ?>">
            <?php echo number_format($zyskWlasnyTydzien, 2, ',', ' '); ?> zł
        </div>
        <div class="stat-details">Czysty zysk z towarów</div>
    </div>
</div>

<div class="info-card" style="text-align: center; padding: 40px; margin-top: 30px;">
    <h3 style="margin-top: 0; color: var(--text-color);">Szybka nawigacja</h3>
    <div style="display: flex; gap: 15px; justify-content: center; margin-top: 20px;">
        <a href="lista.php" class="btn btn-primary">📋 Przejdź do listy zgłoszeń</a>
        <a href="dodaj.php" class="btn btn-secondary">➕ Dodaj nowe zgłoszenie</a>
    </div>
</div>

<?php require_once 'footer.php'; ?>
