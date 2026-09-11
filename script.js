// --- MOTYW ---
const themeToggleBtn = document.getElementById('themeToggle');
const themeIcon = document.getElementById('themeIcon');
const rootEl = document.documentElement;

function updateThemeIcon(theme) {
    if (themeIcon) themeIcon.textContent = theme === 'dark' ? '☀️' : '🌙';
}
updateThemeIcon(rootEl.getAttribute('data-theme'));

if (themeToggleBtn) {
    themeToggleBtn.addEventListener('click', () => {
        const currentTheme = rootEl.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        rootEl.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        updateThemeIcon(newTheme);
    });
}

function getCsrfToken() {
    const dedicatedTokenInput = document.getElementById('csrfTokenAjax');
    if (dedicatedTokenInput) {
        return dedicatedTokenInput.value;
    }

    const tokenInput = document.querySelector('input[name="csrf_token"]');
    return tokenInput ? tokenInput.value : '';
}

function appendCsrfToken(formData) {
    formData.append('csrf_token', getCsrfToken());
}

// --- DROPDOWNY (lista.php) ---
window.toggleDropdown = function(menuId) {
    const menu = document.getElementById(menuId);
    if (!menu) return;
    document.querySelectorAll('.dropdown-menu').forEach(m => {
        if (m.id !== menuId) m.classList.add('hidden');
    });
    menu.classList.toggle('hidden');
};

window.addEventListener('click', function(e) {
    if (!e.target.closest('.custom-dropdown')) {
        document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
    }
});

// --- PRZEŁĄCZANIE SEKCJI (dodaj.php) ---
const typeClientRadio = document.getElementById('typeClient');
const typeOwnRadio = document.getElementById('typeOwn');
const clientSection = document.getElementById('clientSection');
const ownSection = document.getElementById('ownSection');

function toggleSections() {
    if (typeClientRadio.checked) {
        clientSection.classList.remove('hidden');
        ownSection.classList.add('hidden');
    } else {
        clientSection.classList.add('hidden');
        ownSection.classList.remove('hidden');
    }
}
if (typeClientRadio && typeOwnRadio) {
    typeClientRadio.addEventListener('change', toggleSections);
    typeOwnRadio.addEventListener('change', toggleSections);
}

// --- WZÓR BLOKADY I ZARZĄDZANIE WIDOKIEM (dodaj.php & obsluga.php) ---
window.toggleLockInputs = function(prefix = '') {
    const selectedRadio = document.querySelector(`input[name="lockType"]:checked`);
    if(!selectedRadio) return;
    const selectedType = selectedRadio.value;
    
    const pinContainer = document.getElementById(prefix + 'PinContainer') || document.getElementById('pinContainer');
    const patternContainer = document.getElementById(prefix + 'PatternContainer') || document.getElementById('patternContainer');

    if (selectedType === 'PIN/Hasło') {
        pinContainer.classList.remove('hidden');
        patternContainer.classList.add('hidden');
    } else if (selectedType === 'Wzór') {
        pinContainer.classList.add('hidden');
        patternContainer.classList.remove('hidden');
    } else {
        pinContainer.classList.add('hidden');
        patternContainer.classList.add('hidden');
    }
}

// Obsługa rysowania wzoru
function initPattern(gridId, inputId, clearBtnId) {
    const grid = document.getElementById(gridId);
    const patternInput = document.getElementById(inputId);
    const clearBtn = document.getElementById(clearBtnId);
    
    if (!grid || !patternInput) return;

    let isDrawing = false;
    let selectedDots = patternInput.value ? patternInput.value.split('-').map(s => s.trim()).filter(s => s !== '') : [];

    selectedDots.forEach(id => {
        const dot = grid.querySelector(`.pattern-dot[data-id="${id}"]`);
        if (dot) dot.classList.add('active');
    });

    function selectDot(dot) {
        const id = dot.getAttribute('data-id');
        if (!selectedDots.includes(id)) {
            selectedDots.push(id);
            dot.classList.add('active');
            patternInput.value = selectedDots.join('-');
        }
    }

    grid.querySelectorAll('.pattern-dot').forEach(dot => {
        dot.addEventListener('mousedown', (e) => { isDrawing = true; selectDot(dot); e.preventDefault(); });
        dot.addEventListener('mouseenter', () => { if (isDrawing) selectDot(dot); });
        dot.addEventListener('touchstart', (e) => { isDrawing = true; selectDot(dot); e.preventDefault(); }, {passive: false});
        dot.addEventListener('touchmove', (e) => {
            if (!isDrawing) return;
            const touch = e.touches[0];
            const target = document.elementFromPoint(touch.clientX, touch.clientY);
            if (target && target.classList.contains('pattern-dot') && target.closest(`#${gridId}`)) {
                selectDot(target);
            }
        });
    });

    window.addEventListener('mouseup', () => { isDrawing = false; });
    window.addEventListener('touchend', () => { isDrawing = false; });

    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            selectedDots = [];
            patternInput.value = '';
            grid.querySelectorAll('.pattern-dot').forEach(d => d.classList.remove('active'));
        });
    }
}

