<script>
    let rowIndex = {{ $rowIndex ?? 1 }};
    let systemCalculatedPrice = 0;
    let isManualPriceActive = false;

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

        const discountPercent =
            parseFloat(document.getElementById('globalDiscount')?.value) || 0;
        const ppnPercent =
            parseFloat(document.getElementById('ppnInput')?.value) || 0;

        const discountValue = total * (discountPercent / 100);
        const afterDiscount = total - discountValue;
        const ppnValue = afterDiscount * (ppnPercent / 100);
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

        // Event listener untuk manual grand total
        manualInput?.addEventListener('input', function(e) {
            let value = e.target.value;
            
            // Hanya ambil angka untuk parsing
            const numericValue = parseCurrency(value);
            
            if (numericValue > 0) {
                // Format display dengan currency
                e.target.value = formatCurrency(numericValue);
                
                isManualPriceActive = true;
                resetBtn.disabled = false;
                systemInfo?.classList.remove('hidden');
                systemValue.textContent = 'Rp ' + formatCurrency(systemCalculatedPrice);
                
                // Update display dan hidden input dengan nilai numeric (tidak diformat)
                grandDisplay.textContent = 'Rp ' + formatCurrency(numericValue);
                manualValueHidden.value = numericValue; // Simpan angka murni
            } else {
                isManualPriceActive = false;
                resetBtn.disabled = true;
                systemInfo?.classList.add('hidden');
                grandDisplay.textContent = 'Rp ' + formatCurrency(systemCalculatedPrice);
                manualValueHidden.value = ''; // Kosongkan jika tidak ada nilai
            }
        });

        // Reset manual price
        resetBtn?.addEventListener('click', function() {
            manualInput.value = '';
            isManualPriceActive = false;
            this.disabled = true;
            systemInfo?.classList.add('hidden');
            grandDisplay.textContent = 'Rp ' + formatCurrency(systemCalculatedPrice);
            manualValueHidden.value = ''; // Pastikan hidden input juga dikosongkan
        });
    }

    /* =========================
       ROW EVENTS
    ========================= */
    function addRowListeners(row) {
        const priceInput = row.querySelector('.price-input');
        const quantityInput = row.querySelector('.quantity-input');
        const removeBtn = row.querySelector('.remove-row');

        priceInput.addEventListener('input', function (e) {
            const value = parseCurrency(e.target.value);
            e.target.value = formatCurrency(value);
            calculateSubtotal(row);
        });

        quantityInput.addEventListener('input', function () {
            calculateSubtotal(row);
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

        newRow.querySelectorAll('input').forEach(input => {
            const name = input.getAttribute('name');
            if (name) {
                input.setAttribute(
                    'name',
                    name.replace(/\[\d+]/, `[${rowIndex}]`)
                );
            }

            if (!input.classList.contains('subtotal-input')) {
                input.value = '';
            } else {
                input.value = '0';
            }
        });

        container.appendChild(newRow);
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

    updateRemoveButtons();
    syncPaymentStatus();
    initManualPriceHandlers();
    calculateGrandTotal();
</script>