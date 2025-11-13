<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Struk Penjualan - {{ $sale->invoice_number }}</title>
    <style>
        @page {
            margin: 10mm;
            size: A4;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', 'Arial', sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            padding: 15px;
        }
        
        .header {
            margin-bottom: 20px;
            border-bottom: 3px solid #0066cc;
            padding-bottom: 10px;
        }
        
        .company-info {
            text-align: center;
            margin-bottom: 15px;
        }
        
        .company-info h1 {
            font-size: 22px;
            color: #0066cc;
            margin-bottom: 3px;
            font-weight: bold;
        }
        
        .company-info p {
            font-size: 10px;
            color: #555;
            margin: 2px 0;
        }
        
        .receipt-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin: 15px 0;
            color: #333;
            text-transform: uppercase;
        }
        
        .info-section {
            width: 100%;
            margin-bottom: 15px;
        }
        
        .info-row-container {
            display: table;
            width: 100%;
        }
        
        .info-left, .info-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 0 5px;
        }
        
        .info-box {
            padding: 10px;
            background: #f8f9fa;
            border-radius: 3px;
        }
        
        .info-box h3 {
            font-size: 11px;
            color: #0066cc;
            margin-bottom: 8px;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 4px;
            font-weight: bold;
        }
        
        .info-row {
            margin-bottom: 4px;
            font-size: 10px;
        }
        
        .info-label {
            display: inline-block;
            width: 100px;
            font-weight: 600;
            color: #555;
        }
        
        .info-value {
            display: inline;
            color: #333;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 10px;
        }
        
        table thead {
            background: #0066cc;
            color: white;
        }
        
        table th {
            padding: 8px 5px;
            text-align: left;
            border: 1px solid #0066cc;
            font-weight: bold;
            font-size: 10px;
        }
        
        table td {
            padding: 6px 5px;
            border: 1px solid #dee2e6;
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
        
        .summary-section {
            width: 100%;
            margin-top: 15px;
        }
        
        .summary-table {
            float: right;
            width: 280px;
        }
        
        .summary-table table {
            margin: 0;
        }
        
        .summary-table table td {
            border: none;
            padding: 4px 8px;
            font-size: 10px;
        }
        
        .summary-table .total-row {
            background: #0066cc;
            color: white;
            font-weight: bold;
            font-size: 12px;
        }

        .payment-info {
            clear: both;
            margin-top: 20px;
            padding: 10px;
            background: #e7f3ff;
            border-left: 3px solid #0066cc;
        }

        .payment-info h4 {
            font-size: 11px;
            margin-bottom: 6px;
            color: #004085;
            font-weight: bold;
        }

        .payment-row {
            display: table;
            width: 100%;
            margin-bottom: 3px;
            font-size: 10px;
        }

        .payment-label {
            display: table-cell;
            width: 50%;
            font-weight: 600;
            color: #555;
        }

        .payment-value {
            display: table-cell;
            width: 50%;
            text-align: right;
            color: #333;
        }
        
        .notes {
            clear: both;
            margin-top: 20px;
            padding: 10px;
            background: #fff3cd;
            border-left: 3px solid #ffc107;
        }
        
        .notes h4 {
            font-size: 11px;
            margin-bottom: 5px;
            color: #856404;
            font-weight: bold;
        }
        
        .notes p {
            font-size: 10px;
            color: #856404;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
        }
        
        .signature-section {
            display: table;
            width: 100%;
            margin-top: 30px;
        }
        
        .signature-box {
            display: table-cell;
            width: 50%;
            text-align: center;
            padding: 8px;
            font-size: 10px;
        }
        
        .signature-line {
            margin-top: 50px;
            border-top: 1px solid #333;
            padding-top: 4px;
            font-weight: bold;
            font-size: 10px;
        }

        .thank-you {
            text-align: center;
            margin-top: 20px;
            padding: 12px;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 3px;
            color: #155724;
        }

        .thank-you h3 {
            font-size: 13px;
            margin-bottom: 4px;
            font-weight: bold;
        }

        .thank-you p {
            font-size: 10px;
        }

        .print-info {
            margin-top: 20px;
            text-align: center;
            font-size: 9px;
            color: #666;
        }

        .print-info p {
            margin: 2px 0;
        }

        .disclaimer {
            margin-top: 5px;
            font-style: italic;
            color: #999;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="company-info">
            <h1>{{ $company['name'] }} Inventory</h1>
            <p>{{ $company['address'] }}</p>
            <p>Telp: {{ $company['phone'] }}</p>
        </div>
    </div>

    <!-- Receipt Title -->
    <div class="receipt-title">STRUK PENJUALAN</div>

    <!-- Info Section -->
    <div class="info-section">
        <div class="info-row-container">
            <div class="info-left">
                <div class="info-box">
                    <h3>Informasi Customer</h3>
                    <div class="info-row">
                        <span class="info-label">Nama Customer:</span>
                        <span class="info-value">{{ $sale->customer->name ?? 'Guest' }}</span>
                    </div>
                    @if($sale->customer && $sale->customer->phone)
                    <div class="info-row">
                        <span class="info-label">Telepon:</span>
                        <span class="info-value">{{ $sale->customer->phone }}</span>
                    </div>
                    @endif
                    @if($sale->customer && $sale->customer->email)
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value">{{ $sale->customer->email }}</span>
                    </div>
                    @endif
                    @if($sale->customer && $sale->customer->address)
                    <div class="info-row">
                        <span class="info-label">Alamat:</span>
                        <span class="info-value">{{ $sale->customer->address }}</span>
                    </div>
                    @endif
                </div>
            </div>
            
            <div class="info-right">
                <div class="info-box">
                    <h3>Informasi Transaksi</h3>
                    <div class="info-row">
                        <span class="info-label">No. Invoice:</span>
                        <span class="info-value">{{ $sale->invoice_number }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Tanggal:</span>
                        <span class="info-value">{{ $sale->sale_date->format('d F Y') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Waktu:</span>
                        <span class="info-value">{{ $sale->sale_date->format('H:i') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Status:</span>
                        <span class="info-value">{{ $sale->status_text }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Metode Bayar:</span>
                        <span class="info-value">{{ $sale->payment_method_text }}</span>
                    </div>
                    @if($sale->payment_method === 'credit' && $sale->customerCredit)
                    <div class="info-row">
                        <span class="info-label">Jatuh Tempo:</span>
                        <span class="info-value">{{ $sale->customerCredit->due_date->format('d F Y') }}</span>
                    </div>
                    @endif
                    <div class="info-row">
                        <span class="info-label">Kasir:</span>
                        <span class="info-value">{{ $sale->user->name ?? '-' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Items Table -->
    <table>
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">No</th>
                <th style="width: 15%;">Kode Produk</th>
                <th style="width: 30%;">Nama Produk</th>
                <th style="width: 12%;" class="text-center">Satuan</th>
                <th style="width: 10%;" class="text-center">Qty</th>
                <th style="width: 14%;" class="text-right">Harga Satuan</th>
                <th style="width: 14%;" class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->saleItems as $index => $item)
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
    <div class="summary-section">
        <div class="summary-table">
            <table>
                <tr>
                    <td>Subtotal:</td>
                    <td class="text-right">Rp {{ number_format($sale->subtotal, 0, ',', '.') }}</td>
                </tr>
                @if($sale->discount > 0)
                <tr>
                    <td>Diskon:</td>
                    <td class="text-right">- Rp {{ number_format($sale->discount, 0, ',', '.') }}</td>
                </tr>
                @endif
                @if($sale->tax > 0)
                <tr>
                    <td>Pajak (PPN 11%):</td>
                    <td class="text-right">Rp {{ number_format($sale->tax, 0, ',', '.') }}</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td>TOTAL:</td>
                    <td class="text-right">Rp {{ number_format($sale->total, 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Payment Information -->
    @if($sale->payment_method === 'cash')
    <div class="payment-info">
        <h4>Informasi Pembayaran</h4>
        <div class="payment-row">
            <span class="payment-label">Jumlah Dibayar:</span>
            <span class="payment-value">Rp {{ number_format($sale->paid_amount, 0, ',', '.') }}</span>
        </div>
        <div class="payment-row">
            <span class="payment-label">Kembalian:</span>
            <span class="payment-value">Rp {{ number_format($sale->change, 0, ',', '.') }}</span>
        </div>
    </div>
    @endif

    @if($sale->payment_method === 'credit' && $sale->customerCredit)
    <div class="payment-info">
        <h4>Informasi Kredit</h4>
        <div class="payment-row">
            <span class="payment-label">No. Kredit:</span>
            <span class="payment-value">{{ $sale->customerCredit->credit_number }}</span>
        </div>
        <div class="payment-row">
            <span class="payment-label">Total Kredit:</span>
            <span class="payment-value">Rp {{ number_format($sale->customerCredit->total_credit, 0, ',', '.') }}</span>
        </div>
        <div class="payment-row">
            <span class="payment-label">Sudah Dibayar:</span>
            <span class="payment-value">Rp {{ number_format($sale->customerCredit->paid_amount, 0, ',', '.') }}</span>
        </div>
        <div class="payment-row">
            <span class="payment-label">Sisa:</span>
            <span class="payment-value">Rp {{ number_format($sale->customerCredit->remaining_amount, 0, ',', '.') }}</span>
        </div>
        <div class="payment-row">
            <span class="payment-label">Jatuh Tempo:</span>
            <span class="payment-value">{{ $sale->customerCredit->due_date->format('d F Y') }}</span>
        </div>
    </div>
    @endif

    <!-- Notes -->
    @if($sale->notes)
    <div class="notes">
        <h4>Catatan:</h4>
        <p>{{ $sale->notes }}</p>
    </div>
    @endif

    <!-- Thank You Message -->
    <div class="thank-you">
        <h3>Terima Kasih Atas Kepercayaan Anda!</h3>
        <p>Barang yang sudah dibeli tidak dapat dikembalikan</p>
    </div>

    <!-- Footer with Signatures -->
    <div class="footer">
        <p style="font-size: 10px; color: #666; margin-bottom: 8px;">
            Struk ini merupakan bukti sah transaksi pembelian. Harap disimpan dengan baik.
        </p>
        
        <div class="signature-section">
            <div class="signature-box">
                <p>Kasir,</p>
                <div class="signature-line">{{ $sale->user->name ?? '-' }}</div>
                <p style="font-size: 9px; color: #666; margin-top: 3px;">Staff Penjualan</p>
            </div>
            
            <div class="signature-box">
                <p>Pelanggan,</p>
                <div class="signature-line">{{ $sale->customer->name ?? 'Guest' }}</div>
                <p style="font-size: 9px; color: #666; margin-top: 3px;">Penerima</p>
            </div>
        </div>
    </div>

    <div class="print-info">
        <p>Dokumen ini dicetak pada {{ now()->format('d F Y H:i') }}</p>
        <p>Invoice No: {{ $sale->invoice_number }}</p>
        <p class="disclaimer">*** Simpan struk ini sebagai bukti transaksi yang sah ***</p>
    </div>
</body>
</html> Penjualan</p>
            </div>
            
            <div class="signature-box">
                <p>Pelanggan,</p>
                <div class="signature-line">{{ $sale->customer->name ?? 'Guest' }}</div>
                <p style="font-size: 10px; color: #666;">Penerima</p>
            </div>
        </div>
    </div>

    <div style="margin-top: 30px; text-align: center; font-size: 10px; color: #666;">
        <p>Dokumen ini dicetak pada {{ now()->format('d F Y H:i') }}</p>
        <p>Invoice No: {{ $sale->invoice_number }}</p>
        <p style="margin-top: 10px; font-style: italic;">*** Simpan struk ini sebagai bukti transaksi yang sah ***</p>
    </div>
</body>
</html>