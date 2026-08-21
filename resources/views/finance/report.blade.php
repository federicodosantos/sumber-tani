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
            </tbody>
        </table>
    @endif
</body>
</html>