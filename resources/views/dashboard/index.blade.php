@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard Toko Bangunan')
@section('page-description', 'Ringkasan performa dan aktivitas toko hari ini')

@section('content')
    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow">
            <p class="text-sm text-text-muted-light dark:text-text-muted-dark">Pendapatan Hari ini</p>
            <p class="text-2xl font-bold text-text-light dark:text-text-dark" id="today-revenue">
                Rp {{ number_format($currentMonthSales, 0, ',', '.') }}
            </p>
        </div>
        <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow">
            <p class="text-sm text-text-muted-light dark:text-text-muted-dark">Transaksi Hari ini</p>
            <p class="text-2xl font-bold text-text-light dark:text-text-dark" id="today-transactions">150</p>
        </div>
        <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow">
            <p class="text-sm text-text-muted-light dark:text-text-muted-dark">Stok Menipis</p>
            <p class="text-2xl font-bold text-text-light dark:text-text-dark" id="low-stock">{{ $lowStockProducts }}</p>
        </div>
        <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow">
            <p class="text-sm text-text-muted-light dark:text-text-muted-dark">Total Produk</p>
            <p class="text-2xl font-bold text-text-light dark:text-text-dark">{{ $totalProducts }}</p>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Sales Trend Chart -->
        <div class="lg:col-span-2 bg-card-light dark:bg-card-dark p-6 rounded-lg shadow">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-text-light dark:text-text-dark">Trend Penjualan</h2>
                <div class="flex space-x-1 bg-gray-100 dark:bg-gray-700 p-1 rounded-lg">
                    <button class="px-3 py-1 text-sm bg-white dark:bg-gray-600 text-primary font-semibold rounded-md shadow" data-period="month">Bulanan</button>
                    <button class="px-3 py-1 text-sm text-text-muted-light dark:text-text-muted-dark rounded-md" data-period="week">Mingguan</button>
                    <button class="px-3 py-1 text-sm text-text-muted-light dark:text-text-muted-dark rounded-md" data-period="day">Harian</button>
                </div>
            </div>
            <div class="h-64">
                <canvas id="salesChart"></canvas>
            </div>
            <div class="flex justify-between items-center mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <div>
                    <p class="text-2xl font-bold text-text-light dark:text-text-dark">
                        Rp {{ number_format($currentMonthSales, 0, ',', '.') }}
                    </p>
                    <p class="text-sm text-text-muted-light dark:text-text-muted-dark">Rata-rata penjualan per Bulan</p>
                </div>
            </div>
        </div>

        <!-- Category Sales Chart -->
        <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow">
            <h2 class="text-xl font-semibold text-text-light dark:text-text-dark mb-4">Kategori Penjualan</h2>
            <div class="relative flex items-center justify-center h-48 mb-4">
                <canvas id="categoryChart"></canvas>
                <div class="absolute text-center">
                    <p class="text-3xl font-bold text-text-light dark:text-text-dark">{{ $categoryData->sum('total') }}</p>
                    <p class="text-sm text-text-muted-light dark:text-text-muted-dark">Total Item</p>
                </div>
            </div>
            <div class="space-y-2 text-sm">
                @foreach($categoryData as $category)
                    <div class="flex justify-between">
                        <div class="flex items-center">
                            <span class="w-3 h-3 rounded-full mr-2" style="background-color: {{ \App\Helpers\DashboardHelper::getCategoryColor($loop->index) }}"></span>
                            <span class="text-text-muted-light dark:text-text-muted-dark">{{ $category['name'] }}</span>
                        </div>
                        <span class="font-semibold text-text-light dark:text-text-dark">
                            {{ $category['percentage'] }}% 
                            <span class="text-text-muted-light dark:text-text-muted-dark text-xs">{{ $category['total'] }} item</span>
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow">
        <div class="flex flex-wrap justify-between items-center mb-4 gap-4">
            <h2 class="text-xl font-semibold text-text-light dark:text-text-dark">Transaksi Terbaru</h2>
            <div class="flex items-center space-x-2">
                <button class="flex items-center px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg text-text-light dark:text-text-dark hover:bg-gray-100 dark:hover:bg-gray-700">
                    <span class="material-icons mr-2 text-sm">download</span>
                    Export
                </button>
                <a href="{{ route('sales.index') }}" class="flex items-center px-4 py-2 text-sm bg-primary text-white rounded-lg hover:bg-blue-600">
                    <span class="material-icons mr-2 text-sm">add</span>
                    Transaksi Baru
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap items-center gap-4 mb-4 pb-4 border-b border-gray-200 dark:border-gray-700">
            <div class="relative flex-grow">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="material-icons text-text-muted-light dark:text-text-muted-dark">search</span>
                </div>
                <input class="bg-background-light dark:bg-card-dark border border-gray-300 dark:border-gray-600 text-text-light dark:text-text-dark text-sm rounded-lg focus:ring-primary focus:border-primary block w-full pl-10 p-2.5" 
                       id="search-transactions" placeholder="Cari transaksi..." type="search">
            </div>
            <div class="flex items-center space-x-2">
                <label class="text-sm font-medium text-text-muted-light dark:text-text-muted-dark" for="date-range">Rentang Tanggal:</label>
                <input class="bg-background-light dark:bg-card-dark border border-gray-300 dark:border-gray-600 text-text-light dark:text-text-dark text-sm rounded-lg focus:ring-primary focus:border-primary block w-full p-2.5" 
                       id="start-date" type="date">
                <span class="text-text-muted-light dark:text-text-muted-dark">to</span>
                <input class="bg-background-light dark:bg-card-dark border border-gray-300 dark:border-gray-600 text-text-light dark:text-text-dark text-sm rounded-lg focus:ring-primary focus:border-primary block w-full p-2.5" 
                       id="end-date" type="date">
            </div>
            <div class="flex items-center space-x-2">
                <label class="text-sm font-medium text-text-muted-light dark:text-text-muted-dark" for="transaction-type">Tipe:</label>
                <select class="bg-background-light dark:bg-card-dark border border-gray-300 dark:border-gray-600 text-text-light dark:text-text-dark text-sm rounded-lg focus:ring-primary focus:border-primary block w-full p-2.5" 
                        id="transaction-type">
                    <option selected>Semua</option>
                    <option value="penjualan">Penjualan</option>
                    <option value="pembelian">Pembelian</option>
                </select>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-text-muted-light dark:text-text-muted-dark">
                <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-3">ID Transaksi</th>
                        <th scope="col" class="px-6 py-3">Customer</th>
                        <th scope="col" class="px-6 py-3">Total</th>
                        <th scope="col" class="px-6 py-3">Tanggal</th>
                        <th scope="col" class="px-6 py-3">Status</th>
                        <th scope="col" class="px-6 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentTransactions as $transaction)
                        <tr class="bg-card-light dark:bg-card-dark border-b dark:border-gray-700">
                            <td class="px-6 py-4 font-medium text-text-light dark:text-text-dark whitespace-nowrap">
                                {{ $transaction['invoice'] }}
                            </td>
                            <td class="px-6 py-4">{{ $transaction['customer'] }}</td>
                            <td class="px-6 py-4">Rp {{ number_format($transaction['total'], 0, ',', '.') }}</td>
                            <td class="px-6 py-4">{{ \Carbon\Carbon::parse($transaction['date'])->format('d M Y') }}</td>
                            <td class="px-6 py-4">
                                @switch($transaction['status'])
                                    @case('completed')
                                        <span class="bg-green-100 text-green-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded-full dark:bg-green-900 dark:text-green-300">
                                            Selesai
                                        </span>
                                        @break
                                    @case('pending')
                                        <span class="bg-blue-100 text-blue-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">
                                            Proses
                                        </span>
                                        @break
                                    @case('canceled')
                                        <span class="bg-red-100 text-red-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded-full dark:bg-red-900 dark:text-red-300">
                                            Dibatalkan
                                        </span>
                                        @break
                                    @default
                                        <span class="bg-gray-100 text-gray-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded-full dark:bg-gray-900 dark:text-gray-300">
                                            {{ ucfirst($transaction['status']) }}
                                        </span>
                                @endswitch
                            </td>
                            <td class="px-6 py-4 flex space-x-2 justify-center">
                                <button onclick="showTransactionDetail({{ $transaction['id'] }})" class="px-3 py-1 text-xs bg-blue-500 text-white rounded-md hover:bg-blue-600">
                                    Detail
                                </button>
                                @if($transaction['status'] === 'pending')
                                    <button onclick="showEditTransaction({{ $transaction['id'] }})" class="px-3 py-1 text-xs bg-yellow-400 text-white rounded-md hover:bg-yellow-500">
                                        Edit
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr class="bg-card-light dark:bg-card-dark">
                            <td colspan="6" class="px-6 py-4 text-center text-text-muted-light dark:text-text-muted-dark">
                                Tidak ada transaksi terbaru
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Detail Transaksi -->
    <div id="transactionDetailModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4" id="modal-title">
                                Detail Transaksi
                            </h3>
                            
                            <div id="transactionDetailContent" class="text-sm text-gray-500 dark:text-gray-300">
                                <!-- Loading spinner -->
                                <div class="flex justify-center py-4">
                                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button onclick="closeTransactionDetailModal()" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-gray-600 text-base font-medium text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Tutup
                    </button>
                    <a id="printReceiptBtn" href="#" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        <span class="material-icons mr-2 text-sm">print</span>
                        Cetak
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit Transaksi -->
    <div id="editTransactionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <form id="editTransactionForm">
                    @csrf
                    @method('PUT')
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                                    Edit Transaksi
                                </h3>
                                
                                <div id="editTransactionContent" class="space-y-4">
                                    <!-- Loading spinner -->
                                    <div class="flex justify-center py-4">
                                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:ml-3 sm:w-auto sm:text-sm">
                            Simpan
                        </button>
                        <button onclick="closeEditTransactionModal()" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
