<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Keuangan — {{ $periodLabel }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            color: #191c1e;
            line-height: 1.5;
            padding: 20px 30px;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #9e4300;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 20px;
            color: #9e4300;
            margin-bottom: 2px;
        }
        .header .store-name {
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }
        .header .period {
            font-size: 12px;
            color: #666;
            margin-top: 4px;
        }
        .header .generated {
            font-size: 9px;
            color: #999;
            margin-top: 2px;
        }
        .section {
            margin-bottom: 18px;
        }
        .section-title {
            font-size: 13px;
            font-weight: 700;
            color: #9e4300;
            border-bottom: 1px solid #e0c0b1;
            padding-bottom: 4px;
            margin-bottom: 8px;
        }
        .summary-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-grid td {
            padding: 6px 10px;
            border: 1px solid #e0e3e5;
        }
        .summary-grid .label {
            background-color: #f2f4f6;
            font-weight: 600;
            width: 40%;
            color: #333;
        }
        .summary-grid .value {
            text-align: right;
            font-weight: 600;
        }
        .summary-grid .value.highlight {
            color: #9e4300;
            font-size: 13px;
        }
        .summary-grid .value.danger {
            color: #ba1a1a;
        }
        .summary-grid .value.success {
            color: #2e7d32;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        table.data-table thead th {
            background-color: #9e4300;
            color: #ffffff;
            padding: 6px 8px;
            text-align: left;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }
        table.data-table thead th.text-right {
            text-align: right;
        }
        table.data-table tbody td {
            padding: 5px 8px;
            border-bottom: 1px solid #e0e3e5;
            font-size: 10px;
        }
        table.data-table tbody td.text-right {
            text-align: right;
        }
        table.data-table tbody tr:nth-child(even) {
            background-color: #f7f9fb;
        }
        table.data-table tfoot td {
            padding: 6px 8px;
            font-weight: 700;
            border-top: 2px solid #9e4300;
            font-size: 11px;
        }
        table.data-table tfoot td.text-right {
            text-align: right;
        }
        .two-col {
            width: 100%;
        }
        .two-col td {
            width: 50%;
            vertical-align: top;
            padding-right: 10px;
        }
        .two-col td:last-child {
            padding-right: 0;
            padding-left: 10px;
        }
        .footer {
            margin-top: 24px;
            text-align: center;
            font-size: 9px;
            color: #999;
            border-top: 1px solid #e0e3e5;
            padding-top: 8px;
        }
        .badge {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: 600;
        }
        .badge-success {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        .badge-danger {
            background-color: #fce4ec;
            color: #ba1a1a;
        }
    </style>
</head>
<body>

    {{-- ====== HEADER ====== --}}
    <div class="header">
        <div class="store-name">Toko Rukun Jaya</div>
        <h1>Laporan Keuangan</h1>
        <div class="period">Periode: {{ $periodLabel }}</div>
        <div class="generated">Dicetak: {{ $generatedAt }}</div>
    </div>

    {{-- ====== RINGKASAN ====== --}}
    <div class="section">
        <div class="section-title">Ringkasan</div>
        <table class="summary-grid">
            <tr>
                <td class="label">Total Omset</td>
                <td class="value highlight">Rp {{ number_format($summary['total_omset'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="label">Jumlah Transaksi</td>
                <td class="value">{{ $summary['jumlah_transaksi'] }} transaksi</td>
            </tr>
            <tr>
                <td class="label">Total Diskon Diberikan</td>
                <td class="value">Rp {{ number_format($summary['total_diskon'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="label">Total HPP (Harga Pokok Penjualan)</td>
                <td class="value">Rp {{ number_format($summary['total_hpp'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="label">Laba Kotor</td>
                <td class="value {{ $summary['total_laba_kotor'] >= 0 ? 'success' : 'danger' }}">
                    Rp {{ number_format($summary['total_laba_kotor'], 0, ',', '.') }}
                </td>
            </tr>
            <tr>
                <td class="label">Transaksi Merugi</td>
                <td class="value">
                    @if($summary['transaksi_merugi'] == 0)
                        <span class="badge badge-success">0 — Aman</span>
                    @else
                        <span class="badge badge-danger">{{ $summary['transaksi_merugi'] }} transaksi</span>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    {{-- ====== METODE PEMBAYARAN ====== --}}
    <div class="section">
        <div class="section-title">Per Metode Pembayaran</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Metode</th>
                    <th class="text-right">Jumlah Transaksi</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Tunai</td>
                    <td class="text-right">{{ $summary['per_metode_bayar']['tunai']['count'] }}</td>
                    <td class="text-right">Rp {{ number_format($summary['per_metode_bayar']['tunai']['total'], 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>QRIS</td>
                    <td class="text-right">{{ $summary['per_metode_bayar']['qris']['count'] }}</td>
                    <td class="text-right">Rp {{ number_format($summary['per_metode_bayar']['qris']['total'], 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- ====== LABA PER KATEGORI ====== --}}
    <div class="section">
        <div class="section-title">Laba Kotor per Kategori</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Kategori</th>
                    <th class="text-right">Pendapatan</th>
                    <th class="text-right">HPP</th>
                    <th class="text-right">Laba Kotor</th>
                    <th class="text-right">Margin</th>
                </tr>
            </thead>
            <tbody>
                @foreach($labaPerKategori as $kat)
                <tr>
                    <td>{{ $kat['category_name'] }}</td>
                    <td class="text-right">Rp {{ number_format($kat['total_revenue'], 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($kat['total_hpp'], 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($kat['total_profit'], 0, ',', '.') }}</td>
                    <td class="text-right">{{ $kat['margin_pct'] }}%</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td><strong>Total</strong></td>
                    <td class="text-right">Rp {{ number_format($labaPerKategori->sum('total_revenue'), 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($labaPerKategori->sum('total_hpp'), 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($labaPerKategori->sum('total_profit'), 0, ',', '.') }}</td>
                    <td class="text-right">—</td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- ====== LABA PER PRODUK ====== --}}
    <div class="section">
        <div class="section-title">Laba Kotor per Produk</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Produk</th>
                    <th>Kategori</th>
                    <th class="text-right">Pendapatan</th>
                    <th class="text-right">HPP</th>
                    <th class="text-right">Laba Kotor</th>
                    <th class="text-right">Margin</th>
                </tr>
            </thead>
            <tbody>
                @foreach($labaPerProduk as $prod)
                <tr>
                    <td>{{ $prod['product_name'] }}</td>
                    <td>{{ $prod['category'] }}</td>
                    <td class="text-right">Rp {{ number_format($prod['total_revenue'], 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($prod['total_hpp'], 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($prod['total_profit'], 0, ',', '.') }}</td>
                    <td class="text-right">{{ $prod['margin_pct'] }}%</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2"><strong>Total</strong></td>
                    <td class="text-right">Rp {{ number_format($labaPerProduk->sum('total_revenue'), 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($labaPerProduk->sum('total_hpp'), 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($labaPerProduk->sum('total_profit'), 0, ',', '.') }}</td>
                    <td class="text-right">—</td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- ====== FOOTER ====== --}}
    <div class="footer">
        Laporan ini digenerate otomatis oleh Sistem POS Toko Rukun Jaya.<br>
        Data bersumber dari transaksi yang tercatat di sistem. Laporan bersifat operasional, bukan pembukuan akuntansi lengkap.
    </div>

</body>
</html>
