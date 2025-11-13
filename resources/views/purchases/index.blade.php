@extends('layouts.app')

@section('title', 'Daftar Pembelian')
@section('page-title', 'Daftar Transaksi Pembelian')
@section('page-description', 'Lihat dan kelola semua transaksi pembelian dari supplier.')

@section('content')
<div x-data="{ 
    openDeleteModal: false, 
    openViewModal: false, 
    openPrintModal: false,
    openConfirmModal: false,
    filtersOpen: false,
    selectedPurchase: null,
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
    },
    
    deletePurchase(purchaseId) {
        if (!confirm('Apakah Anda yakin ingin menghapus transaksi ini?')) {
            return;
        }
        
        fetch(`/purchases/${purchaseId}`, {
            method: 'DELETE',
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
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan jaringan');
        });
    }
}">
    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Pembelian</p>
                    <p class="text-2xl font-bold text-text-light dark:text-text-dark">
                        Rp {{ number_format($stats['total_purchases'] ?? 0, 0, ',', '.') }}
                    </p>
                </div>
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                    <span class="material-icons text-blue-600 dark:text-blue-300">shopping_cart</span>
                </div>
            </div>
        </div>

        <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Transaksi</p>
                    <p class="text-2xl font-bold text-text-light dark:text-text-dark">
                        {{ number_format($stats['total_transactions'] ?? 0) }}
                    </p>
                </div>
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                    <span class="material-icons text-green-600 dark:text-green-300">receipt_long</span>
                </div>
            </div>
        </div>

        <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Transaksi Pending</p>
                    <p class="text-2xl font-bold text-text-light dark:text-text-dark">
                        {{ number_format($stats['pending_transactions'] ?? 0) }}
                    </p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900 rounded-full flex items-center justify-center">
                    <span class="material-icons text-yellow-600 dark:text-yellow-300">hourglass_empty</span>
                </div>
            </div>
        </div>

        <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Kredit Aktif</p>
                    <p class="text-2xl font-bold text-danger">
                        Rp {{ number_format($stats['total_credit_amount'] ?? 0, 0, ',', '.') }}
                    </p>
                </div>
                <div class="w-12 h-12 bg-red-100 dark:bg-red-900 rounded-full flex items-center justify-center">
                    <span class="material-icons text-red-600 dark:text-red-300">credit_card</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex justify-between items-center mb-6">
        <div class="flex items-center space-x-4">
            <a href="{{ route('purchases.create') }}" class="flex items-center justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                <span class="material-icons mr-2">add</span> 
                Buat Pembelian Baru
            </a>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow-sm mb-6">
        <div class="flex flex-col md:flex-row justify-between md:items-center space-y-4 md:space-y-0">
            <!-- Search -->
            <div class="flex-1 max-w-md">
                <form method="GET" action="{{ route('purchases.index') }}">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="material-icons text-gray-400">search</span>
                        </div>
                        <input 
                            type="search" 
                            name="search"
                            value="{{ request('search') }}"
                            class="block w-full pl-10 pr-3 py-2 border border-border-light dark:border-border-dark rounded-md leading-5 bg-background-light dark:bg-background-dark text-text-light dark:text-text-dark placeholder-gray-400 focus:outline-none focus:placeholder-gray-500 focus:ring-1 focus:ring-primary focus:border-primary sm:text-sm" 
                            placeholder="Cari berdasarkan invoice, supplier..."
                        />
                    </div>
                    <input type="hidden" name="status" value="{{ request('status') }}">
                    <input type="hidden" name="payment_method" value="{{ request('payment_method') }}">
                    <input type="hidden" name="supplier_id" value="{{ request('supplier_id') }}">
                    <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                    <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                </form>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center space-x-4">
                <button @click="filtersOpen = !filtersOpen" class="flex items-center px-4 py-2 border border-border-light dark:border-border-dark rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                    <span class="material-icons mr-2 text-base">filter_list</span>
                    Filter
                    <span :class="{'rotate-180': filtersOpen}" class="material-icons ml-1 text-base transform transition-transform duration-200">expand_more</span>
                </button>

                <!-- Sort Dropdown -->
                <div class="relative" x-data="{ sortOpen: false }" @click.outside="sortOpen = false">
                    <button @click="sortOpen = !sortOpen" class="flex items-center px-4 py-2 border border-border-light dark:border-border-dark rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <span class="material-icons mr-2 text-base">sort</span>
                        Urutkan
                        <span class="material-icons ml-1 text-base">expand_more</span>
                    </button>
                    
                    <div x-show="sortOpen" x-transition class="absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-card-light dark:bg-card-dark ring-1 ring-black ring-opacity-5 z-10">
                        <div class="py-1" role="menu">
                            <a href="{{ route('purchases.index', array_merge(request()->except('sort'), ['sort' => 'date_desc'])) }}" class="flex items-center px-4 py-2 text-sm text-text-light dark:text-text-dark hover:bg-gray-100 dark:hover:bg-gray-700">
                                <span class="material-icons mr-3 text-base">arrow_upward</span> Tanggal Terbaru
                            </a>
                            <a href="{{ route('purchases.index', array_merge(request()->except('sort'), ['sort' => 'date_asc'])) }}" class="flex items-center px-4 py-2 text-sm text-text-light dark:text-text-dark hover:bg-gray-100 dark:hover:bg-gray-700">
                                <span class="material-icons mr-3 text-base">arrow_downward</span> Tanggal Terlama
                            </a>
                            <a href="{{ route('purchases.index', array_merge(request()->except('sort'), ['sort' => 'total_desc'])) }}" class="flex items-center px-4 py-2 text-sm text-text-light dark:text-text-dark hover:bg-gray-100 dark:hover:bg-gray-700">
                                <span class="material-icons mr-3 text-base">arrow_upward</span> Total Terbesar
                            </a>
                            <a href="{{ route('purchases.index', array_merge(request()->except('sort'), ['sort' => 'total_asc'])) }}" class="flex items-center px-4 py-2 text-sm text-text-light dark:text-text-dark hover:bg-gray-100 dark:hover:bg-gray-700">
                                <span class="material-icons mr-3 text-base">arrow_downward</span> Total Terkecil
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Export Dropdown -->
                <div class="relative" x-data="{ exportOpen: false }" @click.outside="exportOpen = false">
                    <button @click="exportOpen = !exportOpen" class="flex items-center px-4 py-2 border border-border-light dark:border-border-dark rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <span class="material-icons mr-2 text-base">download</span>
                        Ekspor
                        <span class="material-icons ml-1 text-base">expand_more</span>
                    </button>
                    
                    <div x-show="exportOpen" x-transition class="absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-card-light dark:bg-card-dark ring-1 ring-black ring-opacity-5 z-10">
                        <div class="py-1" role="menu">
                            <a href="{{ route('purchases.export', array_merge(request()->all(), ['format' => 'excel'])) }}" class="flex items-center px-4 py-2 text-sm text-text-light dark:text-text-dark hover:bg-gray-100 dark:hover:bg-gray-700">
                                <span class="material-icons mr-3 text-base">grid_on</span> Ekspor ke Excel
                            </a>
                            <a href="{{ route('purchases.export', array_merge(request()->all(), ['format' => 'pdf'])) }}" class="flex items-center px-4 py-2 text-sm text-text-light dark:text-text-dark hover:bg-gray-100 dark:hover:bg-gray-700">
                                <span class="material-icons mr-3 text-base">description</span> Ekspor ke PDF
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Advanced Filters -->
        <div x-show="filtersOpen" x-transition class="border-t border-border-light dark:border-border-dark mt-4 pt-4">
            <form method="GET" action="{{ route('purchases.index') }}">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                        <select name="status" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-border-light dark:border-border-dark focus:outline-none focus:ring-primary focus:border-primary sm:text-sm rounded-md bg-background-light dark:bg-background-dark">
                            <option value="">Semua Status</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Selesai</option>
                            <option value="canceled" {{ request('status') === 'canceled' ? 'selected' : '' }}>Dibatalkan</option>
                        </select>
                    </div>

                    <div>
                        <label for="payment_method" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Metode Pembayaran</label>
                        <select name="payment_method" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-border-light dark:border-border-dark focus:outline-none focus:ring-primary focus:border-primary sm:text-sm rounded-md bg-background-light dark:bg-background-dark">
                            <option value="">Semua Metode</option>
                            <option value="cash" {{ request('payment_method') === 'cash' ? 'selected' : '' }}>Cash</option>
                            <option value="credit" {{ request('payment_method') === 'credit' ? 'selected' : '' }}>Kredit</option>
                            <option value="transfer" {{ request('payment_method') === 'transfer' ? 'selected' : '' }}>Transfer</option>
                        </select>
                    </div>

                    <div>
                        <label for="supplier_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Supplier</label>
                        <select name="supplier_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-border-light dark:border-border-dark focus:outline-none focus:ring-primary focus:border-primary sm:text-sm rounded-md bg-background-light dark:bg-background-dark">
                            <option value="">Semua Supplier</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="credit_status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status Kredit</label>
                        <select name="credit_status" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-border-light dark:border-border-dark focus:outline-none focus:ring-primary focus:border-primary sm:text-sm rounded-md bg-background-light dark:bg-background-dark">
                            <option value="">Semua</option>
                            <option value="active" {{ request('credit_status') === 'active' ? 'selected' : '' }}>Kredit Aktif</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label for="date_from" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Mulai</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="mt-1 block w-full py-2 px-3 border border-border-light dark:border-border-dark rounded-md leading-5 bg-background-light dark:bg-background-dark text-text-light dark:text-text-dark focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
                    </div>

                    <div>
                        <label for="date_to" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Akhir</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="mt-1 block w-full py-2 px-3 border border-border-light dark:border-border-dark rounded-md leading-5 bg-background-light dark:bg-background-dark text-text-light dark:text-text-dark focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
                    </div>
                </div>

                <div class="mt-4 flex justify-end space-x-2">
                    <a href="{{ route('purchases.index') }}" class="px-4 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500">
                        Reset
                    </a>
                    <button type="submit" class="px-4 py-2 rounded-md text-sm font-medium text-white bg-primary hover:bg-green-600">
                        Terapkan Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Purchases Table -->
    <div class="bg-card-light dark:bg-card-dark rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-border-light dark:divide-border-dark">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Invoice
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Tanggal
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Supplier
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Total
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Pembayaran
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Staff
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-card-light dark:bg-card-dark divide-y divide-border-light dark:divide-border-dark">
                    @forelse($purchases as $purchase)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <a href="{{ route('purchases.show', $purchase) }}" class="text-sm font-medium text-primary hover:underline">
                                        {{ $purchase->invoice_number }}
                                    </a>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        ID: {{ $purchase->id }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-light dark:text-text-dark">
                                {{ $purchase->purchase_date->format('d M Y') }}
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $purchase->purchase_date->format('H:i') }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-text-light dark:text-text-dark">
                                    {{ $purchase->supplier->name ?? 'N/A' }}
                                </div>
                                @if($purchase->supplier)
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $purchase->supplier->phone ?? '-' }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-text-light dark:text-text-dark">
                                    Rp {{ number_format($purchase->total, 0, ',', '.') }}
                                </div>
                                @if($purchase->supplierCredit && $purchase->supplierCredit->status === 'active')
                                    <div class="text-xs text-danger">
                                        Sisa: Rp {{ number_format($purchase->supplierCredit->remaining_amount, 0, ',', '.') }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                @if($purchase->payment_method === 'cash')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Cash
                                    </span>
                                @elseif($purchase->payment_method === 'credit')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                        Kredit
                                    </span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        Transfer
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                @if($purchase->status === 'completed')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Selesai
                                    </span>
                                @elseif($purchase->status === 'pending')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                        Pending
                                    </span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                        Dibatalkan
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-light dark:text-text-dark">
                                {{ $purchase->user->name ?? 'Unknown' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center">
                                <div class="flex items-center justify-center space-x-2">
                                    <a href="{{ route('purchases.show', $purchase) }}" class="flex items-center px-3 py-1 text-sm font-medium text-white bg-primary rounded-md hover:bg-green-600">
                                        <span class="material-icons mr-1 text-sm">visibility</span> View
                                    </a>

                                    @if($purchase->status === 'pending')
                                        <button @click="confirmReceipt({{ $purchase->id }})" :disabled="confirming" class="flex items-center px-3 py-1 text-sm font-medium text-white bg-blue-500 rounded-md hover:bg-blue-600 disabled:opacity-50">
                                            <span class="material-icons mr-1 text-sm">check_circle</span> Terima
                                        </button>
                                    @endif

                                    @if($purchase->status === 'pending')
                                        <a href="{{ route('purchases.edit', $purchase) }}" class="flex items-center px-3 py-1 text-sm font-medium text-white bg-yellow-500 rounded-md hover:bg-yellow-600">
                                            <span class="material-icons mr-1 text-sm">edit</span> Edit
                                        </a>
                                    @endif

                                    @if($purchase->status === 'completed')
                                        <a href="{{ route('purchases.print-receipt', $purchase) }}" target="_blank" class="flex items-center px-3 py-1 text-sm font-medium text-white bg-purple-500 rounded-md hover:bg-purple-600">
                                            <span class="material-icons mr-1 text-sm">print</span> Print
                                        </a>
                                    @endif

                                    @if($purchase->status === 'pending' && (!$purchase->supplierCredit || $purchase->supplierCredit->paid_amount == 0))
                                        <button @click="deletePurchase({{ $purchase->id }})" class="flex items-center px-3 py-1 text-sm font-medium text-white bg-danger rounded-md hover:bg-red-700">
                                            <span class="material-icons mr-1 text-sm">delete</span> Hapus
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <div class="text-gray-500 dark:text-gray-400">
                                    <span class="material-icons text-4xl mb-4 block">shopping_cart</span>
                                    <p class="text-lg font-medium">Tidak ada transaksi ditemukan</p>
                                    <p class="mt-1">Mulai buat transaksi pembelian pertama Anda.</p>
                                    <a href="{{ route('purchases.create') }}" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary hover:bg-green-600">
                                        <span class="material-icons mr-2">add</span>
                                        Buat Pembelian Baru
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($purchases->hasPages())
            <div class="px-6 py-4 border-t border-border-light dark:border-border-dark">
                {{ $purchases->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
// Additional JavaScript for enhanced functionality
document.addEventListener('DOMContentLoaded', function() {
    // Auto submit form when filters change
    const filterSelects = document.querySelectorAll('select[name="status"], select[name="payment_method"], select[name="supplier_id"]');
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            this.closest('form').submit();
        });
    });

    // Format currency function
    window.formatCurrency = function(value) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(value || 0);
    };

    // Auto-refresh pending purchases every 30 seconds
    const hasPendingPurchases = document.querySelector('span.bg-yellow-100');
    if (hasPendingPurchases) {
        setInterval(() => {
            // Only refresh if user is not actively interacting
            if (!document.activeElement || document.activeElement.tagName !== 'INPUT') {
                const urlParams = new URLSearchParams(window.location.search);
                if (!urlParams.has('status') || urlParams.get('status') === 'pending') {
                    // Silent refresh - you might want to use AJAX instead
                    console.log('Auto-refresh available for pending purchases');
                }
            }
        }, 30000);
    }

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + K to focus search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            document.querySelector('input[name="search"]')?.focus();
        }
        
        // Ctrl/Cmd + N to create new purchase
        if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
            e.preventDefault();
            window.location.href = '{{ route("purchases.create") }}';
        }
    });

    // Show toast notifications
    @if(session('success'))
        showToast('{{ session("success") }}', 'success');
    @endif

    @if(session('error'))
        showToast('{{ session("error") }}', 'error');
    @endif

    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 ${
            type === 'success' ? 'bg-green-500' : 'bg-red-500'
        } text-white`;
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('opacity-0', 'transition-opacity', 'duration-500');
            setTimeout(() => toast.remove(), 500);
        }, 3000);
    }
});
</script>
@endpush