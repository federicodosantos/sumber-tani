/**
 * Formats commands for R2 Customer Invoice (Thermal)
 */
function layoutR2Invoice(data) {
    const isDebtPayment = data.invoice.type === 'debt_payment';
    
    let cmds = [
        CMD_RESET,

        // header center
        CMD_ALIGN_CENTER,
        data.store.name + "\n",
        data.store.address + "\n\n",

        // Title
        CMD_ALIGN_CENTER,
        (isDebtPayment ? "BUKTI PEMBAYARAN HUTANG" : "NOTA PENJUALAN R2") + "\n",
        data.invoice.code + "\n",
        separator(LINE_CHARS),

        // Customer Info
        CMD_ALIGN_LEFT,
        "Pelanggan : " + data.customer.name + "\n",
        "Telp      : " + data.customer.phone + "\n",
        "Tanggal   : " + data.invoice.datetime + "\n",
        separator(LINE_CHARS),
    ];

    if (!isDebtPayment) {
        // --- PURCHASE CONTENT ---
        cmds.push(headerItemLine() + "\n");
        
        data.items.forEach(item => {
            cmds.push(
                formatItemLine(
                    item.name,
                    item.qty,
                    formatIDR(item.price),
                    formatIDR(item.total)
                ) + "\n"
            );
        });

        const totalVal = Number(data.transaction.total);
        const totalStr = formatIDR(totalVal);
        const discountVal = Number(data.transaction.discount || 0);
        const discountStr = formatIDR(discountVal);

        cmds.push(
            separator(LINE_CHARS),
            formatRightLine("METODE", data.transaction.payment_method) + "\n",
            formatRightLine("DISKON", discountStr) + "\n",
            "TOTAL : ".padStart(LINE_CHARS - totalStr.length - 1) + totalStr + "\n"
        );
    } else {
        // --- DEBT PAYMENT CONTENT ---
        const col1 = 15; // Invoice
        const col2 = 11; // Before
        const col3 = 11; // Paid
        const col4 = 11; // After

        cmds.push(
            "Invoice".padEnd(col1) +
            "Awal".padStart(col2) +
            "Bayar".padStart(col3) +
            "Sisa".padStart(col4) + "\n"
        );

        data.details.forEach(detail => {
            cmds.push(
                (detail.inv_code.length > col1 ? detail.inv_code.substring(0, col1-1) + "…" : detail.inv_code).padEnd(col1) +
                formatIDR(detail.debt_before).padStart(col2) +
                formatIDR(detail.amount_paid).padStart(col3) +
                formatIDR(detail.debt_after).padStart(col4) + "\n"
            );
        });

        const totalPaidStr = formatIDR(data.payment.total);
        cmds.push(
            separator(LINE_CHARS),
            formatRightLine("METODE", data.payment.payment_method) + "\n",
            "TOTAL BAYAR : ".padStart(LINE_CHARS - totalPaidStr.length - 1) + totalPaidStr + "\n"
        );
    }

    cmds.push(
        separator(LINE_CHARS),
        "\n",
        
        // Signatures (Thermal approximation)
        CMD_ALIGN_LEFT,
        " Tanda Terima,            Hormat Kami,\n\n\n",
        "(" + data.customer.name.substring(0, 12).padEnd(12) + ")        ( Admin )\n",
        "\n",

        // Footer
        CMD_ALIGN_CENTER,
        "Terima kasih atas kepercayaan Anda!\n",
        CMD_CUT_PAPER
    );

    return cmds;
}
