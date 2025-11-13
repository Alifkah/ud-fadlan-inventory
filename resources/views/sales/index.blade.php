@extends('layouts.app')

@section('title', 'Daftar Penjualan')
@section('page-title', 'Daftar Transaksi Penjualan')
@section('page-description', 'Lihat dan kelola semua transaksi penjualan.')

@section('content')
<div x-data="{ 
    openDeleteModal: false, 
    openViewModal: false, 
    openPrintModal: false,
    filtersOpen: false,
    selectedSale: null,
    
    deleteSale(saleId) {
        if (!confirm('Apakah Anda yakin ingin menghapus transaksi ini?')) {
            return;
        }
        
        fetch(`/sales/${saleId}`, {
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
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Penjualan</p>
                    <p class="text-2xl font-bold text-text-light dark:text-text-dark">
                        Rp {{ number_format($stats['total_sales'] ?? 0, 0, ',', '.') }}
                    </p>
                </div>
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                    <span class="material-icons text-blue-600 dark:text-blue-300">point_of_sale</span>
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
            <a href="{{ route('sales.create') }}" class="flex items-center justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                <span class="material-icons mr-2">add</span> 
                Buat Penjualan Baru
            </a>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow-sm mb-6">
        <div class="flex flex-col md:flex-row justify-between md:items-center space-y-4 md:space-y-0">
            <!-- Search -->
            <div class="flex-1 max-w-md">
                <form method="GET" action="{{ route('sales.index') }}">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="material-icons text-gray-400">search</span>
                        </div>
                        <input 
                            type="search" 
                            name="search"
                            value="{{ request('search') }}"
                            class="block w-full pl-10 pr-3 py-2 border border-border-light dark:border-border-dark rounded-md leading-5 bg-background-light dark:bg-background-dark text-text-light dark:text-text-dark placeholder-gray-400 focus:outline-none focus:placeholder-gray-500 focus:ring-1 focus:ring-primary focus:border-primary sm:text-sm" 
                            placeholder="Cari berdasarkan invoice, customer..."
                        />
                    </div>
                    <input type="hidden" name="status" value="{{ request('status') }}">
                    <input type="hidden" name="payment_method" value="{{ request('payment_method') }}">
                    <input type="hidden" name="customer_id" value="{{ request('customer_id') }}">
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
                            <a href="{{ route('sales.index', array_merge(request()->except('sort'), ['sort' => 'date_desc'])) }}" class="flex items-center px-4 py-2 text-sm text-text-light dark:text-text-dark hover:bg-gray-100 dark:hover:bg-gray-700">
                                <span class="material-icons mr-3 text-base">arrow_upward</span> Tanggal Terbaru
                            </a>
                            <a href="{{ route('sales.index', array_merge(request()->except('sort'), ['sort' => 'date_asc'])) }}" class="flex items-center px-4 py-2 text-sm text-text-light dark:text-text-dark hover:bg-gray-100 dark:hover:bg-gray-700">
                                <span class="material-icons mr-3 text-base">arrow_downward</span> Tanggal Terlama
                            </a>
                            <a href="{{ route('sales.index', array_merge(request()->except('sort'), ['sort' => 'total_desc'])) }}" class="flex items-center px-4 py-2 text-sm text-text-light dark:text-text-dark hover:bg-gray-100 dark:hover:bg-gray-700">
                                <span class="material-icons mr-3 text-base">arrow_upward</span> Total Terbesar
                            </a>
                            <a href="{{ route('sales.index', array_merge(request()->except('sort'), ['sort' => 'total_asc'])) }}" class="flex items-center px-4 py-2 text-sm text-text-light dark:text-text-dark hover:bg-gray-100 dark:hover:bg-gray-700">
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
                            <a href="{{ route('sales.export', array_merge(request()->all(), ['format' => 'excel'])) }}" class="flex items-center px-4 py-2 text-sm text-text-light dark:text-text-dark hover:bg-gray-100 dark:hover:bg-gray-700">
                                <span class="material-icons mr-3 text-base">grid_on</span> Ekspor ke Excel
                            </a>
                            <a href="{{ route('sales.export', array_merge(request()->all(), ['format' => 'pdf'])) }}" class="flex items-center px-4 py-2 text-sm text-text-light dark:text-text-dark hover:bg-gray-100 dark:hover:bg-gray-700">
                                <span class="material-icons mr-3 text-base">description</span> Ekspor ke PDF
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Advanced Filters -->
        <div x-show="filtersOpen" x-transition class="border-t border-border-light dark:border-border-dark mt-4 pt-4">
            <form method="GET" action="{{ route('sales.index') }}">
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
                        <label for="customer_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Customer</label>
                        <select name="customer_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-border-light dark:border-border-dark focus:outline-none focus:ring-primary focus:border-primary sm:text-sm rounded-md bg-background-light dark:bg-background-dark">
                            <option value="">Semua Customer</option>
                            @foreach($customers ?? [] as $customer)
                                <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }}
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
                    <a href="{{ route('sales.index') }}" class="px-4 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500">
                        Reset
                    </a>
                    <button type="submit" class="px-4 py-2 rounded-md text-sm font-medium text-white bg-primary hover:bg-green-600">
                        Terapkan Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Sales Table -->
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
                            Customer
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
                            Kasir
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-card-light dark:bg-card-dark divide-y divide-border-light dark:divide-border-dark">
                    @forelse($sales as $sale)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <a href="{{ route('sales.show', $sale) }}" class="text-sm font-medium text-primary hover:underline">
                                        {{ $sale->invoice_number }}
                                    </a>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        ID: {{ $sale->id }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-light dark:text-text-dark">
                                {{ $sale->sale_date->format('d M Y') }}
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $sale->sale_date->format('H:i') }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-text-light dark:text-text-dark">
                                    {{ $sale->customer->name ?? 'Guest' }}
                                </div>
                                @if($sale->customer)
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $sale->customer->phone ?? '-' }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-text-light dark:text-text-dark">
                                    Rp {{ number_format($sale->total, 0, ',', '.') }}
                                </div>
                                @if($sale->customerCredit && $sale->customerCredit->status === 'active')
                                    <div class="text-xs text-danger">
                                        Sisa: Rp {{ number_format($sale->customerCredit->remaining_amount, 0, ',', '.') }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                @if($sale->payment_method === 'cash')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Cash
                                    </span>
                                @elseif($sale->payment_method === 'credit')
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
                                @if($sale->status === 'completed')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Selesai
                                    </span>
                                @elseif($sale->status === 'pending')
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
                                {{ $sale->user->name ?? 'Unknown' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center">
                                <div class="flex items-center justify-center space-x-2">
                                    <a href="{{ route('sales.show', $sale) }}" class="flex items-center px-3 py-1 text-sm font-medium text-white bg-primary rounded-md hover:bg-green-600">
                                        <span class="material-icons mr-1 text-sm">visibility</span> View
                                    </a>

                                    @if($sale->status === 'pending')
                                        <a href="{{ route('sales.edit', $sale) }}" class="flex items-center px-3 py-1 text-sm font-medium text-white bg-yellow-500 rounded-md hover:bg-yellow-600">
                                            <span class="material-icons mr-1 text-sm">edit</span> Edit
                                        </a>
                                    @endif

                                    @if($sale->status === 'completed')
                                        <a href="{{ route('sales.print-receipt', $sale) }}" target="_blank" class="flex items-center px-3 py-1 text-sm font-medium text-white bg-purple-500 rounded-md hover:bg-purple-600">
                                            <span class="material-icons mr-1 text-sm">print</span> Print
                                        </a>
                                    @endif

                                    @if($sale->status === 'pending' && (!$sale->customerCredit || $sale->customerCredit->paid_amount == 0))
                                        <button @click="deleteSale({{ $sale->id }})" class="flex items-center px-3 py-1 text-sm font-medium text-white bg-danger rounded-md hover:bg-red-700">
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
                                    <span class="material-icons text-4xl mb-4 block">point_of_sale</span>
                                    <p class="text-lg font-medium">Tidak ada transaksi ditemukan</p>
                                    <p class="mt-1">Mulai buat transaksi penjualan pertama Anda.</p>
                                    <a href="{{ route('sales.create') }}" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary hover:bg-green-600">
                                        <span class="material-icons mr-2">add</span>
                                        Buat Penjualan Baru
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($sales->hasPages())
            <div class="px-6 py-4 border-t border-border-light dark:border-border-dark">
                {{ $sales->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Format currency function
    window.formatCurrency = function(value) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(value || 0);
    };

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