<script>
    let rowIndex = parseInt('{{ $rowIndex ?? 1 }}');
    let systemCalculatedPrice = 0;
    let isManualPriceActive = false;

    function setRowIndex(val) {
        rowIndex = val;
    }

    /* =========================
       DISCOUNT TYPE TOGGLE
    ========================= */
    /* =========================
       CALCULATION TRIGGER
    ========================= */
    window.addEventListener('rupiah-change', function() {
        calculateGrandTotal();
    });

    // Reset form when modal is closed (X button, backdrop, or Batal)
    window.addEventListener('modal-closed', (e) => {
        if (e.detail === 'create-purchase' || e.detail === 'edit-purchase') {
            window.resetPurchaseForm();
        }
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
        // Find hidden value inputs of input-rupiah components
        const hetInput = row.querySelector('input[name$="[het_price]"][type="hidden"]');
        const basicDiscInput = row.querySelector('input[name$="[basic_discount]"][type="hidden"]');
        const addDiscInput = row.querySelector('input[name$="[additional_discount]"][type="hidden"]');
        const netPriceHidden = row.querySelector('input[name$="[net_price]"][type="hidden"]');
        const subtotalHidden = row.querySelector('input[name$="[subtotal]"][type="hidden"]');
        
        const quantityInput = row.querySelector('.quantity-input');

        const het = parseFloat(hetInput?.value) || 0;
        const basicDisc = parseFloat(basicDiscInput?.value) || 0;
        const addDisc = parseFloat(addDiscInput?.value) || 0;
        const qty = parseFloat(quantityInput?.value) || 0;

        const netPrice = het - basicDisc - addDisc;
        const subtotal = netPrice * qty;

        // Update hidden inputs for submission
        if (netPriceHidden) netPriceHidden.value = netPrice;
        if (subtotalHidden) subtotalHidden.value = subtotal;

        // Update display inputs of input-rupiah via custom event
        const namePrefix = hetInput?.name.split('[')[0]; // products
        const rowIndex = hetInput?.name.match(/\[(\d+)\]/)[1];
        
        window.dispatchEvent(new CustomEvent('update-rupiah-value', { 
            detail: { name: `products[${rowIndex}][net_price]`, value: netPrice } 
        }));
        
        window.dispatchEvent(new CustomEvent('update-rupiah-value', { 
            detail: { name: `products[${rowIndex}][subtotal]`, value: subtotal } 
        }));

        calculateGrandTotal();
    }

    function calculateGrandTotal() {
        let total = 0;

        document.querySelectorAll('.product-row').forEach(row => {
            const subtotalHidden = row.querySelector('input[name$="[subtotal]"][type="hidden"]');
            total += parseFloat(subtotalHidden?.value) || 0;
        });

        const discountType = document.getElementById('discountTypeInput')?.value || 'percent';
        const ppnType = document.getElementById('ppnTypeInput')?.value || 'percent';

        const discountInputEl = discountType === 'percent' 
            ? document.getElementById('globalDiscount') 
            : document.getElementById('globalDiscountNominal_value');
        
        const ppnInputEl = ppnType === 'percent' 
            ? document.getElementById('ppnInput') 
            : document.getElementById('ppnInputNominal_value');

        const discountInputValue = parseCurrency(discountInputEl?.value);
        const ppnInputValue = parseCurrency(ppnInputEl?.value);

        let discountValue = 0;
        if (discountType === 'percent') {
            discountValue = total * (discountInputValue / 100);
        } else {
            discountValue = discountInputValue;
        }

        const afterDiscount = total - discountValue;
        const ppnValue = ppnType === 'percent'
            ? afterDiscount * (ppnInputValue / 100)
            : ppnInputValue;
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
        const quantityInput = row.querySelector('.quantity-input');
        const comboboxHidden = row.querySelector('.product-select input[type="hidden"]');
        const removeBtn = row.querySelector('.remove-row');

        quantityInput?.addEventListener('input', function () {
            calculateSubtotal(row);
        });

        // Rupiah Change Listener for HET and Discounts
        row.addEventListener('rupiah-change', function(e) {
            // Only recalculate if it's one of the price/discount inputs
            if (e.detail.name.includes('het_price') || 
                e.detail.name.includes('basic_discount') || 
                e.detail.name.includes('additional_discount')) {
                calculateSubtotal(row);
            }
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

        // CLEANUP: Remove Alpine-rendered artifacts to prevent duplication
        // Alpine 3 renders elements as siblings of the template tags.
        // We must remove these clones before Alpine.initTree re-renders them.
        newRow.querySelectorAll('template').forEach(t => {
            while (t.nextSibling && (t.nextSibling.nodeType === 1) && t.nextSibling.tagName !== 'TEMPLATE' && !t.nextSibling.hasAttribute('x-data')) {
                t.nextSibling.remove();
            }
        });

        newRow.querySelectorAll('[id]').forEach(el => {
            const id = el.getAttribute('id');
            if (id) {
                el.setAttribute('id', id.replace(/_\d+_/, `_${rowIndex}_`));
            }
        });

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
            } else {
                el.value = '';
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
    function initPurchaseForm() {
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
    }

    function resetPurchaseForm() {
        const globalDiscount = document.getElementById('globalDiscount');
        const ppnInput = document.getElementById('ppnInput');
        const method = document.getElementById('method');
        const manualGrandTotal = document.getElementById('manualGrandTotal');
        const manualValueHidden = document.getElementById('manualGrandTotalValue');
        const resetBtn = document.getElementById('resetManualPrice');
        const systemInfo = document.getElementById('systemPriceInfo');

        // Reset global inputs
        const discountTypeInput = document.getElementById('discountTypeInput');
        const ppnTypeInput = document.getElementById('ppnTypeInput');

        if (globalDiscount) globalDiscount.value = '';
        if (ppnInput) ppnInput.value = '';

        // Trigger change to update input-rupiah components
        window.dispatchEvent(new CustomEvent('update-rupiah-value', { detail: { name: 'ppn', value: '' } }));
        window.dispatchEvent(new CustomEvent('update-rupiah-value', { detail: { name: 'discount', value: '' } }));

        if (method) method.value = '0';
        syncPaymentStatus();

        // Rows
        const container = document.getElementById('rowsContainer');
        const rows = container?.querySelectorAll('.product-row') || [];
        
        // Remove all but first
        for (let i = 1; i < rows.length; i++) {
            rows[i].remove();
        }

        // Reset first row
        const firstRow = rows[0];
        if (firstRow) {
            const qtyInput = firstRow.querySelector('input[name*="[quantity]"]');
            const unitInput = firstRow.querySelector('input[name*="[unit]"]');
            if (qtyInput) qtyInput.value = 1;
            if (unitInput) unitInput.value = 'PCS';

            // Reset first row rupiah components
            const firstRowFields = ['het_price', 'basic_discount', 'additional_discount', 'net_price', 'subtotal'];
            firstRowFields.forEach(field => {
                window.dispatchEvent(new CustomEvent('update-rupiah-value', { 
                    detail: { name: `products[0][${field}]`, value: '' } 
                }));
            });

            // Reset first row combobox
            window.dispatchEvent(new CustomEvent('combobox-reset', { 
                detail: { name: 'products[0][product_id]' } 
            }));
        }

        // Reset manual price state
        if (manualGrandTotal) manualGrandTotal.value = '';
        if (manualValueHidden) manualValueHidden.value = '';
        isManualPriceActive = false;
        if (resetBtn) resetBtn.disabled = true;
        systemInfo?.classList.add('hidden');

        rowIndex = 1;
        calculateGrandTotal();
        updateRemoveButtons();
    }

    // Export functions to window for modal use
    window.initPurchaseForm = initPurchaseForm;
    window.setRowIndex = setRowIndex;
    window.resetPurchaseForm = resetPurchaseForm;

    // Run on initial load
    initPurchaseForm();
</script>
