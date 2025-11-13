<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Purchase Order - {{ $purchase->invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            padding: 20px;
        }
        
        .header {
            margin-bottom: 30px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 15px;
        }
        
        .company-info {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .company-info h1 {
            font-size: 24px;
            color: #007bff;
            margin-bottom: 5px;
        }
        
        .company-info p {
            font-size: 11px;
            color: #666;
        }
        
        .po-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin: 20px 0;
            color: #333;
        }
        
        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        
        .info-left, .info-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        
        .info-box {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            margin-right: 10px;
        }
        
        .info-right .info-box {
            margin-right: 0;
            margin-left: 10px;
        }
        
        .info-box h3 {
            font-size: 12px;
            color: #007bff;
            margin-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 5px;
        }
        
        .info-row {
            margin-bottom: 5px;
        }
        
        .info-label {
            display: inline-block;
            width: 120px;
            font-weight: bold;
            color: #666;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        table thead {
            background: #007bff;
            color: white;
        }
        
        table th, table td {
            padding: 10px;
            text-align: left;
            border: 1px solid #dee2e6;
        }
        
        table th {
            font-weight: bold;
            font-size: 11px;
        }
        
        table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .summary {
            float: right;
            width: 300px;
            margin-top: 20px;
        }
        
        .summary table {
            margin-bottom: 0;
        }
        
        .summary table td {
            border: none;
            padding: 5px 10px;
        }
        
        .summary .total-row {
            background: #007bff;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }
        
        .notes {
            clear: both;
            margin-top: 30px;
            padding: 15px;
            background: #fff3cd;
            border-left: 4px solid #ffc107;
        }
        
        .notes h4 {
            font-size: 12px;
            margin-bottom: 5px;
            color: #856404;
        }
        
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
        }
        
        .signature-section {
            display: table;
            width: 100%;
            margin-top: 50px;
        }
        
        .signature-box {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            padding: 10px;
        }
        
        .signature-line {
            margin-top: 60px;
            border-top: 1px solid #333;
            padding-top: 5px;
            font-weight: bold;
        }
        
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="company-info">
            <h1>{{ $company['name'] }}</h1>
            <p>{{ $company['address'] }}</p>
            <p>Telp: {{ $company['phone'] }}</p>
        </div>
    </div>

    <!-- PO Title -->
    <div class="po-title">PURCHASE ORDER</div>

    <!-- Info Section -->
    <div class="info-section">
        <div class="info-left">
            <div class="info-box">
                <h3>Informasi Supplier</h3>
                <div class="info-row">
                    <span class="info-label">Nama Supplier:</span>
                    <span>{{ $purchase->supplier->name }}</span>
                </div>
                @if($purchase->supplier->company_name)
                <div class="info-row">
                    <span class="info-label">Perusahaan:</span>
                    <span>{{ $purchase->supplier->company_name }}</span>
                </div>
                @endif
                <div class="info-row">
                    <span class="info-label">Telepon:</span>
                    <span>{{ $purchase->supplier->phone }}</span>
                </div>
                @if($purchase->supplier->email)
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span>{{ $purchase->supplier->email }}</span>
                </div>
                @endif
                @if($purchase->supplier->address)
                <div class="info-row">
                    <span class="info-label">Alamat:</span>
                    <span>{{ $purchase->supplier->address }}</span>
                </div>
                @endif
            </div>
        </div>
        
        <div class="info-right">
            <div class="info-box">
                <h3>Informasi Pesanan</h3>
                <div class="info-row">
                    <span class="info-label">No. PO:</span>
                    <span>{{ $purchase->invoice_number }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Tanggal:</span>
                    <span>{{ $purchase->purchase_date->format('d F Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span>{{ $purchase->status_text }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Metode Bayar:</span>
                    <span>{{ $purchase->payment_method_text }}</span>
                </div>
                @if($purchase->payment_method === 'credit' && $purchase->supplierCredit)
                <div class="info-row">
                    <span class="info-label">Jatuh Tempo:</span>
                    <span>{{ $purchase->supplierCredit->due_date->format('d F Y') }}</span>
                </div>
                @endif
                <div class="info-row">
                    <span class="info-label">Dibuat oleh:</span>
                    <span>{{ $purchase->user->name ?? '-' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Items Table -->
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 15%;">Kode Produk</th>
                <th style="width: 30%;">Nama Produk</th>
                <th style="width: 10%;" class="text-center">Satuan</th>
                <th style="width: 10%;" class="text-center">Qty</th>
                <th style="width: 15%;" class="text-right">Harga Satuan</th>
                <th style="width: 15%;" class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchase->purchaseItems as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $item->product->code ?? '-' }}</td>
                <td>{{ $item->product->name ?? $item->product_name ?? 'Unknown' }}</td>
                <td class="text-center">{{ $item->product->unit ?? 'pcs' }}</td>
                <td class="text-center">{{ number_format($item->quantity, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($item->total_price, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Summary -->
    <div class="summary">
        <table>
            <tr>
                <td>Subtotal:</td>
                <td class="text-right">Rp {{ number_format($purchase->subtotal, 0, ',', '.') }}</td>
            </tr>
            @if($purchase->discount > 0)
            <tr>
                <td>Diskon:</td>
                <td class="text-right">- Rp {{ number_format($purchase->discount, 0, ',', '.') }}</td>
            </tr>
            @endif
            @if($purchase->tax > 0)
            <tr>
                <td>Pajak (PPN 11%):</td>
                <td class="text-right">Rp {{ number_format($purchase->tax, 0, ',', '.') }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td>TOTAL:</td>
                <td class="text-right">Rp {{ number_format($purchase->total, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <!-- Notes -->
    @if($purchase->notes)
    <div class="notes">
        <h4>Catatan:</h4>
        <p>{{ $purchase->notes }}</p>
    </div>
    @endif

    <!-- Footer with Signatures -->
    <div class="footer">
        <p style="font-size: 11px; color: #666; margin-bottom: 10px;">
            Mohon untuk mengkonfirmasi penerimaan purchase order ini dan mengirimkan barang sesuai dengan spesifikasi yang tertera.
        </p>
        
        <div class="signature-section">
            <div class="signature-box">
                <p>Dibuat oleh,</p>
                <div class="signature-line">{{ $purchase->user->name ?? '-' }}</div>
                <p style="font-size: 10px; color: #666;">Staff Pembelian</p>
            </div>
            
            <div class="signature-box">
                <p>Disetujui oleh,</p>
                <div class="signature-line">___________________</div>
                <p style="font-size: 10px; color: #666;">Manager</p>
            </div>
            
            <div class="signature-box">
                <p>Diterima oleh,</p>
                <div class="signature-line">___________________</div>
                <p style="font-size: 10px; color: #666;">{{ $purchase->supplier->name }}</p>
            </div>
        </div>
    </div>

    <div style="margin-top: 30px; text-align: center; font-size: 10px; color: #666;">
        <p>Dokumen ini dicetak pada {{ now()->format('d F Y H:i') }}</p>
        <p>Purchase Order No: {{ $purchase->invoice_number }}</p>
    </div>
</body>
</html>