/* Modal animations */
.modal-enter {
    opacity: 0;
    transform: scale(0.95);
}

.modal-enter-active {
    opacity: 1;
    transform: scale(1);
    transition: opacity 0.3s ease-out, transform 0.3s ease-out;
}

.modal-exit {
    opacity: 1;
    transform: scale(1);
}

.modal-exit-active {
    opacity: 0;
    transform: scale(0.95);
    transition: opacity 0.2s ease-in, transform 0.2s ease-in;
}

/* Backdrop animations */
.backdrop-enter {
    opacity: 0;
}

.backdrop-enter-active {
    opacity: 1;
    transition: opacity 0.3s ease-out;
}

.backdrop-exit {
    opacity: 1;
}

.backdrop-exit-active {
    opacity: 0;
    transition: opacity 0.2s ease-in;
}

/* Custom scrollbar for modal content */
.modal-content::-webkit-scrollbar {
    width: 6px;
}

.modal-content::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.modal-content::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

.modal-content::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/dashboard.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Sales Chart
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    const salesChart = new Chart(salesCtx, {
        type: 'bar',
        data: {
            labels: @json($salesTrend->pluck('period')),
            datasets: [{
                label: 'Penjualan (Juta)',
                data: @json($salesTrend->pluck('total')),
                backgroundColor: 'rgba(0, 123, 255, 0.5)',
                borderColor: 'rgba(0, 123, 255, 1)',
                borderWidth: 1,
                borderRadius: 5,
                barThickness: 30,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += DashboardUtils.formatCurrency(context.parsed.y * 1000000);
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(200, 200, 200, 0.2)'
                    },
                    ticks: {
                        callback: function(value, index, values) {
                            return value + ' Jt';
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });

    // Initialize Category Chart
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    const categoryChart = new Chart(categoryCtx, {
        type: 'doughnut',
        data: {
            labels: @json($categoryData->pluck('name')),
            datasets: [{
                label: 'Kategori Penjualan',
                data: @json($categoryData->pluck('percentage')),
                backgroundColor: [
                    '#60a5fa', // blue-400
                    '#facc15', // yellow-400
                    '#4ade80', // green-400
                    '#f87171', // red-400
                    '#a78bfa', // purple-400
                    '#fb7185'  // pink-400
                ],
                borderColor: [
                    '#f8f9fa', // background-light
                ],
                borderWidth: 5,
                cutout: '80%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed;
                            return label + ': ' + value + '%';
                        }
                    }
                }
            }
        }
    });

    // Chart Period Toggle
    const periodButtons = document.querySelectorAll('[data-period]');
    periodButtons.forEach(button => {
        button.addEventListener('click', function() {
            const period = this.dataset.period;
            
            // Update button styles
            periodButtons.forEach(btn => {
                btn.className = 'px-3 py-1 text-sm text-text-muted-light dark:text-text-muted-dark rounded-md';
            });
            this.className = 'px-3 py-1 text-sm bg-white dark:bg-gray-600 text-primary font-semibold rounded-md shadow';
            
            // Update chart using Dashboard utility
            Dashboard.updateChart(salesChart, period);
        });
    });
});

