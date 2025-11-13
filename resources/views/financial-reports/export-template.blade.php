<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan - {{ $period['label'] }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #007bff;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .section {
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #007bff;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-card {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
        }
        .stat-label {
            font-size: 11px;
            color: #666;
            margin-bottom: 5px;
        }
        .stat-value {
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }
        .stat-growth {
            font-size: 10px;
            margin-top: 5px;
        }
        .stat-growth.positive { color: #28a745; }
        .stat-growth.negative { color: #dc3545; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th {
            background-color: #007bff;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }
        table td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        table tr:hover {
            background-color: #f5f5f5;
        }
        .rank {
            display: inline-block;
            width: 25px;
            height: 25px;
            line-height: 25px;
            text-align: center;
            border-radius: 50%;
            font-weight: bold;
        }
        .rank-gold { background-color: #ffd700; color: #333; }
        .rank-silver { background-color: #c0c0c0; color: #333; }
        .rank-bronze { background-color: #cd7f32; color: white; }
        .rank-other { background-color: #e9ecef; color: #666; }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 10px;
        }
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN KEUANGAN</h1>
        <p><strong>UD Fadlan - Toko Bangunan</strong></p>
        <p>Periode: {{ $period['label'] }}</p>
        <p style="font-size: 10px;">Dicetak: {{ $generated_at->format('d F Y H:i:s') }} | Oleh: {{ $generated_by }}</p>
    </div>

    <!-- Ringkasan Statistik -->
    <div class="section">
        <div class="section-title">Ringkasan Keuangan</div>
        <div class="stats-grid">
            @foreach(['revenue', 'expenditure', 'net_profit', 'total_capital'] as $key)
                <div class="stat-card">
                    <div class="stat-label">{{ $main_stats[$key]['label'] }}</div>
                    <div class="stat-value">Rp {{ number_format($main_stats[$key]['amount'], 0, ',', '.') }}</div>
                    @if($main_stats[$key]['growth_label'])
                        <div class="stat-growth {{ $main_stats[$key]['growth'] >= 0 ? 'positive' : 'negative' }}">
                            {{ $main_stats[$key]['growth'] >= 0 ? '▲' : '▼' }} {{ $main_stats[$key]['growth_label'] }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <!-- Produk Terlaris -->
    <div class="section">
        <div class="section-title">Produk Terlaris</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 50px; text-align: center;">Rank</th>
                    <th>Produk</th>
                    <th>Kode</th>
                    <th style="text-align: right;">Qty Terjual</th>
                    <th style="text-align: right;">Harga Rata-rata</th>
                    <th style="text-align: right;">Total Penjualan</th>
                    <th style="text-align: center;">%</th>
                </tr>
            </thead>
            <tbody>
                @foreach($top_products as $product)
                    <tr>
                        <td style="text-align: center;">
                            <span class="rank {{ $product['rank'] == 1 ? 'rank-gold' : ($product['rank'] == 2 ? 'rank-silver' : ($product['rank'] == 3 ? 'rank-bronze' : 'rank-other')) }}">
                                {{ $product['rank'] }}
                            </span>
                        </td>
                        <td><strong>{{ $product['product_name'] }}</strong></td>
                        <td>{{ $product['product_code'] }}</td>
                        <td style="text-align: right;">{{ number_format($product['total_quantity']) }} {{ $product['unit'] }}</td>
                        <td style="text-align: right;">Rp {{ number_format($product['avg_price'], 0, ',', '.') }}</td>
                        <td style="text-align: right;"><strong>Rp {{ number_format($product['total_sales'], 0, ',', '.') }}</strong></td>
                        <td style="text-align: center;">{{ $product['percentage'] }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Detail Penjualan -->
    @if(isset($sales_details) && $sales_details->count() > 0)
        <div class="section" style="page-break-before: always;">
            <div class="section-title">Detail Transaksi Penjualan</div>
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>No. Invoice</th>
                        <th>Customer</th>
                        <th style="text-align: right;">Jumlah Item</th>
                        <th style="text-align: right;">Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sales_details as $sale)
                        <tr>
                            <td>{{ $sale->sale_date->format('d/m/Y') }}</td>
                            <td>{{ $sale->invoice_number }}</td>
                            <td>{{ $sale->customer?->name ?? 'Guest' }}</td>
                            <td style="text-align: right;">{{ $sale->saleItems->sum('quantity') }}</td>
                            <td style="text-align: right;">Rp {{ number_format($sale->total, 0, ',', '.') }}</td>
                            <td>{{ $sale->status_text }}</td>
                        </tr>
                    @endforeach
                    <tr style="background-color: #f8f9fa; font-weight: bold;">
                        <td colspan="4" style="text-align: right;">TOTAL PENJUALAN:</td>
                        <td style="text-align: right;">Rp {{ number_format($sales_details->sum('total'), 0, ',', '.') }}</td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endif

    <!-- Detail Pembelian -->
    @if(isset($purchase_details) && $purchase_details->count() > 0)
        <div class="section" style="page-break-before: always;">
            <div class="section-title">Detail Transaksi Pembelian</div>
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>No. Invoice</th>
                        <th>Supplier</th>
                        <th style="text-align: right;">Jumlah Item</th>
                        <th style="text-align: right;">Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchase_details as $purchase)
                        <tr>
                            <td>{{ $purchase->purchase_date->format('d/m/Y') }}</td>
                            <td>{{ $purchase->invoice_number }}</td>
                            <td>{{ $purchase->supplier?->name ?? '-' }}</td>
                            <td style="text-align: right;">{{ $purchase->purchaseItems->sum('quantity') }}</td>
                            <td style="text-align: right;">Rp {{ number_format($purchase->total, 0, ',', '.') }}</td>
                            <td>{{ $purchase->status_text }}</td>
                        </tr>
                    @endforeach
                    <tr style="background-color: #f8f9fa; font-weight: bold;">
                        <td colspan="4" style="text-align: right;">TOTAL PEMBELIAN:</td>
                        <td style="text-align: right;">Rp {{ number_format($purchase_details->sum('total'), 0, ',', '.') }}</td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endif

    <div class="footer">
        <p>Dokumen ini digenerate secara otomatis oleh Sistem Manajemen UD Fadlan</p>
        <p>© {{ date('Y') }} UD Fadlan. All rights reserved.</p>
    </div>
</body>
</html>