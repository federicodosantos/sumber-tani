<script>
    let rowIndex = {{ $rowIndex ?? 1 }};
    let systemCalculatedPrice = 0;
    let isManualPriceActive = false;

    /* =========================
       DISCOUNT TYPE TOGGLE
    ========================= */
    const discountTypeInput = document.getElementById('discountTypeInput');
    const discountUnitLabel = document.getElementById('discountUnitLabel');
    const toggleBtn = document.getElementById('toggleDiscountType');
    const ppnTypeInput = document.getElementById('ppnTypeInput');
    const ppnUnitLabel = document.getElementById('ppnUnitLabel');
    const togglePpnBtn = document.getElementById('togglePpnType');

    toggleBtn?.addEventListener('click', function () {
        const discountInput = document.getElementById('globalDiscount');
        const current = discountTypeInput.value;
        if (current === 'percent') {
            discountTypeInput.value = 'nominal';
            discountUnitLabel.textContent = 'Rp';
            toggleBtn.textContent = 'Rp';
            // Reset max constraint
            discountInput?.removeAttribute('max');
        } else {
            discountTypeInput.value = 'percent';
            discountUnitLabel.textContent = '%';
            toggleBtn.textContent = '%';
            discountInput?.setAttribute('max', '100');
        }
        if (discountInput) discountInput.value = 0;
        calculateGrandTotal();
    });

    togglePpnBtn?.addEventListener('click', function () {
        const ppnInput = document.getElementById('ppnInput');
        const current = ppnTypeInput.value;
        if (current === 'percent') {
            ppnTypeInput.value = 'nominal';
            ppnUnitLabel.textContent = 'Rp';
            togglePpnBtn.textContent = 'Rp';
            ppnInput?.removeAttribute('max');
        } else {
            ppnTypeInput.value = 'percent';
            ppnUnitLabel.textContent = '%';
            togglePpnBtn.textContent = '%';
            ppnInput?.setAttribute('max', '100');
        }
        if (ppnInput) ppnInput.value = 0;
        calculateGrandTotal();
    });

    /* =========================
       UTIL
    ========================= */
    function formatCurrency(num) {
        return new Intl.NumberFormat('id-ID').format(Math.round(num));
    }

    function parseCurrency(str) {
        if (!str) return 0;
        return parseFloat(
            str.toString().replace(/[^0-9,-]/g, '').replace(',', '.')
        ) || 0;
    }

    /* =========================
       CALCULATION
    ========================= */
    function calculateSubtotal(row) {
        const priceInput = row.querySelector('.price-input');
        const quantityInput = row.querySelector('.quantity-input');
        const subtotalInput = row.querySelector('.subtotal-input');

        const price = parseCurrency(priceInput.value);
        const qty = parseFloat(quantityInput.value) || 0;

        const subtotal = price * qty;
        subtotalInput.value = formatCurrency(subtotal);

        calculateGrandTotal();
    }

    function calculateGrandTotal() {
        let total = 0;

        document.querySelectorAll('.product-row').forEach(row => {
            total += parseCurrency(row.querySelector('.subtotal-input').value);
        });

        const discountType = discountTypeInput?.value || 'percent';
        const discountInput =
            parseFloat(document.getElementById('globalDiscount')?.value) || 0;
        const ppnInput = parseFloat(document.getElementById('ppnInput')?.value) || 0;
        const ppnType = ppnTypeInput?.value || 'percent';

        let discountValue = 0;
        if (discountType === 'percent') {
            discountValue = total * (discountInput / 100);
        } else {
            discountValue = discountInput;
        }

        const afterDiscount = total - discountValue;
        const ppnValue = ppnType === 'percent'
            ? afterDiscount * (ppnInput / 100)
            : ppnInput;
        const grandTotal = afterDiscount + ppnValue;

        // Simpan harga sistem
        systemCalculatedPrice = grandTotal;

        // Update display
        document.getElementById('totalDisplay').innerText =
            'Rp ' + formatCurrency(total);
        document.getElementById('discountDisplay').innerText =
            '- Rp ' + formatCurrency(discountValue);
        document.getElementById('ppnDisplay').innerText =
            '+ Rp ' + formatCurrency(ppnValue);

        // Tampilkan/sembunyikan manual price section
        const manualSection = document.getElementById('manualPriceSection');
        if (grandTotal > 0) {
            manualSection?.classList.remove('hidden');
        } else {
            manualSection?.classList.add('hidden');
            isManualPriceActive = false;
            document.getElementById('systemPriceInfo')?.classList.add('hidden');
        }

        // Update grand total display
        updateGrandTotalDisplay();
    }

    /* =========================
       MANUAL PRICE HANDLING
    ========================= */
    function updateGrandTotalDisplay() {
        if (isManualPriceActive) {
            const manualPrice = parseCurrency(document.getElementById('manualGrandTotal')?.value);
            document.getElementById('grandTotalDisplay').innerText =
                'Rp ' + formatCurrency(manualPrice);
            document.getElementById('manualGrandTotalValue').value = manualPrice;
        } else {
            document.getElementById('grandTotalDisplay').innerText =
                'Rp ' + formatCurrency(systemCalculatedPrice);
            document.getElementById('manualGrandTotalValue').value = '';
        }
    }

    function initManualPriceHandlers() {
        const manualInput = document.getElementById('manualGrandTotal');
        const resetBtn = document.getElementById('resetManualPrice');
        const systemInfo = document.getElementById('systemPriceInfo');
        const systemValue = document.getElementById('systemPriceValue');
        const grandDisplay = document.getElementById('grandTotalDisplay');
        const manualValueHidden = document.getElementById('manualGrandTotalValue');

        manualInput?.addEventListener('input', function(e) {
            let value = e.target.value;
            const numericValue = parseCurrency(value);

            if (numericValue > 0) {
                e.target.value = formatCurrency(numericValue);

                isManualPriceActive = true;
                resetBtn.disabled = false;
                systemInfo?.classList.remove('hidden');
                systemValue.textContent = 'Rp ' + formatCurrency(systemCalculatedPrice);

                grandDisplay.textContent = 'Rp ' + formatCurrency(numericValue);
                manualValueHidden.value = numericValue;
            } else {
                isManualPriceActive = false;
                resetBtn.disabled = true;
                systemInfo?.classList.add('hidden');
                grandDisplay.textContent = 'Rp ' + formatCurrency(systemCalculatedPrice);
                manualValueHidden.value = '';
            }
        });

        resetBtn?.addEventListener('click', function() {
            manualInput.value = '';
            isManualPriceActive = false;
            this.disabled = true;
            systemInfo?.classList.add('hidden');
            grandDisplay.textContent = 'Rp ' + formatCurrency(systemCalculatedPrice);
            manualValueHidden.value = '';
        });
    }

    /* =========================
       ROW EVENTS
    ========================= */
    function addRowListeners(row) {
        const priceInput = row.querySelector('.price-input');
        const quantityInput = row.querySelector('.quantity-input');
        const removeBtn = row.querySelector('.remove-row');
        const comboboxHidden = row.querySelector('.combobox-hidden');

        priceInput.addEventListener('input', function (e) {
            const value = parseCurrency(e.target.value);
            e.target.value = formatCurrency(value);
            calculateSubtotal(row);
        });

        quantityInput.addEventListener('input', function () {
            calculateSubtotal(row);
        });

        // Searchable Combobox Listener
        comboboxHidden?.addEventListener('combobox-change', function(e) {
            const data = e.detail.selected;
            if (data) {
                // Auto-fill unit if available (optional enhancement)
                // const unitInput = row.querySelector('input[name*="[unit]"]');
                // if (unitInput && !unitInput.value) unitInput.value = data.unit || 'PCS';
                
                calculateSubtotal(row);
            }
        });

        if (removeBtn) {
            removeBtn.addEventListener('click', function () {
                row.remove();
                updateRemoveButtons();
                calculateGrandTotal();
            });
        }
    }

    function updateRemoveButtons() {
        const rows = document.querySelectorAll('.product-row');
        rows.forEach(row => {
            const btn = row.querySelector('.remove-row');
            if (btn) btn.disabled = rows.length === 1;
        });
    }

    /* =========================
       ADD ROW
    ========================= */
    document.getElementById('addRow')?.addEventListener('click', function () {
        const container = document.getElementById('rowsContainer');
        const firstRow = container.querySelector('.product-row');
        const newRow = firstRow.cloneNode(true);

        newRow.querySelectorAll('input, select').forEach(el => {
            const name = el.getAttribute('name');
            if (name) {
                el.setAttribute(
                    'name',
                    name.replace(/\[\d+]/, `[${rowIndex}]`)
                );
            }

            if (el.tagName === 'SELECT') {
                el.selectedIndex = 0;
            } else if (!el.classList.contains('subtotal-input')) {
                el.value = '';
            } else {
                el.value = '0';
            }
        });

        container.appendChild(newRow);
        
        // Re-initialize Alpine for the new row (crucial for searchable combobox)
        if (window.Alpine) {
            window.Alpine.initTree(newRow);
        }

        addRowListeners(newRow);
        updateRemoveButtons();
        calculateGrandTotal();
        rowIndex++;
    });

    /* =========================
       HEADER EVENTS
    ========================= */
    document.getElementById('globalDiscount')
        ?.addEventListener('input', calculateGrandTotal);

    document.getElementById('ppnInput')
        ?.addEventListener('input', calculateGrandTotal);

    /* =========================
       PAYMENT METHOD ↔ LUNAS
    ========================= */
    const methodSelect = document.getElementById('method');
    const isPaidCheckbox = document.getElementById('isPaid');

    function syncPaymentStatus() {
        if (!methodSelect || !isPaidCheckbox) return;

        if (methodSelect.value === '0') {
            isPaidCheckbox.checked = true;
            isPaidCheckbox.disabled = true;
        } else {
            isPaidCheckbox.disabled = false;
        }
    }

    methodSelect?.addEventListener('change', syncPaymentStatus);

    /* =========================
       INIT ON PAGE LOAD
    ========================= */
    document.querySelectorAll('.product-row').forEach(row => {
        addRowListeners(row);
    });

    // Set initial discount type max constraint
    if (discountTypeInput?.value === 'percent') {
        document.getElementById('globalDiscount')?.setAttribute('max', '100');
    }
    if (ppnTypeInput?.value === 'percent') {
        document.getElementById('ppnInput')?.setAttribute('max', '100');
    }

    updateRemoveButtons();
    syncPaymentStatus();
    initManualPriceHandlers();
    calculateGrandTotal();
</script>