// Helper function to format currency properly
function formatRupiah(amount) {
    // Convert to number and round
    const num = Math.round(parseFloat(amount));
    // Format with Indonesian locale
    return num.toLocaleString('id-ID');
}

// Modal Functions
function showTransactionDetail(transactionId) {
    const modal = document.getElementById('transactionDetailModal');
    const content = document.getElementById('transactionDetailContent');
    const printBtn = document.getElementById('printReceiptBtn');
    
    // Show modal
    modal.classList.remove('hidden');
    
    // Reset content to loading
    content.innerHTML = `
        <div class="flex justify-center py-4">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
        </div>
    `;
    
    // Fetch transaction details
    fetch(`/sales/${transactionId}/details`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            const transaction = data.data;
            content.innerHTML = `
                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">No. Invoice</label>
                            <p class="text-sm text-gray-900 dark:text-white font-semibold">${transaction.invoice_number}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Customer</label>
                            <p class="text-sm text-gray-900 dark:text-white">${transaction.customer_name || 'Guest'}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal</label>
                            <p class="text-sm text-gray-900 dark:text-white">${new Date(transaction.sale_date).toLocaleDateString('id-ID')}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getStatusClass(transaction.status)}">
                                ${getStatusText(transaction.status)}
                            </span>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Item Transaksi</label>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Produk</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Qty</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Harga</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600">
                                    ${transaction.items.map(item => `
                                        <tr>
                                            <td class="px-3 py-2 text-sm text-gray-900 dark:text-white">${item.product_name}</td>
                                            <td class="px-3 py-2 text-sm text-gray-900 dark:text-white text-right">${item.quantity}</td>
                                            <td class="px-3 py-2 text-sm text-gray-900 dark:text-white text-right">Rp ${formatRupiah(item.unit_price / 100)}</td>
                                            <td class="px-3 py-2 text-sm text-gray-900 dark:text-white text-right">Rp ${formatRupiah(item.total_price / 100)}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600">
                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Subtotal:</span>
                            <span class="text-sm text-gray-900 dark:text-white">Rp ${formatRupiah(transaction.subtotal)}</span>
                        </div>
                        ${transaction.discount > 0 ? `
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Diskon:</span>
                                <span class="text-sm text-gray-900 dark:text-white">-Rp ${formatRupiah(transaction.discount)}</span>
                            </div>
                        ` : ''}
                        ${transaction.tax > 0 ? `
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Pajak:</span>
                                <span class="text-sm text-gray-900 dark:text-white">Rp ${formatRupiah(transaction.tax)}</span>
                            </div>
                        ` : ''}
                        <div class="flex justify-between font-semibold text-lg">
                            <span class="text-gray-900 dark:text-white">Total:</span>
                            <span class="text-gray-900 dark:text-white">Rp ${formatRupiah(transaction.total)}</span>
                        </div>
                    </div>
                </div>
            `;
            
            // Update print button
            printBtn.href = `/sales/${transactionId}/print-receipt`;
        } else {
            console.error('API returned success: false', data);
            content.innerHTML = `
                <div class="text-center py-4">
                    <p class="text-red-500">Gagal memuat detail transaksi: ${data.message || 'Unknown error'}</p>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        content.innerHTML = `
            <div class="text-center py-4">
                <p class="text-red-500">Terjadi kesalahan saat memuat data: ${error.message}</p>
                <p class="text-xs text-gray-500 mt-2">Lihat console untuk detail error</p>
            </div>
        `;
    });
}

