<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @php use Illuminate\Support\Number; @endphp
    <title>Laporan Persediaan</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #333; }
        .header { text-align: center; margin-bottom: 14px; padding-bottom: 8px; border-bottom: 2px solid #ddd; }
        .header h1 { font-size: 18px; margin: 0; color: #8AB763; }
        .header h2 { font-size: 13px; margin: 4px 0 0 0; color: #333; }
        .header p { margin: 2px 0; font-size: 9px; }
        .section-title { font-size: 11px; font-weight: bold; margin: 14px 0 6px 0; text-transform: uppercase; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        table.items th, table.items td { border: 1px solid #ddd; padding: 4px 5px; }
        table.items th { background-color: #f5f5f5; font-size: 8px; text-transform: uppercase; text-align: left; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row td { font-weight: bold; background: #f5f5f5; }
        .grand { width: 360px; margin-left: auto; border-collapse: collapse; margin-top: 10px; }
        .grand td { padding: 5px 8px; font-size: 10px; }
        .grand .label { text-align: left; }
        .grand .amount { text-align: right; font-weight: bold; }
        .grand .final { border-top: 2px solid #333; font-size: 11px; }
        .note { font-size: 8px; color: #555; margin-top: 12px; line-height: 1.4; }
        .warn { color: #b45309; }
        .empty { text-align: center; font-style: italic; color: #999; }
        .missing { color: #b91c1c; }
    </style>
</head>
<body>
    <div class="header">
        <h1>TOKO SUMBERTANI</h1>
        <p>Jl. Trans Sulawesi, Motolohu, Kec. Randangan, Kab. Pohuwato, Gorontalo 96469</p>
        <p>Telp: +62 813-5674-5129 | Email: sumbertani0209@gmail.com</p>
        <h2>Laporan Persediaan / Aset Barang</h2>
        <p>Posisi per {{ $printedAt->translatedFormat('d F Y, H:i') }}</p>
    </div>

    <div class="section-title">Persediaan Gudang</div>
    <table class="items">
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama Produk</th>
                <th>Kategori</th>
                <th class="text-center">Batch</th>
                <th class="text-right">Qty</th>
                <th class="text-right">HPP</th>
                <th class="text-right">Nilai</th>
                <th class="text-right">Harga Konsumen</th>
                <th class="text-right">Harga R1</th>
                <th class="text-right">Harga R2</th>
                <th class="text-center">Kadaluarsa</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($inventoryRows as $row)
                <tr>
                    <td>{{ $row['code'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['category'] }}</td>
                    <td class="text-center">{{ $row['batch'] }}</td>
                    <td class="text-right">{{ Number::format((float) $row['quantity'], null, 3, 'id') }}</td>
                    <td class="text-right @if ($row['missing_hpp']) missing @endif">
                        Rp {{ Number::format((float) $row['unit_price'], null, 3, 'id') }}
                    </td>
                    <td class="text-right">Rp {{ Number::format((float) $row['value'], null, 3, 'id') }}</td>
                    <td class="text-right">Rp {{ Number::format((float) $row['price_consument'], null, 3, 'id') }}</td>
                    <td class="text-right">Rp {{ Number::format((float) $row['price_r1'], null, 3, 'id') }}</td>
                    <td class="text-right">Rp {{ Number::format((float) $row['price_r2'], null, 3, 'id') }}</td>
                    <td class="text-center">{{ $row['expired_date'] ? $row['expired_date']->format('d/m/Y') : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="empty">Tidak ada stok gudang saat ini.</td>
                </tr>
            @endforelse
            @if (count($inventoryRows) > 0)
                <tr class="total-row">
                    <td colspan="6">Total Persediaan</td>
                    <td class="text-right">Rp {{ Number::format((float) $inventoryTotal, null, 3, 'id') }}</td>
                    <td colspan="4"></td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="section-title">Barang dalam Perjalanan</div>
    <p style="margin: 0 0 6px 0; font-size: 8px; color: #666;">Sudah dibeli, belum sampai gudang</p>
    <table class="items">
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama Produk</th>
                <th>Satuan</th>
                <th class="text-right">Qty Belum Diterima</th>
                <th class="text-right">Harga Beli</th>
                <th class="text-right">Nilai</th>
                <th class="text-center">Tanggal Beli</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($inTransitRows as $row)
                <tr>
                    <td>{{ $row['code'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['unit'] }}</td>
                    <td class="text-right">{{ Number::format((float) $row['quantity'], null, 3, 'id') }}</td>
                    <td class="text-right">Rp {{ Number::format((float) $row['net_price'], null, 3, 'id') }}</td>
                    <td class="text-right">Rp {{ Number::format((float) $row['value'], null, 3, 'id') }}</td>
                    <td class="text-center">{{ $row['purchase_date'] ? $row['purchase_date']->format('d/m/Y') : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="empty">Tidak ada barang dalam perjalanan.</td>
                </tr>
            @endforelse
            @if (count($inTransitRows) > 0)
                <tr class="total-row">
                    <td colspan="5">Total Barang dalam Perjalanan</td>
                    <td class="text-right">Rp {{ Number::format((float) $inTransitTotal, null, 3, 'id') }}</td>
                    <td></td>
                </tr>
            @endif
        </tbody>
    </table>

    <table class="grand">
        <tr>
            <td class="label">Total Persediaan Gudang</td>
            <td class="amount">Rp {{ Number::format((float) $inventoryTotal, null, 3, 'id') }}</td>
        </tr>
        <tr>
            <td class="label">Total Barang dalam Perjalanan</td>
            <td class="amount">Rp {{ Number::format((float) $inTransitTotal, null, 3, 'id') }}</td>
        </tr>
        <tr class="final">
            <td class="label">Total Aset Barang</td>
            <td class="amount">Rp {{ Number::format((float) $totalGoodsAssets, null, 3, 'id') }}</td>
        </tr>
    </table>

    <p class="note">
        Nilai persediaan dihitung dari sisa stok × harga beli (HPP), sama dengan pos Persediaan di neraca.
        Dokumen ini adalah posisi stok pada saat dicetak, bukan laporan per periode.
        @if ($incompleteCount > 0)
            <span class="warn">{{ $incompleteCount }} batch belum punya harga beli — nilai persediaan kemungkinan lebih rendah.</span>
        @endif
    </p>
</body>
</html>
