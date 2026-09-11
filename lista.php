<?php
require_once 'db.php';

$search = trim($_GET['search'] ?? '');
$selectedTypes = (array)($_GET['type'] ?? []);
$selectedStatuses = (array)($_GET['status'] ?? []);

try {
    $sql = "SELECT z.*, 
            COALESCE(SUM(c.cena), 0) AS suma_czesci,
            (SELECT COUNT(*) FROM zlecenie_testy t WHERE t.zlecenie_id = z.id AND t.status != 'nie_sprawdzono') AS testy_sprawdzone,
            (SELECT COUNT(*) FROM zlecenie_testy t WHERE t.zlecenie_id = z.id AND t.status = 'uszkodzone') AS testy_uszkodzone
            FROM zlecenia z 
            LEFT JOIN czesci_naprawy c ON z.id = c.zlecenie_id 
            WHERE 1=1";
    $params = array();

    if (!empty($search)) {
        $sql .= " AND (z.clientName LIKE :search OR z.clientPhone LIKE :search OR z.deviceBrandModel LIKE :search OR z.serialNumber LIKE :search OR z.faultDescription LIKE :search OR z.repairNotes LIKE :search)";
        $params[':search'] = "%$search%";
    }

    if (!empty($selectedTypes)) {
        $typePlaceholders = array();
        foreach ($selectedTypes as $i => $t) {
            $placeholder = ":type_$i";
            $typePlaceholders[] = $placeholder;
            $params[$placeholder] = $t;
        }
        $sql .= " AND z.orderType IN (" . implode(',', $typePlaceholders) . ")";
    }

    if (!empty($selectedStatuses)) {
        $statusPlaceholders = array();
        foreach ($selectedStatuses as $i => $s) {
            $placeholder = ":status_$i";
            $statusPlaceholders[] = $placeholder;
            $params[$placeholder] = $s;
        }
        $sql .= " AND z.initialStatus IN (" . implode(',', $statusPlaceholders) . ")";
    }

    $sql .= " GROUP BY z.id ORDER BY z.admissionDate DESC, z.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $zlecenia = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Błąd listy: " . $e->getMessage());
    die("Wystąpił błąd podczas pobierania danych.");
}

$mapa_ilosci = array(
    'Laptop / Komputer' => 13,
    'Smartfon / Tablet' => 14,
    'Audio / Wzmacniacz' => 5,
    'Konsola' => 10,
    'Inne' => 3
);

$containerClass = 'container-large';
require_once 'header.php';
?>

<div class="header-bar">
    <h1>📋 Lista Zgłoszeń i Sprzętu</h1>
</div>

<div class="filter-card">
    <form method="GET" action="lista.php" class="filter-form">
        <div class="filter-group filter-group-search">
            <label>Szukaj</label>
            <input type="text" name="search" placeholder="Wpisz frazę..." value="<?php echo htmlspecialchars($search); ?>">
        </div>

        <div class="filter-group filter-group-type">
            <label>Typ zgłoszenia</label>
            <div class="custom-dropdown">
                <button type="button" class="dropdown-btn" onclick="toggleDropdown('typeDropdownMenu')">
                    <span><?php echo empty($selectedTypes) ? 'Wszystkie typy' : 'Wybrano (' . count($selectedTypes) . ')'; ?></span><span>▼</span>
                </button>
                <div id="typeDropdownMenu" class="dropdown-menu hidden">
                    <label class="filter-checkbox-label"><input type="checkbox" name="type[]" value="client" <?php echo in_array('client', $selectedTypes) ? 'checked' : ''; ?>> Zlecenie Klienta</label>
                    <label class="filter-checkbox-label"><input type="checkbox" name="type[]" value="own" <?php echo in_array('own', $selectedTypes) ? 'checked' : ''; ?>> Własny Sprzęt</label>
                </div>
            </div>
        </div>

        <div class="filter-group filter-group-status">
            <label>Status naprawy</label>
            <div class="custom-dropdown">
                <button type="button" class="dropdown-btn" onclick="toggleDropdown('statusDropdownMenu')">
                    <span><?php echo empty($selectedStatuses) ? 'Wszystkie statusy' : 'Wybrano (' . count($selectedStatuses) . ')'; ?></span><span>▼</span>
                </button>
                <div id="statusDropdownMenu" class="dropdown-menu hidden">
                    <?php 
                    $wszystkieStatusy = array('Przyjęto', 'Czeka na części', 'W trakcie', 'Gotowe', 'Odebrane', 'Sprzedane');
                    foreach ($wszystkieStatusy as $stOption) { 
                    ?>
                        <label class="filter-checkbox-label"><input type="checkbox" name="status[]" value="<?php echo $stOption; ?>" <?php echo in_array($stOption, $selectedStatuses) ? 'checked' : ''; ?>> <?php echo $stOption; ?></label>
                    <?php } ?>
                </div>
            </div>
        </div>

        <div class="filter-actions filter-actions-flex">
            <button type="submit" class="btn btn-primary">Szukaj</button>
            <?php if (!empty($search) || !empty($selectedTypes) || !empty($selectedStatuses)) { ?>
                <a href="lista.php" class="clear-filter-link"><button type="button" class="btn btn-secondary">Wyczyść</button></a>
            <?php } ?>
        </div>
    </form>
</div>

