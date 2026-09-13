@extends('layouts.app')

@section('title', 'Laporan Stok')
@section('page-title', 'Laporan Stok Barang')
@section('page-description', 'Laporan stok barang toko dan status stok berdasarkan kategori')

@section('content')
    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow">
            <p class="text-sm text-text-muted-light dark:text-text-muted-dark">{{ $mainStats['total_products']['label'] }}</p>
            <p class="text-3xl font-bold text-text-light dark:text-text-dark my-2">{{ number_format($mainStats['total_products']['value']) }}</p>
            <p class="text-xs text-green-500">{{ $mainStats['total_products']['growth_text'] }}</p>
        </div>

        <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow">
            <p class="text-sm text-text-muted-light dark:text-text-muted-dark">{{ $mainStats['stock_sold']['label'] }}</p>
            <p class="text-3xl font-bold text-text-light dark:text-text-dark my-2">{{ number_format($mainStats['stock_sold']['value']) }}</p>
            <p class="text-xs {{ $mainStats['stock_sold']['growth'] >= 0 ? 'text-green-500' : 'text-red-500' }}">
                {{ $mainStats['stock_sold']['growth'] >= 0 ? '+' : '' }}{{ $mainStats['stock_sold']['growth_text'] }}
            </p>
        </div>

        <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow">
            <p class="text-sm text-text-muted-light dark:text-text-muted-dark">{{ $mainStats['remaining_stock']['label'] }}</p>
            <p class="text-3xl font-bold text-text-light dark:text-text-dark my-2">{{ number_format($mainStats['remaining_stock']['value']) }}</p>
            <p class="text-xs text-green-500">{{ $mainStats['remaining_stock']['growth_text'] }}</p>
        </div>

        <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow">
            <p class="text-sm text-text-muted-light dark:text-text-muted-dark">{{ $mainStats['low_stock']['label'] }}</p>
            <p class="text-3xl font-bold text-text-light dark:text-text-dark my-2">{{ number_format($mainStats['low_stock']['value']) }}</p>
            <p class="text-xs text-red-500">{{ $mainStats['low_stock']['growth_text'] }}</p>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Stock Status by Category Chart -->
        <div class="lg:col-span-2 bg-card-light dark:bg-card-dark p-6 rounded-lg shadow">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-semibold text-text-light dark:text-text-dark">Status Stok Berdasarkan Kategori</h3>
                <div class="flex space-x-1 bg-gray-100 dark:bg-gray-700 p-1 rounded-lg">
                    <button class="px-3 py-1 text-sm font-medium rounded-md bg-white dark:bg-gray-800 text-primary shadow period-btn" data-period="bulanan">Bulanan</button>
                    <button class="px-3 py-1 text-sm font-medium text-gray-600 dark:text-gray-300 rounded-md period-btn" data-period="mingguan">Mingguan</button>
                    <button class="px-3 py-1 text-sm font-medium text-gray-600 dark:text-gray-300 rounded-md period-btn" data-period="harian">Harian</button>
                </div>
            </div>
            <div class="h-80">
                <canvas id="stockStatusChart"></canvas>
            </div>
        </div>

        <!-- Store Items Donut Chart -->
        <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold text-text-light dark:text-text-dark mb-6">Total Item Toko</h3>
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
                @foreach($storeItemsData['data'] as $item)
                    <div class="flex justify-between items-center text-sm">
                        <div class="flex items-center">
                            <span class="w-3 h-3 rounded-full mr-2" style="background-color: {{ $item['color'] }}"></span>
                            <span class="text-text-muted-light dark:text-text-muted-dark">{{ $item['name'] }}</span>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-text-light dark:text-text-dark">{{ $item['percentage'] }}%</p>
                            <p class="text-xs text-text-muted-light dark:text-text-muted-dark">{{ number_format($item['count']) }} Item</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

   <!-- Top Selling Products Table -->
    <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-semibold text-text-light dark:text-text-dark">Produk Terlaris Bulan Ini</h3>
            <!-- Dropdown Export -->
            <div class="relative" id="export-dropdown-container">
                <button class="bg-primary text-white px-4 py-2 rounded-lg flex items-center text-sm hover:bg-blue-600" id="export-dropdown-button">
                    <span class="material-icons mr-2 text-base">file_download</span>
                    Export
                </button>
                <div class="absolute right-0 mt-2 w-48 bg-card-light dark:bg-card-dark rounded-md shadow-lg py-1 hidden z-10 border border-gray-200 dark:border-gray-700" id="export-dropdown-menu">
                    <a href="#" data-format="csv" class="export-option block px-4 py-2 text-sm text-text-light dark:text-text-dark hover:bg-gray-100 dark:hover:bg-gray-700">Export as CSV</a>
                    <a href="#" data-format="pdf" class="export-option block px-4 py-2 text-sm text-text-light dark:text-text-dark hover:bg-gray-100 dark:hover:bg-gray-700">Export as PDF</a>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-text-muted-light dark:text-text-muted-dark uppercase bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-3">Produk</th>
                        <th scope="col" class="px-6 py-3">Kode</th>
                        <th scope="col" class="px-6 py-3">Kategori</th>
                        <th scope="col" class="px-6 py-3">Stok Saat Ini</th>
                        <th scope="col" class="px-6 py-3">Stok Minimum</th>
                        <th scope="col" class="px-6 py-3">Harga Beli</th>
                        <th scope="col" class="px-6 py-3">Harga Jual</th>
                        <th scope="col" class="px-6 py-3">Status</th>
                        <th scope="col" class="px-6 py-3">Lokasi Barang</th>
                        <th scope="col" class="px-6 py-3">Terakhir Update</th>
                        <th scope="col" class="px-6 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topSellingProducts as $product)
                        <tr class="bg-card-light dark:bg-card-dark border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                            <th scope="row" class="px-6 py-4 font-medium text-text-light dark:text-text-dark whitespace-nowrap">
                                {{ $product['product_name'] }}
                            </th>
                            <td class="px-6 py-4 text-text-muted-light dark:text-text-muted-dark">{{ $product['product_code'] }}</td>
                            <td class="px-6 py-4 text-text-muted-light dark:text-text-muted-dark">{{ $product['category'] }}</td>
                            <td class="px-6 py-4 text-text-light dark:text-text-dark">{{ number_format($product['current_stock']) }} {{ $product['unit'] }}</td>
                            <td class="px-6 py-4 text-text-muted-light dark:text-text-muted-dark">{{ number_format($product['minimum_stock']) }} {{ $product['unit'] }}</td>
                            <td class="px-6 py-4 text-text-light dark:text-text-dark">Rp {{ number_format($product['purchase_price'], 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-text-light dark:text-text-dark">Rp {{ number_format($product['selling_price'], 0, ',', '.') }}</td>
                            <td class="px-6 py-4">
                                @php
                                    $statusColors = [
                                        'normal' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                        'menipis' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                        'kritis' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                                        'habis' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
                                    ];
                                    $statusText = [
                                        'normal' => 'Tersedia',
                                        'menipis' => 'Menipis',
                                        'kritis' => 'Kritis',
                                        'habis' => 'Habis'
                                    ];
                                @endphp
                                <span class="text-xs font-medium px-2.5 py-0.5 rounded-full {{ $statusColors[$product['stock_status']] }}">
                                    {{ $statusText[$product['stock_status']] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-text-muted-light dark:text-text-muted-dark">{{ $product['location'] ?: '-' }}</td>
                            <td class="px-6 py-4 text-text-muted-light dark:text-text-muted-dark">
                                {{ \Carbon\Carbon::parse($product['last_update'])->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex space-x-2">
                                    <button onclick="viewProduct({{ $product['rank'] }})" class="px-3 py-1 bg-primary text-white text-xs rounded hover:bg-blue-600">
                                        Lihat
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="bg-card-light dark:bg-card-dark">
                            <td colspan="11" class="px-6 py-8 text-center text-text-muted-light dark:text-text-muted-dark">
                                <div class="flex flex-col items-center">
                                    <span class="material-icons text-4xl mb-2">inventory_2</span>
                                    <p>Belum ada data penjualan untuk bulan ini</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($topSellingProducts->count() > 0)
            <div class="flex justify-between items-center mt-6 text-sm">
                <p class="text-text-muted-light dark:text-text-muted-dark">Menampilkan {{ $topSellingProducts->count() }} produk terlaris</p>
            </div>
        @endif
    </div>

    <!-- View Product Modal -->
    <div id="viewModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-card-light dark:bg-card-dark rounded-lg shadow-xl max-w-2xl w-full">
                <div class="flex justify-between items-center p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-xl font-semibold text-text-light dark:text-text-dark">Detail Produk</h3>
                    <button onclick="closeViewModal()" class="text-text-muted-light dark:text-text-muted-dark hover:text-text-light dark:hover:text-text-dark">
                        <span class="material-icons">close</span>
                    </button>
                </div>
                <div id="viewModalContent" class="p-6">
                    <!-- Content will be loaded dynamically -->
                </div>
                <div class="flex justify-end p-6 border-t border-gray-200 dark:border-gray-700">
                    <button onclick="closeViewModal()" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-text-light dark:text-text-dark rounded hover:bg-gray-300 dark:hover:bg-gray-600">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
let stockStatusChart, storeItemsChart;
let currentPeriod = 'bulanan';

document.addEventListener('DOMContentLoaded', function() {
    initCharts();
    setupPeriodButtons();
});

function initCharts() {
    // Stock Status by Category Chart
    const stockCtx = document.getElementById('stockStatusChart').getContext('2d');
    const isDarkMode = document.documentElement.classList.contains('dark');
    
    const stockData = @json($stockStatusByCategory);
    
    stockStatusChart = new Chart(stockCtx, {
        type: 'bar',
        data: {
            labels: stockData.map(item => item.category_name),
            datasets: [
                {
                    label: 'Normal',
                    data: stockData.map(item => item.normal),
                    backgroundColor: 'rgba(16, 185, 129, 0.5)',
                    borderColor: 'rgba(16, 185, 129, 1)',
                    borderWidth: 2,
                    borderRadius: 5
                },
                {
                    label: 'Menipis',
                    data: stockData.map(item => item.menipis),
                    backgroundColor: 'rgba(59, 130, 246, 0.5)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 2,
                    borderRadius: 5
                },
                {
                    label: 'Kritis',
                    data: stockData.map(item => item.kritis),
                    backgroundColor: 'rgba(245, 158, 11, 0.5)',
                    borderColor: 'rgba(245, 158, 11, 1)',
                    borderWidth: 2,
                    borderRadius: 5
                },
                {
                    label: 'Habis',
                    data: stockData.map(item => item.habis),
                    backgroundColor: 'rgba(239, 68, 68, 0.5)',
                    borderColor: 'rgba(239, 68, 68, 1)',
                    borderWidth: 2,
                    borderRadius: 5
                }
            ]
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
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    stacked: false,
                    grid: {
                        color: 'rgba(200, 200, 200, 0.2)'
                    },
                    ticks: {
                        color: isDarkMode ? '#a0aec0' : '#6c757d'
                    }
                },
                x: {
                    stacked: false,
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
    const storeData = @json($storeItemsData['data']);
    
    storeItemsChart = new Chart(itemsCtx, {
        type: 'doughnut',
        data: {
            labels: storeData.map(item => item.name),
            datasets: [{
                data: storeData.map(item => item.percentage),
                backgroundColor: storeData.map(item => item.color),
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
                            const item = storeData[context.dataIndex];
                            return label + ': ' + value + '% (' + item.count + ' item)';
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
            
            document.querySelectorAll('.period-btn').forEach(b => {
                b.className = 'px-3 py-1 text-sm font-medium text-gray-600 dark:text-gray-300 rounded-md period-btn';
            });
            this.className = 'px-3 py-1 text-sm font-medium rounded-md bg-white dark:bg-gray-800 text-primary shadow period-btn';
            
            loadChartData(currentPeriod);
        });
    });
}

function loadChartData(period) {
    fetch(`{{ route('stock-reports.chart-data') }}?period=${period}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStockChart(data.data);
            }
        })
        .catch(error => {
            console.error('Error loading chart data:', error);
        });
}

function updateStockChart(data) {
    // Update chart dengan data baru
    stockStatusChart.data.labels = data.map(item => item.period);
    stockStatusChart.data.datasets[0].data = data.map(item => item.stock_in);
    stockStatusChart.data.datasets[1].data = data.map(item => item.stock_out);
    stockStatusChart.update();
}

function exportReport(format) {
    const form = document.createElement('form');
    form.method = 'GET';
    form.action = '{{ route('stock-reports.export') }}';
    form.innerHTML = `
        <input type="hidden" name="format" value="${format}">
        <input type="hidden" name="stock_status" value="all">
    `;
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

function viewProduct(rank) {
    const products = @json($topSellingProducts);
    const product = products.find(p => p.rank === rank);
    
    if (!product) return;
    
    const statusText = {
        'normal': 'Tersedia',
        'menipis': 'Menipis',
        'kritis': 'Kritis',
        'habis': 'Habis'
    };
    
    const content = `
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-text-muted-light dark:text-text-muted-dark mb-1">Nama Produk</label>
                <p class="text-text-light dark:text-text-dark">${product.product_name}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-text-muted-light dark:text-text-muted-dark mb-1">Kode Produk</label>
                <p class="text-text-light dark:text-text-dark">${product.product_code}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-text-muted-light dark:text-text-muted-dark mb-1">Kategori</label>
                <p class="text-text-light dark:text-text-dark">${product.category}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-text-muted-light dark:text-text-muted-dark mb-1">Stok Saat Ini</label>
                <p class="text-text-light dark:text-text-dark">${product.current_stock.toLocaleString('id-ID')} ${product.unit}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-text-muted-light dark:text-text-muted-dark mb-1">Stok Minimum</label>
                <p class="text-text-light dark:text-text-dark">${product.minimum_stock.toLocaleString('id-ID')} ${product.unit}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-text-muted-light dark:text-text-muted-dark mb-1">Harga Beli</label>
                <p class="text-text-light dark:text-text-dark">Rp ${product.purchase_price.toLocaleString('id-ID')}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-text-muted-light dark:text-text-muted-dark mb-1">Harga Jual</label>
                <p class="text-text-light dark:text-text-dark">Rp ${product.selling_price.toLocaleString('id-ID')}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-text-muted-light dark:text-text-muted-dark mb-1">Status</label>
                <p class="text-text-light dark:text-text-dark">${statusText[product.stock_status]}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-text-muted-light dark:text-text-muted-dark mb-1">Lokasi Barang</label>
                <p class="text-text-light dark:text-text-dark">${product.location || '-'}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-text-muted-light dark:text-text-muted-dark mb-1">Total Terjual</label>
                <p class="text-text-light dark:text-text-dark">${product.total_sold.toLocaleString('id-ID')} ${product.unit}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-text-muted-light dark:text-text-muted-dark mb-1">Nilai Penjualan</label>
                <p class="text-green-500 font-semibold">Rp ${product.sales_value.toLocaleString('id-ID')}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-text-muted-light dark:text-text-muted-dark mb-1">Terakhir Update</label>
                <p class="text-text-light dark:text-text-dark">${new Date(product.last_update).toLocaleDateString('id-ID')}</p>
            </div>
        </div>
    `;
    
    document.getElementById('viewModalContent').innerHTML = content;
    document.getElementById('viewModal').classList.remove('hidden');
}

function closeViewModal() {
    document.getElementById('viewModal').classList.add('hidden');
}

function editProduct(rank) {
    // Redirect to edit page or open edit modal
    const products = @json($topSellingProducts);
    const product = products.find(p => p.rank === rank);
    
    if (product) {
        window.location.href = `{{ url('stock') }}/${product.product_code}/edit`;
    }
}

// --- Tambahkan script baru untuk dropdown export ---
document.addEventListener('DOMContentLoaded', function() {
    const exportDropdownButton = document.getElementById('export-dropdown-button');
    const exportDropdownMenu = document.getElementById('export-dropdown-menu');
    const exportOptions = document.querySelectorAll('.export-option');

    // Toggle dropdown saat tombol diklik
    exportDropdownButton.addEventListener('click', function(event) {
        event.stopPropagation(); // Mencegah event bubble ke document
        exportDropdownMenu.classList.toggle('hidden');
    });

    // Handle klik pada opsi export
    exportOptions.forEach(option => {
        option.addEventListener('click', function(event) {
            event.preventDefault(); // Cegah default behavior <a>
            const format = this.getAttribute('data-format');
            exportReport(format); // Panggil fungsi export dengan format yang dipilih
            // Optional: Tutup dropdown setelah export dimulai
            exportDropdownMenu.classList.add('hidden');
        });
    });

    // Tutup dropdown jika klik di luar area dropdown
    document.addEventListener('click', function(event) {
        if (exportDropdownButton && exportDropdownMenu) {
            if (!exportDropdownButton.contains(event.target) && !exportDropdownMenu.contains(event.target)) {
                exportDropdownMenu.classList.add('hidden');
            }
        }
    });
});

// Perbarui fungsi exportReport untuk menerima parameter format
function exportReport(format) {
    const form = document.createElement('form');
    form.method = 'POST'; // Gunakan POST untuk keamanan dan mengirim CSRF
    form.action = '{{ route('stock-reports.export') }}';

    // Tambahkan CSRF token
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = '{{ csrf_token() }}';
    form.appendChild(csrfInput);

    // Tambahkan parameter format (csv, excel, pdf)
    const formatInput = document.createElement('input');
    formatInput.type = 'hidden';
    formatInput.name = 'format';
    formatInput.value = format;
    form.appendChild(formatInput);

    // Tambahkan parameter stock_status jika diperlukan
    const stockStatusInput = document.createElement('input');
    stockStatusInput.type = 'hidden';
    stockStatusInput.name = 'stock_status';
    stockStatusInput.value = 'all';
    form.appendChild(stockStatusInput);

    // Submit form
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}
</script>
@endpush