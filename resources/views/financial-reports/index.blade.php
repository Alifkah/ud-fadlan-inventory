@extends('layouts.app')
@section('title', 'Laporan Keuangan')
@section('page-title', 'Laporan Keuangan')
@section('page-description', 'Laporan keuangan toko dan produk terlaris')
@section('content')
    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
        <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow">
            <p class="text-sm text-text-muted-light dark:text-text-muted-dark">{{ $mainStats['revenue']['label'] }}</p>
            <p class="text-2xl font-bold text-text-light dark:text-text-dark my-2">
                Rp {{ number_format($mainStats['revenue']['amount'], 0, ',', '.') }}
            </p>
            <p class="text-xs {{ $mainStats['revenue']['growth'] >= 0 ? 'text-green-500' : 'text-red-500' }}">
                {{ $mainStats['revenue']['growth'] >= 0 ? '+' : '' }}{{ $mainStats['revenue']['growth_label'] }}
            </p>
        </div>
        <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow">
            <p class="text-sm text-text-muted-light dark:text-text-muted-dark">{{ $mainStats['expenditure']['label'] }}</p>
            <p class="text-2xl font-bold text-text-light dark:text-text-dark my-2">
                Rp {{ number_format($mainStats['expenditure']['amount'], 0, ',', '.') }}
            </p>
            <p class="text-xs {{ $mainStats['expenditure']['growth'] >= 0 ? 'text-red-500' : 'text-green-500' }}">
                {{ $mainStats['expenditure']['growth'] >= 0 ? '+' : '' }}{{ $mainStats['expenditure']['growth_label'] }}
            </p>
        </div>
        <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow">
            <p class="text-sm text-text-muted-light dark:text-text-muted-dark">{{ $mainStats['net_profit']['label'] }}</p>
            <p class="text-2xl font-bold text-text-light dark:text-text-dark my-2">
                Rp {{ number_format($mainStats['net_profit']['amount'], 0, ',', '.') }}
            </p>
            <p class="text-xs {{ $mainStats['net_profit']['amount'] >= 0 ? 'text-green-500' : 'text-red-500' }}">
                Margin: {{ $mainStats['revenue']['amount'] > 0 ? number_format(($mainStats['net_profit']['amount'] / $mainStats['revenue']['amount']) * 100, 1) : 0 }}%
            </p>
        </div>
        <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow">
            <p class="text-sm text-text-muted-light dark:text-text-muted-dark">{{ $mainStats['total_capital']['label'] }}</p>
            <p class="text-2xl font-bold text-text-light dark:text-text-dark my-2">
                Rp {{ number_format($mainStats['total_capital']['amount'], 0, ',', '.') }}
            </p>
            <p class="text-xs text-text-muted-light dark:text-text-muted-dark">Nilai Stok Saat Ini</p>
        </div>
    </div>
    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Sales Trend Chart -->
        <div class="lg:col-span-2 bg-card-light dark:bg-card-dark p-6 rounded-lg shadow">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-text-light dark:text-text-dark">Trend Penjualan vs Pembelian</h3>
                <div class="flex space-x-1 bg-gray-100 dark:bg-gray-700 p-1 rounded-lg">
                    <button class="px-3 py-1 text-sm font-medium rounded-md bg-white dark:bg-gray-800 text-primary shadow period-btn" data-period="bulanan">Bulanan</button>
                    <button class="px-3 py-1 text-sm font-medium text-gray-600 dark:text-gray-300 rounded-md period-btn" data-period="mingguan">Mingguan</button>
                    <button class="px-3 py-1 text-sm font-medium text-gray-600 dark:text-gray-300 rounded-md period-btn" data-period="harian">Harian</button>
                </div>
            </div>
            <div class="h-80">
                <canvas id="salesTrendChart"></canvas>
            </div>
        </div>
        <!-- Store Items Donut Chart -->
        <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold text-text-light dark:text-text-dark mb-4">Total Item Toko</h3>
            <div class="flex justify-center items-center my-6">
                <div class="relative w-48 h-48">
                    <canvas id="storeItemsChart"></canvas>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-3xl font-bold text-text-light dark:text-text-dark">
                            {{ number_format($storeItemsData['total_items']) }}
                        </span>
                        <span class="text-sm text-text-muted-light dark:text-text-muted-dark">Total Item</span>
                    </div>
                </div>
            </div>
            <div class="space-y-3">
                @foreach($storeItemsData['categories'] as $index => $category)
                    <div class="flex justify-between items-center text-sm">
                        <div class="flex items-center">
                            <span class="w-2 h-2 rounded-full mr-2" style="background-color: {{ ['#3b82f6', '#14b8a6', '#f97316', '#eab308', '#8b5cf6'][$index % 5] }}"></span>
                            <span class="text-text-muted-light dark:text-text-muted-dark">{{ $category['name'] }}</span>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-text-light dark:text-text-dark">{{ $category['percentage'] }}%</p>
                            <p class="text-xs text-text-muted-light dark:text-text-muted-dark">{{ number_format($category['count']) }} Item</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <!-- Top Selling Products -->
        <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-text-light dark:text-text-dark">Produk Terlaris Periode Ini</h3>
                <!-- Dropdown Export -->
                <div class="relative group" id="export-dropdown-container">
                    <button class="bg-primary text-white px-4 py-2 rounded-lg flex items-center text-sm hover:bg-blue-600" id="export-dropdown-button">
                        <span class="material-icons mr-2 text-base">file_download</span>
                        Export Laporan
                    </button>
                    <div class="absolute right-0 mt-2 w-48 bg-card-light dark:bg-card-dark rounded-md shadow-lg hidden z-10 border border-gray-200 dark:border-gray-700" id="export-dropdown-menu">
                        <a href="#" data-format="pdf" class="export-option block px-4 py-2 text-sm text-text-light dark:text-text-dark hover:bg-gray-100 dark:hover:bg-gray-700">Export as PDF</a>
                        <a href="#" data-format="csv" class="export-option block px-4 py-2 text-sm text-text-light dark:text-text-dark hover:bg-gray-100 dark:hover:bg-gray-700">Export as Excel</a>
                    </div>
                </div>
            </div>
            <!-- Hapus tombol export lama jika ingin diganti sepenuhnya -->
            {{-- <div class="relative group">
                <button class="bg-primary text-white px-4 py-2 rounded-lg flex items-center text-sm hover:bg-blue-600">
                    <span class="material-icons mr-2 text-base">file_download</span>
                    Export
                </button>
                <div class="absolute right-0 mt-2 w-48 bg-card-light dark:bg-card-dark rounded-md shadow-lg hidden group-hover:block z-10 border border-gray-200 dark:border-gray-700">
                    <a href="#" onclick="exportTopProducts('csv')" class="block px-4 py-2 text-sm text-text-light dark:text-text-dark hover:bg-gray-100 dark:hover:bg-gray-700">Export as CSV</a>
                    <a href="#" onclick="exportTopProducts('pdf')" class="block px-4 py-2 text-sm text-text-light dark:text-text-dark hover:bg-gray-100 dark:hover:bg-gray-700">Export as PDF</a>
                </div>
            </div> --}}
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-text-muted-light dark:text-text-muted-dark uppercase bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-3">Rank</th>
                        <th scope="col" class="px-6 py-3">Produk</th>
                        <th scope="col" class="px-6 py-3">Kode</th>
                        <th scope="col" class="px-6 py-3">Jumlah Terjual</th>
                        <th scope="col" class="px-6 py-3">Harga Rata-rata</th>
                        <th scope="col" class="px-6 py-3">Total Penjualan</th>
                        <th scope="col" class="px-6 py-3">Persentase</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topProducts as $product)
                        <tr class="bg-card-light dark:bg-card-dark border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full {{ $product['rank'] <= 3 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }} font-bold">
                                    {{ $product['rank'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-medium text-text-light dark:text-text-dark">
                                {{ $product['product_name'] }}
                            </td>
                            <td class="px-6 py-4 text-text-muted-light dark:text-text-muted-dark">
                                {{ $product['product_code'] }}
                            </td>
                            <td class="px-6 py-4 text-text-light dark:text-text-dark">
                                {{ number_format($product['total_quantity']) }} {{ $product['unit'] }}
                            </td>
                            <td class="px-6 py-4 text-text-light dark:text-text-dark">
                                Rp {{ number_format($product['avg_price'], 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-green-500 font-semibold">
                                Rp {{ number_format($product['total_sales'], 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700 mr-2">
                                        <div class="bg-primary h-2.5 rounded-full" style="width: {{ $product['percentage'] }}%"></div>
                                    </div>
                                    <span class="text-xs font-medium text-text-light dark:text-text-dark">{{ $product['percentage'] }}%</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="bg-card-light dark:bg-card-dark">
                            <td colspan="7" class="px-6 py-8 text-center text-text-muted-light dark:text-text-muted-dark">
                                <div class="flex flex-col items-center">
                                    <span class="material-icons text-4xl mb-2">inventory_2</span>
                                    <p>Belum ada data penjualan untuk periode ini</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <!-- Export Modal -->
    <div id="exportModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900 sm:mx-0 sm:h-10 sm:w-10">
                            <span class="material-icons text-primary">download</span>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                Export Laporan Keuangan
                            </h3>
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Format Export</label>
                                <select id="exportFormat" class="bg-background-light dark:bg-background-dark border border-gray-300 dark:border-gray-600 text-text-light dark:text-text-dark text-sm rounded-lg focus:ring-primary focus:border-primary block w-full p-2.5">
                                    <option value="excel">Excel (.xlsx)</option>
                                    <option value="pdf">PDF (.pdf)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button onclick="processExport()" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:ml-3 sm:w-auto sm:text-sm">
                        Export
                    </button>
                    <button onclick="closeExportModal()" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Batal
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
<script>
let salesTrendChart, storeItemsChart;
let currentPeriod = 'bulanan';
document.addEventListener('DOMContentLoaded', function() {
    initCharts();
    setupPeriodButtons();
});
function initCharts() {
    const isDarkMode = document.documentElement.classList.contains('dark');
    // Sales Trend Chart
    const salesCtx = document.getElementById('salesTrendChart').getContext('2d');
    salesTrendChart = new Chart(salesCtx, {
        type: 'bar',
        data: {
            labels: @json($salesTrend->pluck('period')),
            datasets: [{
                label: 'Penjualan',
                data: @json($salesTrend->pluck('penjualan')),
                backgroundColor: 'rgba(59, 130, 246, 0.5)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 2,
                borderRadius: 5
            }, {
                label: 'Pembelian',
                data: @json($salesTrend->pluck('pembelian')),
                backgroundColor: 'rgba(239, 68, 68, 0.5)',
                borderColor: 'rgba(239, 68, 68, 1)',
                borderWidth: 2,
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        color: isDarkMode ? '#e2e8f0' : '#212529',
                        usePointStyle: true,
                        padding: 15
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += 'Rp ' + (context.parsed.y * 1000000).toLocaleString('id-ID');
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
                        color: isDarkMode ? '#a0aec0' : '#6c757d',
                        callback: function(value) {
                            return value + ' Jt';
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: isDarkMode ? '#a0aec0' : '#6c757d'
                    }
                }
            }
        }
    });
    // Store Items Donut Chart
    const itemsCtx = document.getElementById('storeItemsChart').getContext('2d');
    storeItemsChart = new Chart(itemsCtx, {
        type: 'doughnut',
        data: {
            labels: @json($storeItemsData['categories']->pluck('name')),
            datasets: [{
                data: @json($storeItemsData['categories']->pluck('percentage')),
                backgroundColor: [
                    '#3b82f6',
                    '#14b8a6',
                    '#f97316',
                    '#eab308',
                    '#8b5cf6'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '75%',
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
}
function setupPeriodButtons() {
    document.querySelectorAll('.period-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            currentPeriod = this.dataset.period;
            // Update button styles
            document.querySelectorAll('.period-btn').forEach(b => {
                b.className = 'px-3 py-1 text-sm font-medium text-gray-600 dark:text-gray-300 rounded-md period-btn';
            });
            this.className = 'px-3 py-1 text-sm font-medium rounded-md bg-white dark:bg-gray-800 text-primary shadow period-btn';
            // Load data berdasarkan period
            loadChartData(currentPeriod);
        });
    });
}
function loadChartData(period) {
    fetch(`{{ route('financial-reports.chart-data') }}?chart_type=sales_trend&period=${period}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateSalesTrendChart(data.data);
            }
        })
        .catch(error => {
            console.error('Error loading chart data:', error);
        });
}
function updateSalesTrendChart(data) {
    salesTrendChart.data.labels = data.map(item => item.period);
    salesTrendChart.data.datasets[0].data = data.map(item => item.penjualan);
    salesTrendChart.data.datasets[1].data = data.map(item => item.pembelian);
    salesTrendChart.update();
}
function exportReport() {
    document.getElementById('exportModal').classList.remove('hidden');
}
function closeExportModal() {
    document.getElementById('exportModal').classList.add('hidden');
}
function processExport() {
    const format = document.getElementById('exportFormat').value;
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route('financial-reports.export') }}';
    form.innerHTML = `
        @csrf
        <input type="hidden" name="format" value="${format}">
    `;
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
    closeExportModal();
}

// Handle export dropdown
document.addEventListener('DOMContentLoaded', function() {
    const exportDropdownButton = document.getElementById('export-dropdown-button');
    const exportDropdownMenu = document.getElementById('export-dropdown-menu');
    const exportOptions = document.querySelectorAll('.export-option');

    // Tampilkan/hide dropdown saat tombol diklik
    exportDropdownButton.addEventListener('click', function(event) {
        event.stopPropagation(); // Mencegah event bubble ke document
        exportDropdownMenu.classList.toggle('hidden');
        exportDropdownMenu.classList.toggle('block');
    });

    // Handle klik pada opsi export
    exportOptions.forEach(option => {
        option.addEventListener('click', function(event) {
            event.preventDefault(); // Cegah default behavior <a>
            const format = this.getAttribute('data-format');
            exportTopProducts(format);
            // Opsional: Tutup dropdown setelah export dimulai
            exportDropdownMenu.classList.add('hidden');
            exportDropdownMenu.classList.remove('block');
        });
    });

    // Tutup dropdown jika klik di luar area dropdown
    document.addEventListener('click', function(event) {
        if (exportDropdownMenu && !exportDropdownButton.contains(event.target) && !exportDropdownMenu.contains(event.target)) {
            exportDropdownMenu.classList.add('hidden');
            exportDropdownMenu.classList.remove('block');
        }
    });
});

// Fungsi export produk terlaris (jika ingin tetap digunakan)
function exportTopProducts(format) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route('financial-reports.export') }}';

    // Tambahkan CSRF token
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = '{{ csrf_token() }}';
    form.appendChild(csrfInput);

    // Tambahkan parameter format dan type
    const formatInput = document.createElement('input');
    formatInput.type = 'hidden';
    formatInput.name = 'format';
    formatInput.value = format;
    form.appendChild(formatInput);

    const typeInput = document.createElement('input');
    typeInput.type = 'hidden';
    typeInput.name = 'type';
    typeInput.value = 'top_products';
    form.appendChild(typeInput);

    // Submit form
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}
</script>
@endpush