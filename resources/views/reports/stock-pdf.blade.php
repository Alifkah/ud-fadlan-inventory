<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #333;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #007bff;
        }
        
        .header h1 {
            font-size: 18px;
            color: #007bff;
            margin-bottom: 5px;
        }
        
        .header .subtitle {
            font-size: 11px;
            color: #666;
        }
        
        .info-section {
            margin-bottom: 15px;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
        }
        
        .info-section table {
            width: 100%;
        }
        
        .info-section td {
            padding: 3px 5px;
            font-size: 9px;
        }
        
        .info-section td:first-child {
            font-weight: bold;
            width: 120px;
        }
        
        .summary-cards {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        
        .summary-card {
            display: table-cell;
            width: 25%;
            padding: 10px;
            text-align: center;
            border: 1px solid #dee2e6;
            background: #f8f9fa;
        }
        
        .summary-card .label {
            font-size: 9px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .summary-card .value {
            font-size: 16px;
            font-weight: bold;
            color: #007bff;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        .data-table th {
            background: #007bff;
            color: white;
            padding: 8px 5px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
        }
        
        .data-table td {
            padding: 6px 5px;
            border-bottom: 1px solid #dee2e6;
            font-size: 9px;
        }
        
        .data-table tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 8px;
            font-weight: bold;
        }
        
        .status-tersedia {
            background: #d4edda;
            color: #155724;
        }
        
        .status-menipis {
            background: #cfe2ff;
            color: #084298;
        }
        
        .status-kritis {
            background: #fff3cd;
            color: #664d03;
        }
        
        .status-habis {
            background: #f8d7da;
            color: #721c24;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 8px;
            color: #666;
            padding: 10px 0;
            border-top: 1px solid #dee2e6;
        }
        
        .page-number:after {
            content: "Halaman " counter(page);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>{{ $title }}</h1>
        <div class="subtitle">UD Fadlan - Sistem Manajemen Inventori</div>
    </div>
    
    <!-- Info Section -->
    <div class="info-section">
        <table>
            <tr>
                <td>Tanggal Dibuat:</td>
                <td>{{ $generated_at->format('d F Y H:i:s') }}</td>
                <td>Dibuat Oleh:</td>
                <td>{{ $generated_by }}</td>
            </tr>
        </table>
    </div>
    
    <!-- Summary Cards -->
    <div class="summary-cards">
        <div class="summary-card">
            <div class="label">{{ $summary['total_products']['label'] }}</div>
            <div class="value">{{ number_format($summary['total_products']['value']) }}</div>
        </div>
        <div class="summary-card">
            <div class="label">{{ $summary['stock_sold']['label'] }}</div>
            <div class="value">{{ number_format($summary['stock_sold']['value']) }}</div>
        </div>
        <div class="summary-card">
            <div class="label">{{ $summary['remaining_stock']['label'] }}</div>
            <div class="value">{{ number_format($summary['remaining_stock']['value']) }}</div>
        </div>
        <div class="summary-card">
            <div class="label">{{ $summary['low_stock']['label'] }}</div>
            <div class="value">{{ number_format($summary['low_stock']['value']) }}</div>
        </div>
    </div>
    
    <!-- Data Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th width="8%">Kode</th>
                <th width="20%">Nama Produk</th>
                <th width="12%">Kategori</th>
                <th width="9%" class="text-center">Stok Saat Ini</th>
                <th width="9%" class="text-center">Stok Min</th>
                <th width="11%" class="text-right">Harga Beli</th>
                <th width="11%" class="text-right">Harga Jual</th>
                <th width="10%" class="text-center">Status</th>
                <th width="10%">Lokasi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $item)
                <tr>
                    <td>{{ $item['Kode Produk'] }}</td>
                    <td>{{ $item['Nama Produk'] }}</td>
                    <td>{{ $item['Kategori'] }}</td>
                    <td class="text-center">{{ number_format($item['Stok Saat Ini']) }}</td>
                    <td class="text-center">{{ number_format($item['Stok Minimum']) }}</td>
                    <td class="text-right">Rp {{ number_format($item['Harga Beli'], 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item['Harga Jual'], 0, ',', '.') }}</td>
                    <td class="text-center">
                        @php
                            $statusClass = match($item['Status']) {
                                'Normal' => 'status-tersedia',
                                'Menipis' => 'status-menipis',
                                'Kritis' => 'status-kritis',
                                'Habis' => 'status-habis',
                                default => 'status-tersedia'
                            };
                        @endphp
                        <span class="status-badge {{ $statusClass }}">{{ $item['Status'] }}</span>
                    </td>
                    <td>{{ $item['Lokasi Barang'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    
    <!-- Footer -->
    <div class="footer">
        <div class="page-number"></div>
        <div>Dicetak pada {{ now()->format('d F Y H:i:s') }} | © UD Fadlan {{ now()->year }}</div>
    </div>
</body>
</html>