<?php if (empty($zlecenia)) { ?>
    <p class="empty-list-msg">Nie znaleziono zgłoszeń.</p>
<?php } else { ?>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th># / Typ</th>
                    <th>Data zakł.</th>
                    <th>Sprzęt</th>
                    <th>Klient / Szczegóły</th>
                    <th class="th-fault-desc">Usterka / Prace</th>
                    <th>Status</th>
                    <th>Cena</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($zlecenia as $item) { 
                    $kat = isset($item['deviceCategory']) ? $item['deviceCategory'] : 'Inne';
                    $wszystkichTestow = isset($mapa_ilosci[$kat]) ? $mapa_ilosci[$kat] : 3;
                    $sprawdzone = $item['testy_sprawdzone'];
                    $uszkodzone = $item['testy_uszkodzone'];

                    $testClass = "test-status-default";
                    $testTekst = "Testy: $sprawdzone/$wszystkichTestow";

                    if ($sprawdzone > 0 && $sprawdzone < $wszystkichTestow) {
                        $testClass = "test-status-partial";
                    } elseif ($sprawdzone == $wszystkichTestow) {
                        if ($uszkodzone > 0) {
                            $testClass = "test-status-error";
                            $testTekst = "Testy: Usterki ($uszkodzone)";
                        } else {
                            $testClass = "test-status-ok";
                            $testTekst = "Testy: OK";
                        }
                    }
                ?>
                    <tr>
                        <td>
                            <b class="id-badge-text">#<?php echo htmlspecialchars($item['id']); ?></b><br>
                            <span class="badge <?php echo ($item['orderType'] === 'client') ? 'badge-client' : 'badge-own'; ?>">
                                <?php echo ($item['orderType'] === 'client') ? 'Klient' : 'Własny'; ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($item['admissionDate']); ?></td>
                        <td>
                            <b><?php echo htmlspecialchars($item['deviceBrandModel']); ?></b>
                            <div class="text-muted-sm"><?php echo htmlspecialchars($item['deviceCategory']); ?></div>
                        </td>
                        <td>
                            <?php if ($item['orderType'] === 'client') { ?>
                                <b><?php echo htmlspecialchars($item['clientName'] ? $item['clientName'] : 'Brak danych'); ?></b>
                                <?php if (!empty($item['clientPhone'])) { ?>
                                    <div class="text-muted-sm">📞 <?php echo htmlspecialchars($item['clientPhone']); ?></div>
                                <?php } ?>
                            <?php } else { ?>
                                <b>Zakup:</b> <?php echo htmlspecialchars($item['purchaseSource'] ? $item['purchaseSource'] : 'Brak źródła'); ?>
                                <?php 
                                    $totalCost = (float)(isset($item['purchasePrice']) ? $item['purchasePrice'] : 0) + (float)(isset($item['additionalCost']) ? $item['additionalCost'] : 0) + (float)(isset($item['suma_czesci']) ? $item['suma_czesci'] : 0);
                                    if ($totalCost > 0) {
                                ?>
                                    <div class="text-muted-sm">Koszt całk: <?php echo number_format($totalCost, 2, ',', ' '); ?> zł</div>
                                <?php } ?>
                            <?php } ?>
                        </td>
                        <td>
                            <div class="fault-desc-wrapper">
                                <div class="fault-desc-item"><b class="fault-label">Usterka:</b> <?php echo nl2br(htmlspecialchars($item['faultDescription'])); ?></div>
                                <?php if (!empty($item['repairNotes'])) { ?>
                                    <div class="repair-notes-wrapper"><span class="repair-notes-label">🛠️ Prace:</span> <?php echo nl2br(htmlspecialchars($item['repairNotes'])); ?></div>
                                <?php } ?>
                            </div>
                        </td>
                        <td>
                            <?php 
                                $st = isset($item['initialStatus']) && $item['initialStatus'] ? $item['initialStatus'] : 'Przyjęto';
                                $statusClass = 'badge-status-przyjeto';
                                if ($st === 'Czeka na części') $statusClass = 'badge-status-czeka';
                                elseif ($st === 'W trakcie') $statusClass = 'badge-status-w-trakcie';
                                elseif ($st === 'Gotowe') $statusClass = 'badge-status-gotowe';
                                elseif ($st === 'Odebrane' || $st === 'Sprzedane') $statusClass = 'badge-status-zamkniete';
                            ?>
                            <span class="badge badge-block <?php echo $statusClass; ?>">
                                <?php echo htmlspecialchars($st); ?>
                            </span><br>
                            <span class="badge badge-test-status <?php echo $testClass; ?>">
                                <?php echo $testTekst; ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                                if ($item['orderType'] === 'client' && !empty($item['price'])) {
                                    echo "<b>" . number_format($item['price'], 2, ',', ' ') . " zł</b>";
                                } elseif ($item['orderType'] === 'own' && !empty($item['salePrice'])) {
                                    echo "<b>" . number_format($item['salePrice'], 2, ',', ' ') . " zł</b>";
                                } else {
                                    echo "<span class='text-muted-sm'>-</span>";
                                }
                            ?>
                        </td>
                        <td><a href="obsluga.php?id=<?php echo $item['id']; ?>" class="action-link"><button class="btn btn-primary btn-sm-action">Obsługa</button></a></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
<?php } ?>

<?php require_once 'footer.php'; ?>