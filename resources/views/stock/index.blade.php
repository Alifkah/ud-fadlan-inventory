@extends('layouts.app')

@section('title', 'Stok Barang')
@section('page-title', 'Stok Barang')
@section('page-description', 'Kelola dan pantau stok barang di toko Anda')

@section('content')
<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow-sm border border-border-light dark:border-border-dark">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-text-muted-light dark:text-text-muted-dark text-sm">Total Produk</p>
                <p class="text-3xl font-bold text-text-light dark:text-text-dark mt-2">{{ number_format($stockStats['total_products']) }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                <span class="material-icons text-blue-600 dark:text-blue-300">inventory_2</span>
            </div>
        </div>
    </div>

    <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow-sm border border-border-light dark:border-border-dark">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-text-muted-light dark:text-text-muted-dark text-sm">Stok Habis</p>
                <p class="text-3xl font-bold text-text-light dark:text-text-dark mt-2">{{ number_format($stockStats['out_of_stock']) }}</p>
            </div>
            <div class="w-12 h-12 bg-red-100 dark:bg-red-900 rounded-full flex items-center justify-center">
                <span class="material-icons text-red-600 dark:text-red-300">remove_shopping_cart</span>
            </div>
        </div>
        @if($stockStats['out_of_stock'] > 0)
            <p class="text-red-500 text-sm mt-2">Perlu restok segera</p>
        @endif
    </div>

    <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow-sm border border-border-light dark:border-border-dark">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-text-muted-light dark:text-text-muted-dark text-sm">Stok Rendah</p>
                <p class="text-3xl font-bold text-text-light dark:text-text-dark mt-2">{{ number_format($stockStats['low_stock']) }}</p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900 rounded-full flex items-center justify-center">
                <span class="material-icons text-yellow-600 dark:text-yellow-300">warning</span>
            </div>
        </div>
        @if($stockStats['low_stock'] > 0)
            <p class="text-orange-500 text-sm mt-2">Perlu perhatian</p>
        @endif
    </div>

    <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow-sm border border-border-light dark:border-border-dark">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-text-muted-light dark:text-text-muted-dark text-sm">Nilai Stok</p>
                <p class="text-3xl font-bold text-text-light dark:text-text-dark mt-2">Rp {{ number_format($stockStats['total_stock_value'], 0, ',', '.') }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                <span class="material-icons text-green-600 dark:text-green-300">attach_money</span>
            </div>
        </div>
    </div>
</div>

<!-- Filters and Actions -->
<div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow-sm border border-border-light dark:border-border-dark mb-6">
    <div class="flex flex-col md:flex-row items-center justify-between space-y-3 md:space-y-0 md:space-x-4 mb-4">
        <!-- Search -->
        <div class="w-full md:w-1/3">
            <form method="GET" action="{{ route('stock.index') }}" class="flex items-center">
                <label for="search" class="sr-only">Search</label>
                <div class="relative w-full">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <span class="material-icons text-text-muted-light dark:text-text-muted-dark">search</span>
                    </div>
                    <input type="text" id="search" name="search" value="{{ request('search') }}" 
                           class="bg-background-light border border-border-light text-text-light text-sm rounded-lg focus:ring-primary focus:border-primary block w-full pl-10 p-2.5 dark:bg-background-dark dark:border-border-dark dark:text-text-dark" 
                           placeholder="Cari berdasarkan nama atau kode produk">
                    @foreach(['category_id', 'stock_status'] as $param)
                        @if(request($param))
                            <input type="hidden" name="{{ $param }}" value="{{ request($param) }}">
                        @endif
                    @endforeach
                </div>
                <button type="submit" class="ml-2 p-2.5 text-sm font-medium text-white bg-primary rounded-lg hover:bg-primary/90 focus:ring-4 focus:outline-none focus:ring-primary/50">
                    <span class="material-icons">search</span>
                </button>
            </form>
        </div>

        <!-- Actions -->
        <div class="w-full md:w-auto flex flex-col md:flex-row space-y-2 md:space-y-0 items-stretch md:items-center justify-end md:space-x-3 flex-shrink-0">
            <!-- Category Filter -->
            <select name="category_id" id="categoryFilter" onchange="applyFilter()" 
                    class="w-full md:w-auto flex items-center justify-center py-2 px-4 text-sm font-medium text-text-light dark:text-text-dark focus:outline-none bg-card-light rounded-lg border border-border-light hover:bg-gray-100 focus:z-10 focus:ring-4 focus:ring-gray-200 dark:bg-card-dark dark:border-border-dark dark:hover:bg-gray-700">
                <option value="">Semua Kategori</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>

            <!-- Stock Status Filter -->
            <select name="stock_status" id="stockStatusFilter" onchange="applyFilter()" 
                    class="w-full md:w-auto flex items-center justify-center py-2 px-4 text-sm font-medium text-text-light dark:text-text-dark focus:outline-none bg-card-light rounded-lg border border-border-light hover:bg-gray-100 focus:z-10 focus:ring-4 focus:ring-gray-200 dark:bg-card-dark dark:border-border-dark dark:hover:bg-gray-700">
                <option value="">Semua Status</option>
                <option value="normal" {{ request('stock_status') == 'normal' ? 'selected' : '' }}>Normal</option>
                <option value="menipis" {{ request('stock_status') == 'menipis' ? 'selected' : '' }}>Menipis</option>
                <option value="kritis" {{ request('stock_status') == 'kritis' ? 'selected' : '' }}>Kritis</option>
                <option value="habis" {{ request('stock_status') == 'habis' ? 'selected' : '' }}>Habis</option>
            </select>

            <!-- Add Product Button -->
            <button onclick="openAddProductModal()" 
                    class="w-full md:w-auto flex items-center justify-center py-2 px-4 text-sm font-medium text-white bg-primary rounded-lg hover:bg-primary/90 focus:outline-none focus:ring-4 focus:ring-primary/50">
                <span class="material-icons mr-2">add</span>
                Tambah Produk
            </button>

            <!-- Export Button -->
            <button onclick="exportStock()" 
                    class="w-full md:w-auto flex items-center justify-center py-2 px-4 text-sm font-medium text-text-light dark:text-text-dark focus:outline-none bg-card-light rounded-lg border border-border-light hover:bg-gray-100 focus:z-10 focus:ring-4 focus:ring-gray-200 dark:bg-card-dark dark:border-border-dark dark:hover:bg-gray-700">
                <span class="material-icons mr-2">download</span>
                Export
            </button>
        </div>
    </div>

    <!-- Products Table -->
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-text-muted-light dark:text-text-muted-dark">
            <thead class="text-xs text-text-muted-light dark:text-text-muted-dark uppercase bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        <input type="checkbox" id="selectAll" class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary dark:focus:ring-primary dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                    </th>
                    <th scope="col" class="px-6 py-3">Kode</th>
                    <th scope="col" class="px-6 py-3">Nama Produk</th>
                    <th scope="col" class="px-6 py-3">Kategori</th>
                    <th scope="col" class="px-6 py-3">Stok Saat Ini</th>
                    <th scope="col" class="px-6 py-3">Stok Minimum</th>
                    <th scope="col" class="px-6 py-3">Harga Beli</th>
                    <th scope="col" class="px-6 py-3">Harga Jual</th>
                    <th scope="col" class="px-6 py-3">Nilai Stok</th>
                    <th scope="col" class="px-6 py-3">Status</th>
                    <th scope="col" class="px-6 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    <tr class="bg-card-light dark:bg-card-dark border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                        <td class="px-6 py-4">
                            <input type="checkbox" name="selected_products[]" value="{{ $product->id }}" 
                                   class="product-checkbox w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary dark:focus:ring-primary dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                        </td>
                        <td class="px-6 py-4 font-medium text-text-light dark:text-text-dark">
                            {{ $product->code }}
                        </td>
                        <td class="px-6 py-4">
                            <div>
                                <a href="{{ route('stock.show', $product) }}" class="font-medium text-primary hover:underline">
                                    {{ $product->name }}
                                </a>
                                @if($product->location)
                                    <p class="text-xs text-text-muted-light dark:text-text-muted-dark mt-1">
                                        <span class="material-icons text-xs">place</span> {{ $product->location }}
                                    </p>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4">{{ $product->category->name }}</td>
                        <td class="px-6 py-4">
                            <div class="flex flex-col">
                                <span class="font-medium text-text-light dark:text-text-dark">{{ number_format($product->current_stock) }}</span>
                                <span class="text-xs text-text-muted-light dark:text-text-muted-dark">{{ $product->unit }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-col">
                                <span class="font-medium text-text-light dark:text-text-dark">{{ number_format($product->minimum_stock) }}</span>
                                <span class="text-xs text-text-muted-light dark:text-text-muted-dark">{{ $product->unit }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            Rp {{ number_format($product->purchase_price, 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4">
                            Rp {{ number_format($product->selling_price, 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 font-medium">
                            Rp {{ number_format($product->stock_value, 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $statusClass = match($product->stock_status) {
                                    'normal' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                    'menipis' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                    'kritis' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                                    'habis' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                    default => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300'
                                };
                                $statusText = match($product->stock_status) {
                                    'normal' => 'Normal',
                                    'menipis' => 'Menipis',
                                    'kritis' => 'Kritis',
                                    'habis' => 'Habis',
                                    default => 'Unknown'
                                };
                            @endphp
                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $statusClass }}">
                                {{ $statusText }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center space-x-2">
                                <button onclick="openAdjustStockModal({{ $product->id }}, '{{ $product->name }}', {{ $product->current_stock }})" 
                                        class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 text-xs rounded-md">
                                    Sesuaikan
                                </button>
                                <button onclick="openEditProductModal({{ $product->id }})" 
                                        class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 text-xs rounded-md">
                                    Edit
                                </button>
                                <button onclick="deleteProduct({{ $product->id }})" 
                                        class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 text-xs rounded-md">
                                    Hapus
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr class="bg-card-light dark:bg-card-dark border-b dark:border-gray-700">
                        <td colspan="11" class="px-6 py-4 text-center text-text-muted-light dark:text-text-muted-dark">
                            Tidak ada data produk ditemukan
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($products->hasPages())
        <div class="flex justify-between items-center pt-4">
            <span class="text-sm text-text-muted-light dark:text-text-muted-dark">
                Menampilkan {{ $products->firstItem() }}-{{ $products->lastItem() }} dari {{ $products->total() }} produk
            </span>
            {{ $products->withQueryString()->links() }}
        </div>
    @endif
</div>

<!-- Add Product Modal -->
<div id="addProductModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-card-light dark:bg-card-dark">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-text-light dark:text-text-dark">Tambah Produk Baru</h3>
                <button onclick="closeAddProductModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <span class="material-icons">close</span>
                </button>
            </div>
            <form id="addProductForm" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="add_code" class="block text-sm font-medium text-text-light dark:text-text-dark">Kode Produk</label>
                        <input type="text" id="add_code" name="code" required
                               class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label for="add_category_id" class="block text-sm font-medium text-text-light dark:text-text-dark">Kategori</label>
                        <select id="add_category_id" name="category_id" required
                                class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                            <option value="">Pilih Kategori</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label for="add_name" class="block text-sm font-medium text-text-light dark:text-text-dark">Nama Produk</label>
                    <input type="text" id="add_name" name="name" required
                           class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="add_unit" class="block text-sm font-medium text-text-light dark:text-text-dark">Satuan</label>
                        <input type="text" id="add_unit" name="unit" required placeholder="Pcs, Kg, Liter, dll"
                               class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label for="add_location" class="block text-sm font-medium text-text-light dark:text-text-dark">Lokasi (Opsional)</label>
                        <input type="text" id="add_location" name="location" placeholder="Rak A-1, Gudang, dll"
                               class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="add_purchase_price" class="block text-sm font-medium text-text-light dark:text-text-dark">Harga Beli</label>
                        <input type="number" id="add_purchase_price" name="purchase_price" required min="0"
                               class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label for="add_selling_price" class="block text-sm font-medium text-text-light dark:text-text-dark">Harga Jual</label>
                        <input type="number" id="add_selling_price" name="selling_price" required min="0"
                               class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="add_current_stock" class="block text-sm font-medium text-text-light dark:text-text-dark">Stok Awal</label>
                        <input type="number" id="add_current_stock" name="current_stock" required min="0"
                               class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label for="add_minimum_stock" class="block text-sm font-medium text-text-light dark:text-text-dark">Stok Minimum</label>
                        <input type="number" id="add_minimum_stock" name="minimum_stock" min="0" value="10"
                               class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                    </div>
                </div>

                <div>
                    <label for="add_description" class="block text-sm font-medium text-text-light dark:text-text-dark">Deskripsi (Opsional)</label>
                    <textarea id="add_description" name="description" rows="3"
                              class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary"></textarea>
                </div>

                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeAddProductModal()" 
                            class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500">
                        Batal
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-primary text-white rounded-md hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Adjust Stock Modal -->
<div id="adjustStockModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-card-light dark:bg-card-dark">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-text-light dark:text-text-dark">Penyesuaian Stok</h3>
                <button onclick="closeAdjustStockModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <span class="material-icons">close</span>
                </button>
            </div>
            <form id="adjustStockForm" class="space-y-4">
                @csrf
                <input type="hidden" id="adjust_product_id" name="product_id">
                
                <div class="text-center mb-4">
                    <p class="text-text-light dark:text-text-dark font-medium" id="adjust_product_name"></p>
                    <p class="text-text-muted-light dark:text-text-muted-dark text-sm">Stok saat ini: <span id="adjust_current_stock" class="font-medium"></span></p>
                </div>

                <div>
                    <label for="adjustment_type" class="block text-sm font-medium text-text-light dark:text-text-dark">Jenis Penyesuaian</label>
                    <select id="adjustment_type" name="adjustment_type" required
                            class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                        <option value="add">Tambah Stok</option>
                        <option value="subtract">Kurangi Stok</option>
                        <option value="set">Set Stok</option>
                    </select>
                </div>

                <div>
                    <label for="quantity" class="block text-sm font-medium text-text-light dark:text-text-dark">Jumlah</label>
                    <input type="number" id="quantity" name="quantity" required min="1"
                           class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <label for="adjust_notes" class="block text-sm font-medium text-text-light dark:text-text-dark">Catatan (Opsional)</label>
                    <textarea id="adjust_notes" name="notes" rows="3" placeholder="Alasan penyesuaian stok..."
                              class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary"></textarea>
                </div>

                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeAdjustStockModal()" 
                            class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500">
                        Batal
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-primary text-white rounded-md hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                        Sesuaikan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Product Modal -->
<div id="editProductModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-card-light dark:bg-card-dark">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-text-light dark:text-text-dark">Edit Produk</h3>
                <button onclick="closeEditProductModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <span class="material-icons">close</span>
                </button>
            </div>
            <form id="editProductForm" class="space-y-4">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_product_id" name="product_id">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="edit_code" class="block text-sm font-medium text-text-light dark:text-text-dark">Kode Produk</label>
                        <input type="text" id="edit_code" name="code" required
                               class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label for="edit_category_id" class="block text-sm font-medium text-text-light dark:text-text-dark">Kategori</label>
                        <select id="edit_category_id" name="category_id" required
                                class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                            <option value="">Pilih Kategori</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label for="edit_name" class="block text-sm font-medium text-text-light dark:text-text-dark">Nama Produk</label>
                    <input type="text" id="edit_name" name="name" required
                           class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="edit_unit" class="block text-sm font-medium text-text-light dark:text-text-dark">Satuan</label>
                        <input type="text" id="edit_unit" name="unit" required placeholder="Pcs, Kg, Liter, dll"
                               class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label for="edit_location" class="block text-sm font-medium text-text-light dark:text-text-dark">Lokasi (Opsional)</label>
                        <input type="text" id="edit_location" name="location" placeholder="Rak A-1, Gudang, dll"
                               class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="edit_purchase_price" class="block text-sm font-medium text-text-light dark:text-text-dark">Harga Beli</label>
                        <input type="number" id="edit_purchase_price" name="purchase_price" required min="0"
                               class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label for="edit_selling_price" class="block text-sm font-medium text-text-light dark:text-text-dark">Harga Jual</label>
                        <input type="number" id="edit_selling_price" name="selling_price" required min="0"
                               class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="edit_current_stock" class="block text-sm font-medium text-text-light dark:text-text-dark">Stok Saat Ini</label>
                        <input type="number" id="edit_current_stock" name="current_stock" required min="0"
                               class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label for="edit_minimum_stock" class="block text-sm font-medium text-text-light dark:text-text-dark">Stok Minimum</label>
                        <input type="number" id="edit_minimum_stock" name="minimum_stock" min="0"
                               class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                    </div>
                </div>

                <div>
                    <label for="edit_description" class="block text-sm font-medium text-text-light dark:text-text-dark">Deskripsi (Opsional)</label>
                    <textarea id="edit_description" name="description" rows="3"
                              class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary"></textarea>
                </div>

                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeEditProductModal()" 
                            class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500">
                        Batal
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-primary text-white rounded-md hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                        Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Adjust Stock Modal -->
<div id="adjustStockModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-card-light dark:bg-card-dark">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-text-light dark:text-text-dark">Penyesuaian Stok</h3>
                <button onclick="closeAdjustStockModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <span class="material-icons">close</span>
                </button>
            </div>
            <form id="adjustStockForm" class="space-y-4">
                @csrf
                <input type="hidden" id="adjust_product_id" name="product_id">
                
                <div class="text-center mb-4">
                    <p class="text-text-light dark:text-text-dark font-medium" id="adjust_product_name"></p>
                    <p class="text-text-muted-light dark:text-text-muted-dark text-sm">Stok saat ini: <span id="adjust_current_stock" class="font-medium"></span></p>
                </div>

                <div>
                    <label for="adjustment_type" class="block text-sm font-medium text-text-light dark:text-text-dark">Jenis Penyesuaian</label>
                    <select id="adjustment_type" name="adjustment_type" required
                            class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                        <option value="add">Tambah Stok</option>
                        <option value="subtract">Kurangi Stok</option>
                        <option value="set">Set Stok</option>
                    </select>
                </div>

                <div>
                    <label for="quantity" class="block text-sm font-medium text-text-light dark:text-text-dark">Jumlah</label>
                    <input type="number" id="quantity" name="quantity" required min="1"
                           class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <label for="adjust_notes" class="block text-sm font-medium text-text-light dark:text-text-dark">Catatan (Opsional)</label>
                    <textarea id="adjust_notes" name="notes" rows="3" placeholder="Alasan penyesuaian stok..."
                              class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary"></textarea>
                </div>

                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeAdjustStockModal()" 
                            class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500">
                        Batal
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-primary text-white rounded-md hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                        Sesuaikan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Product Modal -->
<div id="editProductModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-card-light dark:bg-card-dark">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-text-light dark:text-text-dark">Edit Produk</h3>
                <button onclick="closeEditProductModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <span class="material-icons">close</span>
                </button>
            </div>
            <form id="editProductForm" class="space-y-4">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_product_id" name="product_id">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="edit_code" class="block text-sm font-medium text-text-light dark:text-text-dark">Kode Produk</label>
                        <input type="text" id="edit_code" name="code" required
                               class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label for="edit_category_id" class="block text-sm font-medium text-text-light dark:text-text-dark">Kategori</label>
                        <select id="edit_category_id" name="category_id" required
                                class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                            <option value="">Pilih Kategori</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label for="edit_name" class="block text-sm font-medium text-text-light dark:text-text-dark">Nama Produk</label>
                    <input type="text" id="edit_name" name="name" required
                           class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="edit_unit" class="block text-sm font-medium text-text-light dark:text-text-dark">Satuan</label>
                        <input type="text" id="edit_unit" name="unit" required placeholder="Pcs, Kg, Liter, dll"
                               class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label for="edit_location" class="block text-sm font-medium text-text-light dark:text-text-dark">Lokasi</label>
                        <input type="text" id="edit_location" name="location" placeholder="Rak A-1, Gudang, dll"
                               class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="edit_purchase_price" class="block text-sm font-medium text-text-light dark:text-text-dark">Harga Beli</label>
                        <input type="number" id="edit_purchase_price" name="purchase_price" required min="0"
                               class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label for="edit_selling_price" class="block text-sm font-medium text-text-light dark:text-text-dark">Harga Jual</label>
                        <input type="number" id="edit_selling_price" name="selling_price" required min="0"
                               class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="edit_current_stock" class="block text-sm font-medium text-text-light dark:text-text-dark">Stok Saat Ini</label>
                        <input type="number" id="edit_current_stock" name="current_stock" required min="0"
                               class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label for="edit_minimum_stock" class="block text-sm font-medium text-text-light dark:text-text-dark">Stok Minimum</label>
                        <input type="number" id="edit_minimum_stock" name="minimum_stock" min="0"
                               class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary">
                    </div>
                </div>

                <div>
                    <label for="edit_description" class="block text-sm font-medium text-text-light dark:text-text-dark">Deskripsi</label>
                    <textarea id="edit_description" name="description" rows="3"
                              class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary"></textarea>
                </div>

                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeEditProductModal()" 
                            class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500">
                        Batal
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-primary text-white rounded-md hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                        Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Auto-generate product code
async function generateProductCode() {
    try {
        const response = await fetch('{{ route("stock.generate-code") }}');
        const data = await response.json();
        if (data.success) {
            document.getElementById('add_code').value = data.code;
        }
    } catch (error) {
        console.error('Error generating product code:', error);
    }
}

// Filter functions
function applyFilter() {
    const form = document.createElement('form');
    form.method = 'GET';
    form.action = '{{ route("stock.index") }}';
    
    // Get current search value
    const searchValue = document.querySelector('input[name="search"]')?.value || '';
    if (searchValue) {
        const searchInput = document.createElement('input');
        searchInput.type = 'hidden';
        searchInput.name = 'search';
        searchInput.value = searchValue;
        form.appendChild(searchInput);
    }
    
    // Get category filter
    const categoryValue = document.getElementById('categoryFilter').value;
    if (categoryValue) {
        const categoryInput = document.createElement('input');
        categoryInput.type = 'hidden';
        categoryInput.name = 'category_id';
        categoryInput.value = categoryValue;
        form.appendChild(categoryInput);
    }
    
    // Get stock status filter
    const statusValue = document.getElementById('stockStatusFilter').value;
    if (statusValue) {
        const statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'stock_status';
        statusInput.value = statusValue;
        form.appendChild(statusInput);
    }
    
    document.body.appendChild(form);
    form.submit();
}

// Modal functions
function openAddProductModal() {
    document.getElementById('addProductModal').classList.remove('hidden');
    generateProductCode(); // Auto generate code when opening modal
}

function closeAddProductModal() {
    document.getElementById('addProductModal').classList.add('hidden');
    document.getElementById('addProductForm').reset();
}

function openAdjustStockModal(productId, productName, currentStock) {
    document.getElementById('adjust_product_id').value = productId;
    document.getElementById('adjust_product_name').textContent = productName;
    document.getElementById('adjust_current_stock').textContent = currentStock;
    document.getElementById('adjustStockModal').classList.remove('hidden');
}

function closeAdjustStockModal() {
    document.getElementById('adjustStockModal').classList.add('hidden');
    document.getElementById('adjustStockForm').reset();
}

function openEditProductModal(productId) {
    // This would typically load product data and open edit modal
    // For now, redirect to edit page or implement inline editing
    window.location.href = `{{ route('stock.index') }}/${productId}/edit`;
}

// Form submissions
document.getElementById('addProductForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    try {
        const response = await fetch('{{ route("stock.store") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('success', data.message);
            closeAddProductModal();
            location.reload(); // Refresh page to show new product
        } else {
            showAlert('error', data.message);
        }
    } catch (error) {
        showAlert('error', 'Terjadi kesalahan saat menyimpan produk');
        console.error('Error:', error);
    }
});

document.getElementById('adjustStockForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const productId = document.getElementById('adjust_product_id').value;
    const formData = new FormData(this);
    
    try {
        const response = await fetch(`{{ route('stock.index') }}/${productId}/adjust-stock`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('success', data.message);
            closeAdjustStockModal();
            location.reload(); // Refresh page to show updated stock
        } else {
            showAlert('error', data.message);
        }
    } catch (error) {
        showAlert('error', 'Terjadi kesalahan saat menyesuaikan stok');
        console.error('Error:', error);
    }
});

// Edit Product Modal Functions
async function openEditProductModal(productId) {
    try {
        const response = await fetch(`{{ route('stock.index') }}/${productId}/data`);
        const result = await response.json();
        
        if (result.success) {
            const product = result.data;
            
            // Isi form dengan data produk
            document.getElementById('edit_product_id').value = product.id;
            document.getElementById('edit_code').value = product.code;
            document.getElementById('edit_name').value = product.name;
            document.getElementById('edit_category_id').value = product.category_id;
            document.getElementById('edit_unit').value = product.unit;
            document.getElementById('edit_location').value = product.location || '';
            document.getElementById('edit_purchase_price').value = product.purchase_price;
            document.getElementById('edit_selling_price').value = product.selling_price;
            document.getElementById('edit_current_stock').value = product.current_stock;
            document.getElementById('edit_minimum_stock').value = product.minimum_stock;
            document.getElementById('edit_description').value = product.description || '';
            
            // Tampilkan modal
            document.getElementById('editProductModal').classList.remove('hidden');
        } else {
            showAlert('error', 'Gagal memuat data produk');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'Terjadi kesalahan saat memuat data produk');
    }
}

function closeEditProductModal() {
    document.getElementById('editProductModal').classList.add('hidden');
    document.getElementById('editProductForm').reset();
}

// Edit Product Form Submission
document.getElementById('editProductForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const productId = document.getElementById('edit_product_id').value;
    const formData = new FormData(this);
    
    try {
        const response = await fetch(`{{ route('stock.index') }}/${productId}`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-HTTP-Method-Override': 'PUT'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('success', data.message);
            closeEditProductModal();
            location.reload(); // Refresh page to show updated product
        } else {
            showAlert('error', data.message);
        }
    } catch (error) {
        showAlert('error', 'Terjadi kesalahan saat memperbarui produk');
        console.error('Error:', error);
    }
});

// Close modal when clicking outside
document.getElementById('editProductModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditProductModal();
    }
});

// Delete product
async function deleteProduct(productId) {
    if (!confirm('Apakah Anda yakin ingin menghapus produk ini?')) {
        return;
    }
    
    try {
        const response = await fetch(`{{ route('stock.index') }}/${productId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('success', data.message);
            location.reload(); // Refresh page
        } else {
            showAlert('error', data.message);
        }
    } catch (error) {
        showAlert('error', 'Terjadi kesalahan saat menghapus produk');
        console.error('Error:', error);
    }
}

// Export function
async function exportStock() {
    try {
        // Show loading indicator
        const exportBtn = event.target.closest('button');
        const originalContent = exportBtn.innerHTML;
        exportBtn.disabled = true;
        exportBtn.innerHTML = '<span class="material-icons mr-2 animate-spin">sync</span>Mengekspor...';
        
        const params = new URLSearchParams();
        
        // Get current filters
        const categoryId = document.getElementById('categoryFilter').value;
        const stockStatus = document.getElementById('stockStatusFilter').value;
        const search = document.querySelector('input[name="search"]')?.value;
        
        if (categoryId) params.append('category_id', categoryId);
        if (stockStatus) params.append('stock_status', stockStatus);
        if (search) params.append('search', search);
        
        const response = await fetch(`{{ route('stock.export') }}?${params}`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        
        const data = await response.json();
        
        if (data.success && data.data && data.data.length > 0) {
            // Create and download Excel file
            const worksheet = XLSX.utils.json_to_sheet(data.data);
            const workbook = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(workbook, worksheet, 'Stok Barang');
            
            // Apply column width
            const wscols = [
                {wch: 15}, // Kode Barang
                {wch: 30}, // Nama Barang
                {wch: 15}, // Kategori
                {wch: 10}, // Satuan
                {wch: 15}, // Stok Saat Ini
                {wch: 15}, // Stok Minimum
                {wch: 15}, // Harga Beli
                {wch: 15}, // Harga Jual
                {wch: 15}, // Nilai Stok
                {wch: 20}, // Lokasi
                {wch: 15}, // Status Stok
                {wch: 12}  // Status Aktif
            ];
            worksheet['!cols'] = wscols;
            
            XLSX.writeFile(workbook, data.filename);
            
            showAlert('success', `Data berhasil diekspor (${data.data.length} produk)`);
        } else {
            showAlert('error', 'Tidak ada data untuk diekspor');
        }
        
        // Restore button
        exportBtn.disabled = false;
        exportBtn.innerHTML = originalContent;
    } catch (error) {
        showAlert('error', 'Terjadi kesalahan saat mengekspor data: ' + error.message);
        console.error('Error:', error);
        
        // Restore button
        const exportBtn = event.target.closest('button');
        if (exportBtn) {
            exportBtn.disabled = false;
            exportBtn.innerHTML = '<span class="material-icons mr-2">download</span>Export';
        }
    }
}

// Select all checkbox functionality
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.product-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});

// Alert function
function showAlert(type, message) {
    // Create alert element
    const alertDiv = document.createElement('div');
    alertDiv.className = `fixed top-4 right-4 z-50 p-4 rounded-md shadow-lg ${
        type === 'success' ? 'bg-green-100 text-green-800 border border-green-200' : 
        'bg-red-100 text-red-800 border border-red-200'
    }`;
    alertDiv.innerHTML = `
        <div class="flex items-center">
            <span class="material-icons mr-2">${type === 'success' ? 'check_circle' : 'error'}</span>
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-gray-400 hover:text-gray-600">
                <span class="material-icons">close</span>
            </button>
        </div>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Close modal when clicking outside
document.getElementById('addProductModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAddProductModal();
    }
});

document.getElementById('adjustStockModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAdjustStockModal();
    }
});
</script>
<!-- Include SheetJS for Excel export -->
<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
@endpush
@endsection