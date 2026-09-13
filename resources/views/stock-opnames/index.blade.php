@extends('layouts.app')

@section('title', 'Stok Opname')
@section('page-title', 'Stok Opname')
@section('page-description', 'Kelola data stok opname barang')

@section('content')
<div class="space-y-6">
    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white dark:bg-card-dark rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-text-muted-light dark:text-text-muted-dark">Total Opname</p>
                    <h3 class="text-2xl font-bold text-text-light dark:text-text-dark mt-1">{{ $stats['total_opnames'] }}</h3>
                </div>
                <div class="w-14 h-14 flex items-center justify-center bg-blue-100 dark:bg-blue-900 rounded-full">
                    <span class="material-icons text-blue-600 dark:text-blue-300 text-3xl">fact_check</span>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-card-dark rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-text-muted-light dark:text-text-muted-dark">Menunggu Persetujuan</p>
                    <h3 class="text-2xl font-bold text-text-light dark:text-text-dark mt-1">{{ $stats['pending'] }}</h3>
                </div>
                <div class="w-14 h-14 flex items-center justify-center bg-yellow-100 dark:bg-yellow-900 rounded-full">
                    <span class="material-icons text-yellow-600 dark:text-yellow-300 text-3xl">pending</span>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-card-dark rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-text-muted-light dark:text-text-muted-dark">Disetujui</p>
                    <h3 class="text-2xl font-bold text-text-light dark:text-text-dark mt-1">{{ $stats['approved'] }}</h3>
                </div>
                <div class="w-14 h-14 flex items-center justify-center bg-green-100 dark:bg-green-900 rounded-full">
                    <span class="material-icons text-green-600 dark:text-green-300 text-3xl">check_circle</span>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-card-dark rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-text-muted-light dark:text-text-muted-dark">Total Selisih</p>
                    <h3 class="text-2xl font-bold text-text-light dark:text-text-dark mt-1">{{ number_format($stats['total_difference']) }}</h3>
                </div>
                <div class="w-14 h-14 flex items-center justify-center bg-red-100 dark:bg-red-900 rounded-full">
                    <span class="material-icons text-red-600 dark:text-red-300 text-3xl">sync_problem</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter and Actions -->
    <div class="bg-white dark:bg-card-dark rounded-lg shadow p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex-1">
                <form method="GET" action="{{ route('stock-opnames.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <!-- Search -->
                    <div>
                        <input type="text" name="search" value="{{ request('search') }}" 
                               placeholder="Cari nomor opname atau produk..." 
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white">
                    </div>

                    <!-- Category Filter -->
                    <div>
                        <select name="category_id" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white">
                            <option value="">Semua Kategori</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <select name="status" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white">
                            <option value="">Semua Status</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Menunggu</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Disetujui</option>
                        </select>
                    </div>

                    <!-- Date Filter -->
                    <div>
                        <input type="date" name="start_date" value="{{ request('start_date') }}" 
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white">
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="px-3 py-2 bg-primary text-white rounded-lg hover:bg-blue-600 transition">
                            <span class="material-icons text-sm">search</span>
                        </button>
                        <a href="{{ route('stock-opnames.index') }}" class="px-3 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                            <span class="material-icons text-sm">refresh</span>
                        </a>
                    </div>
                </form>
            </div>

            <div>
                <button onclick="openCreateModal()" class="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-600 transition">
                    <span class="material-icons">add</span>
                    <span>Tambah Opname</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Stock Opname Table -->
    <div class="bg-white dark:bg-card-dark rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">No. Opname</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Produk</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Stok Sistem</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Stok Fisik</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Selisih</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Petugas</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($opnames as $opname)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium text-text-light dark:text-text-dark">{{ $opname->opname_number }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-text-light dark:text-text-dark">{{ $opname->opname_date->format('d/m/Y') }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div>
                                    <p class="text-sm font-medium text-text-light dark:text-text-dark">{{ $opname->product->name }}</p>
                                    <p class="text-xs text-text-muted-light dark:text-text-muted-dark">{{ $opname->product->code }} - {{ $opname->product->category->name }}</p>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-text-light dark:text-text-dark">{{ number_format($opname->system_stock) }} {{ $opname->product->unit }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-text-light dark:text-text-dark">{{ number_format($opname->physical_stock) }} {{ $opname->product->unit }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium {{ $opname->difference == 0 ? 'text-green-600' : ($opname->difference > 0 ? 'text-blue-600' : 'text-red-600') }}">
                                    {{ $opname->difference > 0 ? '+' : '' }}{{ number_format($opname->difference) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($opname->status === 'pending')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                        Menunggu
                                    </span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        Disetujui
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-text-light dark:text-text-dark">{{ $opname->user->name ?? '-' }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    @if($opname->status === 'pending')
                                        <button onclick="approveOpname({{ $opname->id }})" class="px-3 py-1 text-xs font-medium text-white bg-blue-500 hover:bg-blue-600 rounded transition">
                                            Sesuaikan
                                        </button>
                                        <button onclick="editOpname({{ $opname->id }})" class="px-3 py-1 text-xs font-medium text-white bg-yellow-500 hover:bg-yellow-600 rounded transition">
                                            Edit
                                        </button>
                                        <button onclick="deleteOpname({{ $opname->id }})" class="px-3 py-1 text-xs font-medium text-white bg-red-500 hover:bg-red-600 rounded transition">
                                            Hapus
                                        </button>
                                    @else
                                        <button onclick="viewOpname({{ $opname->id }})" class="px-3 py-1 text-xs font-medium text-white bg-blue-500 hover:bg-gray-600 rounded transition">
                                            Lihat Detail
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-8 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <span class="material-icons text-6xl text-gray-300 dark:text-gray-600 mb-2">inbox</span>
                                    <p class="text-gray-500 dark:text-gray-400">Tidak ada data stok opname</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($opnames->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $opnames->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Create/Edit Modal -->
<div id="opnameModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-card-dark rounded-lg shadow-xl w-full max-w-2xl">
        <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-xl font-semibold text-text-light dark:text-text-dark" id="modalTitle">Tambah Stok Opname</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <span class="material-icons">close</span>
            </button>
        </div>

        <form id="opnameForm" class="p-6 space-y-4">
            @csrf
            <input type="hidden" id="opnameId" name="opname_id">

            <div>
                <label class="block text-sm font-medium text-text-light dark:text-text-dark mb-2">Produk *</label>
                <select id="product_id" name="product_id" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white">
                    <option value="">Pilih Produk</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" data-stock="{{ $product->current_stock }}" data-unit="{{ $product->unit }}">
                            {{ $product->code }} - {{ $product->name }} (Stok: {{ $product->current_stock }} {{ $product->unit }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-text-light dark:text-text-dark mb-2">Tanggal Opname *</label>
                    <input type="date" id="opname_date" name="opname_date" value="{{ date('Y-m-d') }}" required 
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block text-sm font-medium text-text-light dark:text-text-dark mb-2">Stok Sistem</label>
                    <input type="text" id="system_stock_display" readonly 
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-100 dark:bg-gray-800 dark:text-white" 
                           placeholder="Pilih produk dulu">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-text-light dark:text-text-dark mb-2">Stok Fisik *</label>
                <input type="number" id="physical_stock" name="physical_stock" min="0" required 
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white" 
                       placeholder="Masukkan hasil perhitungan fisik">
            </div>

            <div>
                <label class="block text-sm font-medium text-text-light dark:text-text-dark mb-2">Catatan</label>
                <textarea id="notes" name="notes" rows="3" 
                          class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white" 
                          placeholder="Tambahkan catatan jika diperlukan"></textarea>
            </div>

            <div id="differenceAlert" class="hidden p-4 rounded-lg">
                <div class="flex items-center gap-2">
                    <span class="material-icons">info</span>
                    <div>
                        <p class="font-medium">Selisih Terdeteksi</p>
                        <p class="text-sm" id="differenceText"></p>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <button type="button" onclick="closeModal()" class="px-6 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                    Batal
                </button>
                <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-blue-600">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Detail Modal -->
<div id="detailModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-card-dark rounded-lg shadow-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700 sticky top-0 bg-white dark:bg-card-dark">
            <h3 class="text-xl font-semibold text-text-light dark:text-text-dark">Detail Stok Opname</h3>
            <button onclick="closeDetailModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <span class="material-icons">close</span>
            </button>
        </div>

        <div class="p-6 space-y-6">
            <!-- Header Info -->
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="text-sm font-medium text-text-muted-light dark:text-text-muted-dark">No. Opname</label>
                    <p class="text-lg font-semibold text-text-light dark:text-text-dark mt-1" id="detail_opname_number">-</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-text-muted-light dark:text-text-muted-dark">Tanggal Opname</label>
                    <p class="text-lg font-semibold text-text-light dark:text-text-dark mt-1" id="detail_opname_date">-</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-text-muted-light dark:text-text-muted-dark">Status</label>
                    <p class="mt-1" id="detail_status">-</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-text-muted-light dark:text-text-muted-dark">Petugas</label>
                    <p class="text-lg font-semibold text-text-light dark:text-text-dark mt-1" id="detail_user">-</p>
                </div>
            </div>

            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <h4 class="text-lg font-semibold text-text-light dark:text-text-dark mb-4">Informasi Produk</h4>
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-text-muted-light dark:text-text-muted-dark">Kode Produk</span>
                        <span class="text-sm font-medium text-text-light dark:text-text-dark" id="detail_product_code">-</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-text-muted-light dark:text-text-muted-dark">Nama Produk</span>
                        <span class="text-sm font-medium text-text-light dark:text-text-dark" id="detail_product_name">-</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-text-muted-light dark:text-text-muted-dark">Kategori</span>
                        <span class="text-sm font-medium text-text-light dark:text-text-dark" id="detail_category">-</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-text-muted-light dark:text-text-muted-dark">Satuan</span>
                        <span class="text-sm font-medium text-text-light dark:text-text-dark" id="detail_unit">-</span>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <h4 class="text-lg font-semibold text-text-light dark:text-text-dark mb-4">Perbandingan Stok</h4>
                <div class="grid grid-cols-3 gap-4">
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 text-center">
                        <p class="text-sm text-blue-600 dark:text-blue-400 mb-2">Stok Sistem</p>
                        <p class="text-2xl font-bold text-blue-700 dark:text-blue-300" id="detail_system_stock">0</p>
                        <p class="text-xs text-blue-600 dark:text-blue-400 mt-1" id="detail_system_unit">-</p>
                    </div>
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 text-center">
                        <p class="text-sm text-green-600 dark:text-green-400 mb-2">Stok Fisik</p>
                        <p class="text-2xl font-bold text-green-700 dark:text-green-300" id="detail_physical_stock">0</p>
                        <p class="text-xs text-green-600 dark:text-green-400 mt-1" id="detail_physical_unit">-</p>
                    </div>
                    <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 text-center">
                        <p class="text-sm text-red-600 dark:text-red-400 mb-2">Selisih</p>
                        <p class="text-2xl font-bold text-red-700 dark:text-red-300" id="detail_difference">0</p>
                        <p class="text-xs text-red-600 dark:text-red-400 mt-1" id="detail_difference_desc">-</p>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-200 dark:border-gray-700 pt-6" id="detail_notes_section">
                <h4 class="text-lg font-semibold text-text-light dark:text-text-dark mb-2">Catatan</h4>
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                    <p class="text-sm text-text-light dark:text-text-dark" id="detail_notes">-</p>
                </div>
            </div>

            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <div class="flex items-center justify-between text-xs text-text-muted-light dark:text-text-muted-dark">
                    <span>Dibuat: <span id="detail_created_at">-</span></span>
                    <span>Terakhir Diupdate: <span id="detail_updated_at">-</span></span>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3 p-6 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
            <button onclick="closeDetailModal()" class="px-6 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                Tutup
            </button>
            <!-- <button onclick="printDetail()" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-blue-600">
                <span class="flex items-center gap-2">
                    <span class="material-icons text-sm">print</span>
                    <span>Cetak</span>
                </span>
            </button> -->
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Product selection handler
    document.getElementById('product_id').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const systemStock = selectedOption.dataset.stock || '';
        const unit = selectedOption.dataset.unit || '';
        
        document.getElementById('system_stock_display').value = systemStock ? `${systemStock} ${unit}` : '';
        checkDifference();
    });

    // Physical stock input handler
    document.getElementById('physical_stock').addEventListener('input', checkDifference);

    function checkDifference() {
        const productSelect = document.getElementById('product_id');
        const selectedOption = productSelect.options[productSelect.selectedIndex];
        const systemStock = parseInt(selectedOption.dataset.stock) || 0;
        const physicalStock = parseInt(document.getElementById('physical_stock').value) || 0;
        
        const difference = physicalStock - systemStock;

        const alert = document.getElementById('differenceAlert');
        const diffText = document.getElementById('differenceText');

        if (physicalStock > 0 && difference !== 0) {
            alert.classList.remove('hidden');
            if (difference < 0) {
                // Negatif = Kekurangan
                alert.className = 'p-4 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200';
                diffText.textContent = `Stok fisik kurang ${Math.abs(difference)} unit dari sistem`;
            } else {
                // Positif = Kelebihan
                alert.className = 'p-4 rounded-lg bg-blue-50 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200';
                diffText.textContent = `Stok fisik lebih ${Math.abs(difference)} unit dari sistem`;
            }
        } else {
            alert.classList.add('hidden');
        }
    }

    function openCreateModal() {
        document.getElementById('modalTitle').textContent = 'Tambah Stok Opname';
        document.getElementById('opnameForm').reset();
        document.getElementById('opnameId').value = '';
        document.getElementById('opname_date').value = '{{ date("Y-m-d") }}';
        document.getElementById('system_stock_display').value = '';
        document.getElementById('differenceAlert').classList.add('hidden');
        document.getElementById('opnameModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('opnameModal').classList.add('hidden');
    }

    // Form submission
    document.getElementById('opnameForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const opnameId = document.getElementById('opnameId').value;
        const url = opnameId ? `/stock-opnames/${opnameId}` : '{{ route("stock-opnames.store") }}';
        const method = opnameId ? 'PUT' : 'POST';

        try {
            const response = await fetch(url, {
                method: method,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(Object.fromEntries(formData))
            });

            const result = await response.json();

            if (result.success) {
                closeModal();
                window.location.reload();
            } else {
                alert(result.message || 'Terjadi kesalahan');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat menyimpan data');
        }
    });

    async function approveOpname(id) {
        if (!confirm('Apakah Anda yakin ingin menyetujui stok opname ini? Stok produk akan disesuaikan secara otomatis.')) {
            return;
        }

        try {
            const response = await fetch(`/stock-opnames/${id}/approve`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();

            if (result.success) {
                window.location.reload();
            } else {
                alert(result.message || 'Terjadi kesalahan');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat approve stok opname');
        }
    }

    async function deleteOpname(id) {
        if (!confirm('Apakah Anda yakin ingin menghapus stok opname ini?')) {
            return;
        }

        try {
            const response = await fetch(`/stock-opnames/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();

            if (result.success) {
                window.location.reload();
            } else {
                alert(result.message || 'Terjadi kesalahan');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat menghapus data');
        }
    }

    async function editOpname(id) {
        try {
            const response = await fetch(`/stock-opnames/${id}/edit`);
            const result = await response.json();

            if (result.success) {
                document.getElementById('modalTitle').textContent = 'Edit Stok Opname';
                document.getElementById('opnameId').value = result.data.id;
                document.getElementById('product_id').value = result.data.product_id;
                document.getElementById('opname_date').value = result.data.opname_date;
                document.getElementById('physical_stock').value = result.data.physical_stock;
                document.getElementById('notes').value = result.data.notes || '';
                
                // Trigger change to update system stock display
                document.getElementById('product_id').dispatchEvent(new Event('change'));
                
                document.getElementById('opnameModal').classList.remove('hidden');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat mengambil data');
        }
    }

    function viewOpname(id) {
        fetchOpnameDetail(id);
    }

    async function fetchOpnameDetail(id) {
        try {
            const response = await fetch(`/stock-opnames/${id}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();

            if (result.success) {
                const data = result.data;
                
                // Header Info
                document.getElementById('detail_opname_number').textContent = data.opname_number;
                document.getElementById('detail_opname_date').textContent = formatDate(data.opname_date);
                document.getElementById('detail_user').textContent = data.user?.name || '-';
                
                // Status Badge
                const statusEl = document.getElementById('detail_status');
                if (data.status === 'pending') {
                    statusEl.innerHTML = '<span class="px-3 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">Pending</span>';
                } else if (data.status === 'approved') {
                    statusEl.innerHTML = '<span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Approved</span>';
                } else {
                    statusEl.innerHTML = '<span class="px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Rejected</span>';
                }
                
                // Product Info
                document.getElementById('detail_product_code').textContent = data.product.code;
                document.getElementById('detail_product_name').textContent = data.product.name;
                document.getElementById('detail_category').textContent = data.product.category.name;
                document.getElementById('detail_unit').textContent = data.product.unit;
                
                // Stock Comparison
                document.getElementById('detail_system_stock').textContent = formatNumber(data.system_stock);
                document.getElementById('detail_system_unit').textContent = data.product.unit;
                document.getElementById('detail_physical_stock').textContent = formatNumber(data.physical_stock);
                document.getElementById('detail_physical_unit').textContent = data.product.unit;
                
                // Difference
                const difference = data.difference;
                const diffEl = document.getElementById('detail_difference');
                diffEl.textContent = (difference > 0 ? '+' : '') + formatNumber(difference);
                
                if (difference === 0) {
                    diffEl.className = 'text-2xl font-bold text-green-700 dark:text-green-300';
                    document.getElementById('detail_difference_desc').textContent = 'Stok Sesuai';
                } else if (difference > 0) {
                    diffEl.className = 'text-2xl font-bold text-red-700 dark:text-red-300';
                    document.getElementById('detail_difference_desc').textContent = 'Kurang dari Sistem';
                } else {
                    diffEl.className = 'text-2xl font-bold text-blue-700 dark:text-blue-300';
                    document.getElementById('detail_difference_desc').textContent = 'Lebih dari Sistem';
                }
                
                // Notes
                if (data.notes) {
                    document.getElementById('detail_notes').textContent = data.notes;
                    document.getElementById('detail_notes_section').classList.remove('hidden');
                } else {
                    document.getElementById('detail_notes').textContent = 'Tidak ada catatan';
                }
                
                // Timestamps
                document.getElementById('detail_created_at').textContent = formatDateTime(data.created_at);
                document.getElementById('detail_updated_at').textContent = formatDateTime(data.updated_at);
                
                // Show modal
                document.getElementById('detailModal').classList.remove('hidden');
            } else {
                alert('Gagal mengambil detail opname');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat mengambil detail: ' + error.message);
        }
    }

    function closeDetailModal() {
        document.getElementById('detailModal').classList.add('hidden');
    }

    function printDetail() {
        // Implementasi print - bisa menggunakan window.print() atau redirect ke halaman print
        alert('Fitur print akan segera ditambahkan');
    }

    function formatNumber(num) {
        return new Intl.NumberFormat('id-ID').format(num);
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'long',
            year: 'numeric'
        });
    }

    function formatDateTime(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
</script>
@endpush
@endsection