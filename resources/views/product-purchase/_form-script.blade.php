<script>
    let rowIndex = parseInt('{{ $rowIndex ?? 1 }}');
    let systemCalculatedPrice = 0;
    let isManualPriceActive = false;
    // Track the currently active form context (the form that is visible/being interacted with)
    let activeFormContext = null;

    function setRowIndex(val) {
        rowIndex = val;
    }

    /* =========================
       UTIL
    ========================= */
    function formatCurrency(num) {
        // Round to max 3 decimal places (matches MySQL DECIMAL round-half-away-from-zero)
        // e.g. 36750.3756 -> "36.750,376"  36746.8647 -> "36.746,865"  36750 -> "36.750"
        num = Math.round(num * 1000) / 1000;
        return new Intl.NumberFormat('id-ID', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 3
        }).format(num);
    }

    function parseCurrency(str) {
        if (!str && str !== 0) return 0;
        let s = str.toString().trim();
        // Format ID: titik = pemisah ribuan, koma = desimal
        // Hapus semua titik (ribuan), ganti koma jadi titik (desimal)
        s = s.replace(/\./g, '').replace(',', '.');
        return parseFloat(s) || 0;
    }

    /**
     * Get the form context (closest form or form-card container) from an element.
     * This ensures we scope all queries to the correct form when multiple forms exist.
     */
    function getFormContext(element) {
        if (!element) return document;
        return element.closest('form') || element.closest('[id="rowsContainer"]')?.parentElement || document;
    }

    /**
     * Find an element by ID within a specific form context.
     * Falls back to document if the element isn't found in the form context.
     */
    function findInContext(ctx, id) {
        if (ctx && ctx !== document) {
            const el = ctx.querySelector('#' + CSS.escape(id));
            if (el) return el;
        }
        return document.getElementById(id);
    }

    /* =========================
       CALCULATION TRIGGER
    ========================= */
    window.addEventListener('rupiah-change', function(e) {
        // Determine which form context the change came from
        const sourceEl = e.target || e.srcElement;
        if (sourceEl && sourceEl !== window) {
            const ctx = getFormContext(sourceEl);
            calculateGrandTotal(ctx);
        } else if (activeFormContext) {
            calculateGrandTotal(activeFormContext);
        }
    });

    // Reset form when modal is closed (X button, backdrop, or Batal)
    window.addEventListener('modal-closed', (e) => {
        if (e.detail === 'create-purchase' || e.detail === 'edit-purchase') {
            window.resetPurchaseForm();
        }
    });

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
        const qty = parseFloat((quantityInput?.value || '').replace(',', '.')) || 0;

        const netPrice = het - basicDisc - addDisc;
        const subtotal = netPrice * qty;

        // Update hidden inputs for submission
        if (netPriceHidden) netPriceHidden.value = netPrice;
        if (subtotalHidden) subtotalHidden.value = subtotal;

        // Update display inputs of input-rupiah via custom event
        const namePrefix = hetInput?.name.split('[')[0]; // products
        const currentRowIndex = hetInput?.name.match(/\[(\d+)\]/)[1];
        
        window.dispatchEvent(new CustomEvent('update-rupiah-value', { 
            detail: { name: `products[${currentRowIndex}][net_price]`, value: netPrice } 
        }));
        
        window.dispatchEvent(new CustomEvent('update-rupiah-value', { 
            detail: { name: `products[${currentRowIndex}][subtotal]`, value: subtotal } 
        }));

        // Calculate grand total scoped to the correct form
        const ctx = getFormContext(row);
        calculateGrandTotal(ctx);
    }

    function calculateGrandTotal(ctx) {
        if (!ctx) ctx = activeFormContext || document;

        let total = 0;

        ctx.querySelectorAll('.product-row').forEach(row => {
            const subtotalHidden = row.querySelector('input[name$="[subtotal]"][type="hidden"]');
            total += parseFloat(subtotalHidden?.value) || 0;
        });

        const discountType = findInContext(ctx, 'discountTypeInput')?.value || 'percent';
        const ppnType = findInContext(ctx, 'ppnTypeInput')?.value || 'percent';

        const discountInputEl = discountType === 'percent' 
            ? findInContext(ctx, 'globalDiscount') 
            : findInContext(ctx, 'discount_display');
        
        const ppnInputEl = ppnType === 'percent' 
            ? findInContext(ctx, 'ppnInput') 
            : findInContext(ctx, 'ppn_display');

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
        const totalDisplay = findInContext(ctx, 'totalDisplay');
        const discountDisplay = findInContext(ctx, 'discountDisplay');
        const ppnDisplay = findInContext(ctx, 'ppnDisplay');
        
        if (totalDisplay) totalDisplay.innerText = 'Rp ' + formatCurrency(total);
        if (discountDisplay) discountDisplay.innerText = '- Rp ' + formatCurrency(discountValue);
        if (ppnDisplay) ppnDisplay.innerText = '+ Rp ' + formatCurrency(ppnValue);

        // Tampilkan/sembunyikan manual price section
        const manualSection = findInContext(ctx, 'manualPriceSection');
        if (grandTotal > 0) {
            manualSection?.classList.remove('hidden');
        } else {
            manualSection?.classList.add('hidden');
            const manualInput = findInContext(ctx, 'manualGrandTotal');
            if (!manualInput || parseCurrency(manualInput.value) <= 0) {
                isManualPriceActive = false;
                findInContext(ctx, 'systemPriceInfo')?.classList.add('hidden');
            }
        }

        // Update grand total display
        updateGrandTotalDisplay(ctx);
    }

    /* =========================
       MANUAL PRICE HANDLING
    ========================= */
    function updateGrandTotalDisplay(ctx) {
        if (!ctx) ctx = activeFormContext || document;
        
        const grandTotalDisplay = findInContext(ctx, 'grandTotalDisplay');
        const manualGrandTotalValue = findInContext(ctx, 'manualGrandTotalValue');
        const systemInfo = findInContext(ctx, 'systemPriceInfo');
        const systemValue = findInContext(ctx, 'systemPriceValue');
        const resetBtn = findInContext(ctx, 'resetManualPrice');

        const manualInput = findInContext(ctx, 'manualGrandTotal');
        const manualPrice = parseCurrency(manualInput?.value);
        if (manualPrice > 0) {
            isManualPriceActive = true;
        }

        if (isManualPriceActive) {
            if (grandTotalDisplay) grandTotalDisplay.innerText = 'Rp ' + formatCurrency(manualPrice);
            if (manualGrandTotalValue) manualGrandTotalValue.value = manualPrice;
            
            if (systemInfo) systemInfo.classList.remove('hidden');
            if (systemValue) systemValue.textContent = 'Rp ' + formatCurrency(systemCalculatedPrice);
            if (resetBtn) resetBtn.disabled = false;
        } else {
            if (grandTotalDisplay) grandTotalDisplay.innerText = 'Rp ' + formatCurrency(systemCalculatedPrice);
            if (manualGrandTotalValue) manualGrandTotalValue.value = '';
            
            if (systemInfo) systemInfo.classList.add('hidden');
            if (resetBtn) resetBtn.disabled = true;
        }
    }

    function initManualPriceHandlers(ctx) {
        if (!ctx) ctx = document;
        
        const manualInput = findInContext(ctx, 'manualGrandTotal');
        const resetBtn = findInContext(ctx, 'resetManualPrice');
        const systemInfo = findInContext(ctx, 'systemPriceInfo');
        const systemValue = findInContext(ctx, 'systemPriceValue');
        const grandDisplay = findInContext(ctx, 'grandTotalDisplay');
        const manualValueHidden = findInContext(ctx, 'manualGrandTotalValue');

        // Hanya izinkan: angka, koma (maks 1), Backspace, Delete, navigasi
        manualInput?.addEventListener('keydown', function(e) {
            const k = e.key;
            const nav = ['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'Home', 'End', 'Enter'];
            const ok = /[0-9]/.test(k) || nav.includes(k) || e.ctrlKey || e.metaKey 
                       || (k === ',' && !this.value.includes(','));
            if (!ok) e.preventDefault();
        });

        manualInput?.addEventListener('input', function(e) {
            let value = e.target.value;

            // Jika user masih mengetik desimal (trailing comma), biarkan dulu
            if (value.endsWith(',')) {
                const rawSoFar = parseCurrency(value.slice(0, -1));
                manualValueHidden.value = rawSoFar || '';
                return;
            }

            const numericValue = parseCurrency(value);

            if (numericValue > 0) {
                // Reformatkan tapi jangan hapus bagian desimal yang sedang diketik
                const parts = value.split(',');
                const intPart = parseInt((parts[0] || '0').replace(/\./g, ''), 10);
                const intFormatted = intPart.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                e.target.value = parts[1] !== undefined ? intFormatted + ',' + parts[1] : intFormatted;

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
                calculateSubtotal(row);
            }
        });

        if (removeBtn) {
            removeBtn.addEventListener('click', function () {
                const ctx = getFormContext(row);
                row.remove();
                updateRemoveButtons(ctx);
                calculateGrandTotal(ctx);
            });
        }
    }

    function updateRemoveButtons(ctx) {
        if (!ctx) ctx = activeFormContext || document;
        const rows = ctx.querySelectorAll('.product-row');
        rows.forEach(row => {
            const btn = row.querySelector('.remove-row');
            if (btn) btn.disabled = rows.length === 1;
        });
    }

    /* =========================
       ADD ROW
    ========================= */
    function initAddRowButton(ctx) {
        if (!ctx) ctx = document;
        
        const addRowBtn = findInContext(ctx, 'addRow');
        if (!addRowBtn) return;
        
        // Remove old listeners by cloning the button
        const newAddRowBtn = addRowBtn.cloneNode(true);
        addRowBtn.parentNode.replaceChild(newAddRowBtn, addRowBtn);
        
        newAddRowBtn.addEventListener('click', function () {
            const container = findInContext(ctx, 'rowsContainer');
            if (!container) return;
            
            const firstRow = container.querySelector('.product-row');
            if (!firstRow) return;
            
            const newRow = firstRow.cloneNode(true);

            // CLEANUP: Remove Alpine-rendered artifacts to prevent duplication
            newRow.querySelectorAll('template').forEach(t => {
                while (t.nextSibling && (t.nextSibling.nodeType === 1) && t.nextSibling.tagName !== 'TEMPLATE' && !t.nextSibling.hasAttribute('x-data')) {
                    t.nextSibling.remove();
                }
            });

            // Clean Alpine internal state from all x-data elements so they reinitialize properly
            newRow.querySelectorAll('[x-data]').forEach(el => {
                delete el._x_dataStack;
                delete el.__x_is_init;
                el._x_effects?.forEach(e => e());
                delete el._x_effects;
                delete el._x_runEffects;
                delete el._x_undoAddedClasses;
                delete el._x_undoAddedStyles;
                if (el._x_cleanups) {
                    el._x_cleanups.forEach(c => c());
                    delete el._x_cleanups;
                }
            });

            // Update IDs to new row index
            newRow.querySelectorAll('[id]').forEach(el => {
                const id = el.getAttribute('id');
                if (id) {
                    el.setAttribute('id', id.replace(/_\d+_/, `_${rowIndex}_`));
                }
            });

            // Also update IDs in the format "products[0][field]_display" or "products[0][field]_value"
            newRow.querySelectorAll('[id*="products["]').forEach(el => {
                const id = el.getAttribute('id');
                if (id) {
                    el.setAttribute('id', id.replace(/products\[\d+\]/, `products[${rowIndex}]`));
                }
            });

            // Update input/select names and clear values
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
            
            // Re-initialize Alpine for the new row
            if (window.Alpine) {
                window.Alpine.initTree(newRow);
            }

            // Set default values for standard inputs
            const qtyInput = newRow.querySelector('input[name*="[quantity]"]');
            const unitInput = newRow.querySelector('input[name*="[unit]"]');
            if (qtyInput) qtyInput.value = 1;
            if (unitInput) unitInput.value = 'PCS';

            // Reset new row's combobox
            window.dispatchEvent(new CustomEvent('combobox-reset', { 
                detail: { name: `products[${rowIndex}][product_id]` } 
            }));

            // Reset new row's rupiah components
            const rupiahFields = ['het_price', 'basic_discount', 'additional_discount', 'net_price', 'subtotal'];
            rupiahFields.forEach(field => {
                window.dispatchEvent(new CustomEvent('update-rupiah-value', { 
                    detail: { name: `products[${rowIndex}][${field}]`, value: '' } 
                }));
            });

            addRowListeners(newRow);
            updateRemoveButtons(ctx);
            calculateGrandTotal(ctx);
            rowIndex++;
        });
    }

    /* =========================
       HEADER EVENTS
    ========================= */
    function initHeaderEvents(ctx) {
        if (!ctx) ctx = document;
        
        const globalDiscount = findInContext(ctx, 'globalDiscount');
        const ppnInput = findInContext(ctx, 'ppnInput');
        
        // Use a wrapper that passes the correct context
        const recalc = () => calculateGrandTotal(ctx);
        
        globalDiscount?.addEventListener('input', recalc);
        ppnInput?.addEventListener('input', recalc);
    }

    /* =========================
       PAYMENT METHOD ↔ LUNAS
    ========================= */
    function syncPaymentStatus(ctx) {
        if (!ctx) ctx = activeFormContext || document;
        const methodSelect = findInContext(ctx, 'method');
        const isPaidCheckbox = findInContext(ctx, 'isPaid');
        
        if (!methodSelect || !isPaidCheckbox) return;

        if (methodSelect.value === '0') {
            isPaidCheckbox.checked = true;
            isPaidCheckbox.disabled = true;
        } else {
            isPaidCheckbox.disabled = false;
        }
    }
    
    function initPaymentHandlers(ctx) {
        if (!ctx) ctx = document;
        const methodSelect = findInContext(ctx, 'method');
        methodSelect?.addEventListener('change', () => syncPaymentStatus(ctx));
    }

    /* =========================
       INIT ON PAGE LOAD
    ========================= */
    function initPurchaseForm(ctx) {
        if (!ctx) ctx = document;
        
        // Set the active form context
        activeFormContext = ctx;
        
        // Reset manual price state for fresh init
        const manualInput = findInContext(ctx, 'manualGrandTotal');
        const initialManualValue = parseCurrency(manualInput?.value);
        isManualPriceActive = initialManualValue > 0;
        systemCalculatedPrice = 0;
        
        ctx.querySelectorAll('.product-row').forEach(row => {
            addRowListeners(row);
        });

        // Initialize add row button within this context
        initAddRowButton(ctx);
        
        // Initialize header events (PPN, discount inputs)
        initHeaderEvents(ctx);
        
        // Initialize payment method handler
        initPaymentHandlers(ctx);

        updateRemoveButtons(ctx);
        syncPaymentStatus(ctx);
        initManualPriceHandlers(ctx);
        calculateGrandTotal(ctx);
    }

    function resetPurchaseForm() {
        const ctx = activeFormContext || document;
        
        const globalDiscount = findInContext(ctx, 'globalDiscount');
        const ppnInput = findInContext(ctx, 'ppnInput');
        const method = findInContext(ctx, 'method');
        const manualGrandTotal = findInContext(ctx, 'manualGrandTotal');
        const manualValueHidden = findInContext(ctx, 'manualGrandTotalValue');
        const resetBtn = findInContext(ctx, 'resetManualPrice');
        const systemInfo = findInContext(ctx, 'systemPriceInfo');

        if (globalDiscount) globalDiscount.value = '';
        if (ppnInput) ppnInput.value = '';

        // Trigger change to update input-rupiah components
        window.dispatchEvent(new CustomEvent('update-rupiah-value', { detail: { name: 'ppn', value: '' } }));
        window.dispatchEvent(new CustomEvent('update-rupiah-value', { detail: { name: 'discount', value: '' } }));

        if (method) method.value = '0';
        syncPaymentStatus(ctx);

        // Rows
        const container = findInContext(ctx, 'rowsContainer');
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
        calculateGrandTotal(ctx);
        updateRemoveButtons(ctx);
    }

    // Export functions to window for modal use
    window.initPurchaseForm = initPurchaseForm;
    window.setRowIndex = setRowIndex;
    window.resetPurchaseForm = resetPurchaseForm;

    // Run on initial load
    initPurchaseForm();
</script>