function closeTransactionDetailModal() {
    document.getElementById('transactionDetailModal').classList.add('hidden');
}

function showEditTransaction(transactionId) {
    const modal = document.getElementById('editTransactionModal');
    const content = document.getElementById('editTransactionContent');
    const form = document.getElementById('editTransactionForm');
    
    // Show modal
    modal.classList.remove('hidden');
    
    // Set form action
    form.action = `/sales/${transactionId}/quick-update`;
    
    // Reset content to loading
    content.innerHTML = `
        <div class="flex justify-center py-4">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
        </div>
    `;
    
    // Fetch transaction data for editing
    fetch(`/sales/${transactionId}/edit-data`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const transaction = data.data;
                content.innerHTML = `
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">No. Invoice</label>
                                <input type="text" name="invoice_number" value="${transaction.invoice_number}" readonly 
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Customer</label>
                                <select name="customer_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                                    <option value="">Guest</option>
                                    ${data.customers.map(customer => 
                                        `<option value="${customer.id}" ${transaction.customer_id == customer.id ? 'selected' : ''}>${customer.name}</option>`
                                    ).join('')}
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Penjualan</label>
                                <input type="date" name="sale_date" value="${transaction.sale_date}" 
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Metode Pembayaran</label>
                                <select name="payment_method" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                                    <option value="cash" ${transaction.payment_method == 'cash' ? 'selected' : ''}>Tunai</option>
                                    <option value="credit" ${transaction.payment_method == 'credit' ? 'selected' : ''}>Kredit</option>
                                    <option value="transfer" ${transaction.payment_method == 'transfer' ? 'selected' : ''}>Transfer</option>
                                </select>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Catatan</label>
                            <textarea name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">${transaction.notes || ''}</textarea>
                        </div>
                        
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 p-3 rounded-md">
                            <div class="flex">
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Perhatian</h3>
                                    <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                                        <p>Hanya data dasar yang dapat diedit dari modal ini. Untuk mengedit item transaksi, gunakan halaman edit lengkap.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="pt-4 border-t border-gray-200 dark:border-gray-600">
                            <div class="flex justify-between text-lg font-semibold">
                                <span class="text-gray-900 dark:text-white">Total Transaksi:</span>
                                <span class="text-gray-900 dark:text-white">Rp ${formatRupiah(transaction.total)}</span>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                content.innerHTML = `
                    <div class="text-center py-4">
                        <p class="text-red-500">Gagal memuat data transaksi</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            content.innerHTML = `
                <div class="text-center py-4">
                    <p class="text-red-500">Terjadi kesalahan saat memuat data</p>
                </div>
            `;
        });
}

function closeEditTransactionModal() {
    document.getElementById('editTransactionModal').classList.add('hidden');
}

// Handle edit form submission
document.getElementById('editTransactionForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch(this.action, {
        method: 'PUT',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify(Object.fromEntries(formData))
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeEditTransactionModal();
            alert('Transaksi berhasil diperbarui');
            location.reload();
        } else {
            alert(data.message || 'Gagal memperbarui transaksi');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat memperbarui transaksi');
    });
});

// Helper functions
function getStatusClass(status) {
    const classes = {
        'completed': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        'pending': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        'canceled': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
    };
    return classes[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300';
}

function getStatusText(status) {
    const texts = {
        'completed': 'Selesai',
        'pending': 'Proses',
        'canceled': 'Dibatalkan'
    };
    return texts[status] || status;
}
</script>
@endpush