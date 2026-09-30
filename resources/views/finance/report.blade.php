<!DOCTYPE html>
<html>
<head>
    @php use Illuminate\Support\Number; @endphp
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th,
        td {
            border: 1px solid #333;
            padding: 5px;
            text-align: right;
        }
        th:first-child,
        td:first-child,
        td:nth-child(2) {
            text-align: left;
        }
        th {
            background: #eee;
        }
    </style>
</head>
<body>
    <div style="width:100%; text-align:center; margin-bottom:15px;">
        <h1 style="font-size:18px; font-weight:bold; margin:0;">
            Data Penjualan
        </h1>
        <h2 style="font-size:15px; font-weight:bold; margin:4px 0;">
            TOKO SUMBER TANI
        </h2>
        <h3 style="font-size:12px; margin:4px 0;">
            Periode: {{ $startDate }} - {{ $endDate }}
        </h3>
        @if (!empty($customerTypesLabel))
            <h4 style="font-size:11px; margin:2px 0; color:#555; font-weight:normal;">
                Tipe Pelanggan: {{ $customerTypesLabel }}
            </h4>
        @endif
    </div>
    @if ($isLandscape)
        {{-- === BLADE (LANDSCAPE ≤ 10) === --}}
        <table>
            <thead>
                <tr>
                    <th>Waktu</th>
                    @foreach ($columns as $name)
                        <th>{{ $name }}</th>
                    @endforeach
                    <th>TOTAL</th>
                </tr>
            </thead>
            <tbody>
                {{-- PENDAPATAN PER BULAN --}}
                @foreach ($pivot as $period => $rows)
                    <tr>
                        <td><b>{{ $period }}</b></td>
                        @php $rowTotal = 0; @endphp
                        @foreach ($rows as $total)
                            @php $rowTotal += $total; @endphp
                            <td>Rp {{ Number::format((float) $total, null, 3, 'id') }}</td>
                        @endforeach
                        <td><b>Rp {{ Number::format((float) $rowTotal, null, 3, 'id') }}</b></td>
                    </tr>
                @endforeach
                {{-- GARIS PEMISAH --}}
                <tr>
                    <td colspan="{{ count($columns) + 2 }}" style="background:#000;height:2px;padding:0"></td>
                </tr>
                {{-- TOTAL QTY --}}
                <tr>
                    <td><b>TOTAL QTY</b></td>
                    @foreach ($totalQty as $qty)
                        <td><b>{{ Number::format((float) $qty, null, 3, 'id') }}</b></td>
                    @endforeach
                    <td><b>{{ Number::format((float) $grandTotalQty, null, 3, 'id') }}</b></td>
                </tr>
                {{-- TOTAL PENJUALAN --}}
                <tr>
                    <td><b>TOTAL PENJUALAN</b></td>
                    @foreach ($totalSales as $total)
                        <td><b>Rp {{ Number::format((float) $total, null, 3, 'id') }}</b></td>
                    @endforeach
                    <td><b>Rp {{ Number::format((float) $grandTotalSales, null, 3, 'id') }}</b></td>
                </tr>
                {{-- REKONSILIASI KE BASIS NOTA (sama seperti halaman & laba rugi) --}}
                {{-- Baris nol disembunyikan agar tidak membingungkan. --}}
                @if ((float) $discountTotal != 0)
                <tr>
                    <td colspan="{{ count($columns) + 1 }}"><b>TOTAL DISKON</b></td>
                    <td><b>&minus;Rp {{ Number::format((float) $discountTotal, null, 3, 'id') }}</b></td>
                </tr>
                @endif
                @if ((float) $adjustmentTotal != 0)
                <tr>
                    <td colspan="{{ count($columns) + 1 }}"><b>PENYESUAIAN NOTA *</b></td>
                    <td><b>{{ (float) $adjustmentTotal < 0 ? '−' : '+' }}Rp {{ Number::format(abs((float) $adjustmentTotal), null, 3, 'id') }}</b></td>
                </tr>
                @endif
                <tr style="background: #e8f5e9; font-weight: bold;">
                    <td colspan="{{ count($columns) + 1 }}"><b>PENDAPATAN BERSIH</b></td>
                    <td><b>Rp {{ Number::format((float) $netRevenue, null, 3, 'id') }}</b></td>
                </tr>
            </tbody>
        </table>
    @else
        {{-- MODE NORMAL (> 10 kolom) dengan MERGE PERIODE --}}
        <table>
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>{{ $downloadBy === 'product' ? 'Produk' : 'Kategori' }}</th>
                    <th>Qty</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $prevPeriod = null;
                @endphp
                
                @php
                    $totalQtySum = 0;
                    $totalSalesSum = 0;
                @endphp
                
                @foreach ($data as $period => $rows)
                    @foreach ($rows as $index => $row)
                        @php
                            $totalQtySum += $row->total_qty;
                            $totalSalesSum += $row->total_sales;
                        @endphp
                        <tr>
                            @if ($index === 0)
                                {{-- Tampilkan periode dengan style tebal --}}
                                <td style="vertical-align: top;"><b>{{ $period }}</b></td>
                            @else
                                {{-- Tampilkan periode transparan untuk baris berikutnya --}}
                                <td style="color: transparent; border-top: none;">{{ $period }}</td>
                            @endif
                            <td style="text-align: left;">{{ $downloadBy === 'product' ? $row->product_name : $row->category_name }}</td>
                            <td>{{ Number::format((float) $row->total_qty, null, 3, 'id') }}</td>
                            <td>Rp {{ Number::format((float) $row->total_sales, null, 3, 'id') }}</td>
                        </tr>
                    @endforeach
                @endforeach
                
                {{-- BARIS TOTAL --}}
                <tr style="background: #f5f5f5; font-weight: bold;">
                    <td></td>
                    <td style="text-align: left;"><b>TOTAL</b></td>
                    <td><b>{{ Number::format((float) $totalQtySum, null, 3, 'id') }}</b></td>
                    <td><b>Rp {{ Number::format((float) $totalSalesSum, null, 3, 'id') }}</b></td>
                </tr>
                {{-- REKONSILIASI KE BASIS NOTA (sama seperti halaman & laba rugi) --}}
                {{-- Baris nol disembunyikan agar tidak membingungkan. --}}
                @if ((float) $discountTotal != 0)
                <tr>
                    <td></td>
                    <td style="text-align: left;"><b>TOTAL DISKON</b></td>
                    <td></td>
                    <td><b>&minus;Rp {{ Number::format((float) $discountTotal, null, 3, 'id') }}</b></td>
                </tr>
                @endif
                @if ((float) $adjustmentTotal != 0)
                <tr>
                    <td></td>
                    <td style="text-align: left;"><b>PENYESUAIAN NOTA *</b></td>
                    <td></td>
                    <td><b>{{ (float) $adjustmentTotal < 0 ? '−' : '+' }}Rp {{ Number::format(abs((float) $adjustmentTotal), null, 3, 'id') }}</b></td>
                </tr>
                @endif
                <tr style="background: #e8f5e9; font-weight: bold;">
                    <td></td>
                    <td style="text-align: left;"><b>PENDAPATAN BERSIH</b></td>
                    <td></td>
                    <td><b>Rp {{ Number::format((float) $netRevenue, null, 3, 'id') }}</b></td>
                </tr>
            </tbody>
        </table>
    @endif

    {{-- FOOTNOTE: cara membaca angka laporan ini --}}
    <div style="margin-top: 12px; font-size: 9px; color: #333;">
        <p style="margin: 0 0 4px;"><b>Cara membaca angka laporan ini</b></p>
        <p style="margin: 0 0 4px;"><b>Total Penjualan</b> adalah jumlah seluruh rincian barang (harga &times; qty) &mdash; yaitu angka kotor sebelum potongan.</p>
        @if ((float) $discountTotal != 0)
        <p style="margin: 0 0 4px;"><b>Total Diskon</b> adalah jumlah seluruh potongan harga yang diberikan kasir pada nota-nota periode ini. Diskon dicatat per nota (bukan per barang), sehingga ia ditampilkan sebagai satu baris rekap di sini, bukan dipecah ke tiap barang.</p>
        @endif
        @if ((float) $adjustmentTotal != 0)
        <p style="margin: 0 0 4px;"><b>Penyesuaian Nota (*)</b> adalah selisih antara total yang tercetak di nota dengan jumlah rincian barangnya, pada sebagian nota lama (tercatat sebelum 24 Agustus 2026). Penyebabnya: kasir saat itu dapat mengetik total bayar secara manual; bila total ketikan lebih besar dari hitungan barang, kelebihannya tersimpan di total nota tanpa tercatat sebagai diskon (sistem lama tidak menolaknya). Sejak 24 Agustus 2026 sistem menghitung ulang total dari rincian barang sehingga selisih seperti ini tidak lagi terjadi pada nota baru &mdash; angka ini hanya peninggalan data lama dan tidak memengaruhi kas maupun stok.</p>
        @endif
        <p style="margin: 0;"><b>Pendapatan Bersih = Total Penjualan
            @if ((float) $discountTotal != 0)
                &minus; Total Diskon
            @endif
            @if ((float) $adjustmentTotal != 0)
                + Penyesuaian Nota
            @endif
            .</b> Angka inilah yang sama dengan &ldquo;Penjualan Periode Ini&rdquo; dan &ldquo;Total Pendapatan&rdquo; di halaman Laporan Keuangan untuk periode dan filter yang sama.</p>
    </div>
</body>
</html>