initPattern('patternGrid', 'patternCodeInput', 'clearPatternBtn');
initPattern('editPatternGrid', 'editPatternCodeInput', 'editClearPatternBtn');

// --- EDYCJA W OBSLUGA.PHP ---
window.toggleEdit = function(sectionId) {
    const viewEl = document.getElementById('view-' + sectionId);
    const editEl = document.getElementById('edit-' + sectionId);
    const btnEl = document.getElementById('edit-btn-' + (sectionId === 'section1' ? '1' : (sectionId === 'sale' ? 'sale' : '2')));

    if (viewEl.classList.contains('hidden')) {
        viewEl.classList.remove('hidden');
        editEl.classList.add('hidden');
        btnEl.textContent = 'Edytuj';
    } else {
        viewEl.classList.add('hidden');
        editEl.classList.remove('hidden');
        btnEl.textContent = 'Anuluj';
    }
};

// --- DODAWANIE I USUWANIE CZĘŚCI (obsluga.php) ---
window.odswiezTabeluCzesci = function(parts, totalPartsPrice) {
    const container = document.getElementById('partsContainer');
    if (!parts || parts.length === 0) {
        container.innerHTML = '<p class="empty-parts" id="emptyPartsMsg">Brak dodanych części do tej naprawy.</p>';
        return;
    }

    let html = '<table class="parts-table"><thead><tr><th>Nazwa części</th><th>Cena</th><th class="th-action">Akcja</th></tr></thead><tbody>';
    
    parts.forEach(function(part) {
        let cenaFormatted = parseFloat(part.cena).toLocaleString('pl-PL', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        html += `<tr>
            <td>${window.escapeHtml(part.nazwa)}</td>
            <td><b class="part-price-bold">${cenaFormatted} zł</b></td>
            <td class="td-right">
                <button type="button" onclick="usunCzesci(${part.id}, document.querySelector('[name=id]').value)" class="btn-delete-part">Usuń</button>
            </td>
        </tr>`;
    });

    html += `<tr class="tr-total">
        <td class="td-total-label">Suma części:</td>
        <td colspan="2" class="td-total-value" id="totalPartsSum">${totalPartsPrice} zł</td>
    </tr></tbody></table>`;

    container.innerHTML = html;
};

window.escapeHtml = function(text) {
    var map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
};

window.dodajCzesc = function(zlecenieId) {
    var nazwaInput = document.getElementById('ajaxPartName');
    var cenaInput = document.getElementById('ajaxPartPrice');

    var nazwa = nazwaInput.value.trim();
    var cena = cenaInput.value;

    if (!nazwa) {
        alert('Podaj nazwę części.');
        return;
    }

    var formData = new FormData();
    formData.append('action', 'add_part');
    formData.append('part_name', nazwa);
    formData.append('part_price', cena);
    formData.append('ajax', '1');
    appendCsrfToken(formData);

    fetch('obsluga.php?id=' + zlecenieId, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            nazwaInput.value = '';
            cenaInput.value = '';
            window.odswiezTabeluCzesci(data.parts, data.totalPartsPrice);
        } else {
            alert(data.error || 'Błąd podczas dodawania części.');
        }
    })
    .catch(error => console.error('Błąd:', error));
};

window.usunCzesci = function(partId, zlecenieId) {
    if (!confirm('Czy na pewno chcesz usunąć tę część?')) return;

    var formData = new FormData();
    formData.append('action', 'delete_part');
    formData.append('part_id', partId);
    formData.append('ajax', '1');
    appendCsrfToken(formData);

    fetch('obsluga.php?id=' + zlecenieId, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.odswiezTabeluCzesci(data.parts, data.totalPartsPrice);
        } else {
            alert(data.error || 'Błąd podczas usuwania części.');
        }
    })
    .catch(error => console.error('Błąd:', error));
};

// --- STATUS TESTU (test.php) ---
window.zmienStatus = function(selectElement, klucz, zlecenieId) {
    var nowyStatus = selectElement.value;
    var formData = new FormData();
    
    formData.append('klucz_testu', klucz);
    formData.append('status', nowyStatus);
    appendCsrfToken(formData);

    fetch('test.php?id=' + zlecenieId, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            selectElement.style.borderColor = 'var(--success-color)';
            setTimeout(() => selectElement.style.borderColor = '', 600);
        } else {
            alert(data.error || 'Błąd podczas zapisu.');
        }
    })
    .catch(error => console.error('Błąd podczas zapisu:', error));
};
