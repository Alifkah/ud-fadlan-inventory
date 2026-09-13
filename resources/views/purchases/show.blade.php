@extends('layouts.app')

@section('title', 'Detail Pembelian')
@section('page-title', 'Detail Transaksi Pembelian')
@section('page-description', 'Detail lengkap transaksi pembelian ' . $purchase->invoice_number)

@section('content')
<div x-data="{ 
    openDeleteModal: false,
    confirming: false,
    
    confirmReceipt(purchaseId) {
        if (!confirm('Apakah Anda yakin barang sudah diterima? Stok akan otomatis bertambah.')) {
            return;
        }
        
        this.confirming = true;
        
        fetch(`/purchases/${purchaseId}/confirm-receipt`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').content,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert(data.message || 'Terjadi kesalahan');
                this.confirming = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan jaringan');
            this.confirming = false;
        });
    }
}">
    <!-- Back Button and Actions -->
    <div class="flex justify-between items-center mb-6">
        <a href="{{ route('purchases.index') }}" class="flex items-center text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
            <span class="material-icons mr-2">arrow_back</span>
            Kembali ke Daftar Pembelian
        </a>

        <div class="flex space-x-3">
            @if($purchase->status === 'pending')
                <button @click="confirmReceipt({{ $purchase->id }})" :disabled="confirming" class="flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50">
                    <span class="material-icons mr-2 text-sm">check_circle</span>
                    Konfirmasi Penerimaan
                </button>
            @endif

            @if($purchase->status === 'pending')
                <a href="{{ route('purchases.edit', $purchase) }}" class="flex items-center px-4 py-2 bg-yellow-500 text-white rounded-md hover:bg-yellow-600">
                    <span class="material-icons mr-2 text-sm">edit</span>
                    Edit
                </a>
            @endif

            @if($purchase->status === 'completed')
                <a href="{{ route('purchases.print-receipt', $purchase) }}" target="_blank" class="flex items-center px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                    <span class="material-icons mr-2 text-sm">print</span>
                    Cetak Struk
                </a>
            @endif
        </div>
    </div>

    <!-- Purchase Information -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Main Info -->
        <div class="lg:col-span-2 bg-card-light dark:bg-card-dark rounded-lg shadow-sm p-6">
            <h2 class="text-xl font-bold text-text-light dark:text-text-dark mb-4">Informasi Transaksi</h2>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Invoice Number</p>
                    <p class="text-base font-semibold text-text-light dark:text-text-dark">{{ $purchase->invoice_number }}</p>
                </div>

                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Tanggal Pembelian</p>
                    <p class="text-base font-semibold text-text-light dark:text-text-dark">
                        {{ $purchase->purchase_date->format('d M Y, H:i') }}
                    </p>
                </div>

                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Status</p>
                    <div class="mt-1">
                        @if($purchase->status === 'completed')
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                Selesai
                            </span>
                        @elseif($purchase->status === 'pending')
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                Pending
                            </span>
                        @else
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                Dibatalkan
                            </span>
                        @endif
                    </div>
                </div>

                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Metode Pembayaran</p>
                    <div class="mt-1">
                        @if($purchase->payment_method === 'cash')
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                Cash
                            </span>
                        @elseif($purchase->payment_method === 'credit')
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                Kredit
                            </span>
                        @else
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                Transfer
                            </span>
                        @endif
                    </div>
                </div>

                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Staff</p>
                    <p class="text-base font-semibold text-text-light dark:text-text-dark">
                        {{ $purchase->user->name ?? 'Unknown' }}
                    </p>
                </div>

                @if($purchase->received_at)
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Diterima Pada</p>
                    <p class="text-base font-semibold text-text-light dark:text-text-dark">
                        {{ $purchase->received_at->format('d M Y, H:i') }}
                    </p>
                </div>
                @endif
            </div>

            @if($purchase->notes)
                <div class="mt-4 pt-4 border-t border-border-light dark:border-border-dark">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Catatan</p>
                    <p class="text-base text-text-light dark:text-text-dark mt-1">{{ $purchase->notes }}</p>
                </div>
            @endif
        </div>

        <!-- Supplier Info -->
        <div class="bg-card-light dark:bg-card-dark rounded-lg shadow-sm p-6">
            <h2 class="text-xl font-bold text-text-light dark:text-text-dark mb-4">Informasi Supplier</h2>
            
            @if($purchase->supplier)
                <div class="space-y-3">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Nama Supplier</p>
                        <p class="text-base font-semibold text-text-light dark:text-text-dark">
                            {{ $purchase->supplier->name }}
                        </p>
                    </div>

                    @if($purchase->supplier->phone)
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Telepon</p>
                        <p class="text-base text-text-light dark:text-text-dark">
                            {{ $purchase->supplier->phone }}
                        </p>
                    </div>
                    @endif

                    @if($purchase->supplier->email)
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Email</p>
                        <p class="text-base text-text-light dark:text-text-dark">
                            {{ $purchase->supplier->email }}
                        </p>
                    </div>
                    @endif

                    @if($purchase->supplier->address)
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Alamat</p>
                        <p class="text-base text-text-light dark:text-text-dark">
                            {{ $purchase->supplier->address }}
                        </p>
                    </div>
                    @endif
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400">Tidak ada informasi supplier</p>
            @endif
        </div>
    </div>

    <!-- Credit Information (if applicable) -->
    @if($purchase->supplierCredit)
    <div class="bg-card-light dark:bg-card-dark rounded-lg shadow-sm p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-text-light dark:text-text-dark">Informasi Kredit</h2>
            <a href="{{ route('supplier-credits.show', $purchase->supplierCredit) }}" class="text-primary hover:underline text-sm">
                Lihat Detail Kredit →
            </a>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">No. Kredit</p>
                <p class="text-base font-semibold text-text-light dark:text-text-dark">
                    {{ $purchase->supplierCredit->credit_number }}
                </p>
            </div>

            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Kredit</p>
                <p class="text-base font-semibold text-text-light dark:text-text-dark">
                    Rp {{ number_format($purchase->supplierCredit->total_credit, 0, ',', '.') }}
                </p>
            </div>

            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Sudah Dibayar</p>
                <p class="text-base font-semibold text-green-600">
                    Rp {{ number_format($purchase->supplierCredit->paid_amount, 0, ',', '.') }}
                </p>
            </div>

            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Sisa</p>
                <p class="text-base font-semibold text-danger">
                    Rp {{ number_format($purchase->supplierCredit->remaining_amount, 0, ',', '.') }}
                </p>
            </div>

            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Jatuh Tempo</p>
                <p class="text-base font-semibold text-text-light dark:text-text-dark">
                    {{ $purchase->supplierCredit->due_date->format('d M Y') }}
                </p>
            </div>

            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Status</p>
                <div class="mt-1">
                    @if($purchase->supplierCredit->status === 'active')
                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                            Aktif
                        </span>
                    @else
                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                            Lunas
                        </span>
                    @endif
                </div>
            </div>

            <div class="col-span-2">
                <p class="text-sm text-gray-500 dark:text-gray-400">Progress Pembayaran</p>
                <div class="mt-2">
                    <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                        <div class="bg-primary h-2.5 rounded-full" style="width: {{ $purchase->supplierCredit->payment_progress }}%"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">{{ number_format($purchase->supplierCredit->payment_progress, 1) }}%</p>
                </div>
            </div>
        </div>

        <!-- Payment History -->
        @if($purchase->supplierCredit->payments->count() > 0)
        <div class="mt-6 pt-6 border-t border-border-light dark:border-border-dark">
            <h3 class="text-lg font-semibold text-text-light dark:text-text-dark mb-3">Riwayat Pembayaran</h3>
            <div class="space-y-2">
                @foreach($purchase->supplierCredit->payments as $payment)
                    <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-800 rounded">
                        <div>
                            <p class="text-sm font-medium text-text-light dark:text-text-dark">
                                {{ $payment->payment_date->format('d M Y') }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $payment->payment_method }} • {{ $payment->user->name ?? 'Unknown' }}
                            </p>
                        </div>
                        <p class="text-sm font-semibold text-green-600">
                            Rp {{ number_format($payment->payment_amount, 0, ',', '.') }}
                        </p>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    @endif

    <!-- Purchase Items -->
    <div class="bg-card-light dark:bg-card-dark rounded-lg shadow-sm overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-border-light dark:border-border-dark">
            <h2 class="text-xl font-bold text-text-light dark:text-text-dark">Item Pembelian</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-border-light dark:divide-border-dark">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Produk
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Qty
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Harga Satuan
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Total
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-card-light dark:bg-card-dark divide-y divide-border-light dark:divide-border-dark">
                    @foreach($purchase->purchaseItems as $item)
                        <tr>
                            <td class="px-6 py-4">
                                <div>
                                    <p class="text-sm font-medium text-text-light dark:text-text-dark">
                                        {{ $item->product->name ?? 'Produk Dihapus' }}
                                    </p>
                                    @if($item->product)
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $item->product->code }} • {{ $item->product->category->name ?? 'N/A' }}
                                        </p>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center text-sm text-text-light dark:text-text-dark">
                                {{ number_format($item->quantity) }} {{ $item->product->unit ?? 'pcs' }}
                            </td>
                            <td class="px-6 py-4 text-right text-sm text-text-light dark:text-text-dark">
                                Rp {{ number_format($item->unit_price, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-medium text-text-light dark:text-text-dark">
                                Rp {{ number_format($item->total_price, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Summary -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2"></div>
        
        <div class="bg-card-light dark:bg-card-dark rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-bold text-text-light dark:text-text-dark mb-4">Ringkasan</h2>
            
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Subtotal</span>
                    <span class="text-text-light dark:text-text-dark font-medium">
                        Rp {{ number_format($purchase->subtotal, 0, ',', '.') }}
                    </span>
                </div>

                @if($purchase->discount > 0)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Diskon</span>
                    <span class="text-green-600 font-medium">
                        - Rp {{ number_format($purchase->discount, 0, ',', '.') }}
                    </span>
                </div>
                @endif

                @if($purchase->tax > 0)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Ongkos Pengiriman</span>
                    <span class="text-text-light dark:text-text-dark font-medium">
                        Rp {{ number_format($purchase->tax, 0, ',', '.') }}
                    </span>
                </div>
                @endif

                <div class="pt-2 border-t border-border-light dark:border-border-dark">
                    <div class="flex justify-between">
                        <span class="text-base font-bold text-text-light dark:text-text-dark">Total</span>
                        <span class="text-xl font-bold text-primary">
                            Rp {{ number_format($purchase->total, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection