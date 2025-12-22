<script>
    let rowIndex = {{ $rowIndex ?? 1 }};

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

        document.getElementById('totalDisplay').innerText =
            'Rp ' + formatCurrency(total);
        document.getElementById('discountDisplay').innerText =
            '- Rp ' + formatCurrency(discountValue);
        document.getElementById('ppnDisplay').innerText =
            '+ Rp ' + formatCurrency(ppnValue);
        document.getElementById('grandTotalDisplay').innerText =
            'Rp ' + formatCurrency(grandTotal);
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
       HEADER EVENTS (FIX UTAMA)
    ========================= */
    document.getElementById('globalDiscount')
        ?.addEventListener('input', calculateGrandTotal);

    document.getElementById('ppnInput')
        ?.addEventListener('change', calculateGrandTotal);

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
       INIT ON PAGE LOAD (INI KUNCI)
    ========================= */
    document.querySelectorAll('.product-row').forEach(row => {
        addRowListeners(row);
    });

    updateRemoveButtons();
    syncPaymentStatus();
    calculateGrandTotal();
</script>
