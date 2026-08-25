/**
 * Formats commands for Cashier Receipt
 */
function layoutCashierReceipt(data) {
    const totalVal = Number(data.transaction.total);
    const totalStr = formatIDR(totalVal);
    const discountVal = Number(data.transaction.discount || 0);
    const discountStr = formatIDR(discountVal);
    
    const paymentMethod = data.transaction.payment_method || "-";
    const cashReceived = data.transaction.cash_received !== null && data.transaction.cash_received !== undefined
        ? formatIDR(data.transaction.cash_received)
        : null;
    const changeAmount = data.transaction.change_amount !== null && data.transaction.change_amount !== undefined
        ? formatIDR(data.transaction.change_amount)
        : null;

    let cmds = [
        CMD_RESET,

        // header center
        CMD_ALIGN_CENTER,
        data.store.name + "\n",
        data.store.address + "\n\n",

        // left align info
        CMD_ALIGN_LEFT,
        "Tanggal: " + data.transaction.datetime + "\n",
        separator(LINE_CHARS),

        // HEADER KOLOM
        headerItemLine() + "\n",
    ];

    // Items
    data.items.forEach(item => {
        cmds.push(
            formatItemLine(
                item.name,
                formatQty(item.qty),
                formatIDR(item.price),
                formatIDR(item.total)
            ) + "\n"
        );
    });

    // Summary
    cmds.push(
        separator(LINE_CHARS),
        formatRightLine("METODE", paymentMethod) + "\n",
        formatRightLine("DISKON", discountStr) + "\n",
        "TOTAL : ".padStart(LINE_CHARS - totalStr.length - 1) + totalStr + "\n"
    );

    if (cashReceived) cmds.push(formatRightLine("TUNAI", cashReceived) + "\n");
    if (changeAmount) cmds.push(formatRightLine("KEMBALIAN", changeAmount) + "\n");

    cmds.push(
        separator(LINE_CHARS),
        "\n",
        
        // Footer Center
        CMD_ALIGN_CENTER,
        "Terima kasih atas kunjungan Anda!\n",
        data.store.phone + "\n",
        data.store.email + "\n",
        CMD_CUT_PAPER
    );

    return cmds;
}
