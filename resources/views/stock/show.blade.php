@extends('layouts.app')

@section('title', 'Detail Stok Barang')
@section('page-title', 'Detail Stok Barang')
@section('page-description', 'Informasi lengkap tentang stok barang dan riwayat transaksi')

@section('content')
<div class="space-y-6">
    <!-- Product Information Card -->
    <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg border border-border-light dark:border-border-dark">
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
            <!-- Basic Info -->
            <div class="flex-1">
                <div class="flex items-center gap-4 mb-4">
                    <h1 class="text-2xl font-bold text-text-light dark:text-text-dark">{{ $product->name }}</h1>
                    <span class="px-3 py-1 text-xs font-medium rounded-full 
                        @if($stockStats['stock_status'] === 'normal') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                        @elseif($stockStats['stock_status'] === 'menipis') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                        @elseif($stockStats['stock_status'] === 'kritis') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300
                        @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 @endif">
                        {{ ucfirst($stockStats['stock_status']) }}
                    </span>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-text-muted-light dark:text-text-muted-dark">Kode Barang:</span>
                        <span class="ml-2 font-medium text-text-light dark:text-text-dark">{{ $product->code }}</span>
                    </div>
                    <div>
                        <span class="text-text-muted-light dark:text-text-muted-dark">Kategori:</span>
                        <span class="ml-2 font-medium text-text-light dark:text-text-dark">{{ $product->category->name }}</span>
                    </div>
                    <div>
                        <span class="text-text-muted-light dark:text-text-muted-dark">Satuan:</span>
                        <span class="ml-2 font-medium text-text-light dark:text-text-dark">{{ $product->unit }}</span>
                    </div>
                    <div>
                        <span class="text-text-muted-light dark:text-text-muted-dark">Lokasi:</span>
                        <span class="ml-2 font-medium text-text-light dark:text-text-dark">{{ $product->location ?? '-' }}</span>
                    </div>
                </div>
                
                @if($product->description)
                <div class="mt-4">
                    <span class="text-text-muted-light dark:text-text-muted-dark">Deskripsi:</span>
                    <p class="mt-1 text-text-light dark:text-text-dark">{{ $product->description }}</p>
                </div>
                @endif
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col gap-2 lg:w-48">
                <button onclick="editProduct({{ $product->id }})" 
                        class="flex items-center justify-center gap-2 px-4 py-2 bg-orange-500 text-white rounded-lg text-sm hover:bg-orange-600 transition-colors">
                    <span class="material-icons text-base">edit</span>
                    Edit Produk
                </button>
                <button onclick="adjustStock({{ $product->id }})" 
                        class="flex items-center justify-center gap-2 px-4 py-2 bg-blue-500 text-white rounded-lg text-sm hover:bg-blue-600 transition-colors">
                    <span class="material-icons text-base">tune</span>
                    Sesuaikan Stok
                </button>
                <a href="{{ route('stock.index') }}" 
                   class="flex items-center justify-center gap-2 px-4 py-2 border border-border-light dark:border-border-dark text-text-light dark:text-text-dark rounded-lg text-sm hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <span class="material-icons text-base">arrow_back</span>
                    Kembali
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Current Stock -->
        <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg border border-border-light dark:border-border-dark">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-text-light dark:text-text-dark">{{ number_format($product->current_stock) }}</h3>
                    <p class="text-text-muted-light dark:text-text-muted-dark text-sm">Stok Saat Ini</p>
                </div>
                <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-full">
                    <span class="material-icons text-blue-600 dark:text-blue-300">inventory</span>
                </div>
            </div>
            <div class="mt-2 text-xs text-text-muted-light dark:text-text-muted-dark">
                Minimum: {{ number_format($product->minimum_stock) }}
            </div>
        </div>

        <!-- Stock Value -->
        <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg border border-border-light dark:border-border-dark">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-text-light dark:text-text-dark">Rp {{ number_format($stockStats['current_value']) }}</h3>
                    <p class="text-text-muted-light dark:text-text-muted-dark text-sm">Nilai Stok</p>
                </div>
                <div class="p-3 bg-green-100 dark:bg-green-900 rounded-full">
                    <span class="material-icons text-green-600 dark:text-green-300">monetization_on</span>
                </div>
            </div>
            <div class="mt-2 text-xs text-text-muted-light dark:text-text-muted-dark">
                Harga Beli: Rp {{ number_format($product->purchase_price) }}
            </div>
        </div>

        <!-- Selling Value -->
        <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg border border-border-light dark:border-border-dark">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-text-light dark:text-text-dark">Rp {{ number_format($stockStats['selling_value']) }}</h3>
                    <p class="text-text-muted-light dark:text-text-muted-dark text-sm">Nilai Jual</p>
                </div>
                <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-full">
                    <span class="material-icons text-purple-600 dark:text-purple-300">point_of_sale</span>
                </div>
            </div>
            <div class="mt-2 text-xs text-text-muted-light dark:text-text-muted-dark">
                Harga Jual: Rp {{ number_format($product->selling_price) }}
            </div>
        </div>

        <!-- Potential Profit -->
        <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg border border-border-light dark:border-border-dark">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-text-light dark:text-text-dark">Rp {{ number_format($stockStats['potential_profit']) }}</h3>
                    <p class="text-text-muted-light dark:text-text-muted-dark text-sm">Potensi Keuntungan</p>
                </div>
                <div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-full">
                    <span class="material-icons text-yellow-600 dark:text-yellow-300">trending_up</span>
                </div>
            </div>
            <div class="mt-2 text-xs text-text-muted-light dark:text-text-muted-dark">
                Margin: Rp {{ number_format($product->selling_price - $product->purchase_price) }}
            </div>
        </div>
    </div>

    <!-- Stock Movement Chart -->
    @if($stockMovements->isNotEmpty())
    <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg border border-border-light dark:border-border-dark">
        <h3 class="text-lg font-semibold text-text-light dark:text-text-dark mb-4">Pergerakan Stok (30 Hari Terakhir)</h3>
        <div class="h-64">
            <canvas id="stockMovementChart"></canvas>
        </div>
    </div>
    @endif

    <!-- Stock Transaction History -->
    <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg border border-border-light dark:border-border-dark">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-semibold text-text-light dark:text-text-dark">Riwayat Transaksi Stok</h3>
            <div class="flex items-center gap-2">
                <select id="transactionTypeFilter" class="px-3 py-1 border border-border-light dark:border-border-dark rounded text-sm bg-background-light dark:bg-background-dark">
                    <option value="">Semua Transaksi</option>
                    <option value="in">Masuk</option>
                    <option value="out">Keluar</option>
                    <option value="adjustment">Penyesuaian</option>
                </select>
            </div>
        </div>

        @if($product->stockTransactions->isNotEmpty())
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-xs text-text-muted-light dark:text-text-muted-dark uppercase bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left">Tanggal</th>
                        <th class="px-4 py-3 text-left">Kode Transaksi</th>
                        <th class="px-4 py-3 text-left">Jenis</th>
                        <th class="px-4 py-3 text-left">Jumlah</th>
                        <th class="px-4 py-3 text-left">Stok Sebelum</th>
                        <th class="px-4 py-3 text-left">Stok Sesudah</th>
                        <th class="px-4 py-3 text-left">Referensi</th>
                        <th class="px-4 py-3 text-left">User</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($product->stockTransactions as $transaction)
                    <tr class="transaction-row" data-type="{{ $transaction->type }}">
                        <td class="px-4 py-3 text-text-light dark:text-text-dark">
                            {{ $transaction->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-4 py-3 font-mono text-text-light dark:text-text-dark">
                            {{ $transaction->transaction_code }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs font-medium rounded-full
                                @if($transaction->type === 'in') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                                @elseif($transaction->type === 'out') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300
                                @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300 @endif">
                                {{ $transaction->type_text }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-text-light dark:text-text-dark">
                            @if($transaction->type === 'out')
                                <span class="text-red-600 dark:text-red-400">-{{ number_format($transaction->quantity) }}</span>
                            @else
                                <span class="text-green-600 dark:text-green-400">+{{ number_format($transaction->quantity) }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-text-light dark:text-text-dark">
                            {{ number_format($transaction->stock_before) }}
                        </td>
                        <td class="px-4 py-3 text-text-light dark:text-text-dark">
                            {{ number_format($transaction->stock_after) }}
                        </td>
                        <td class="px-4 py-3 text-text-light dark:text-text-dark">
                            {{ $transaction->reference_text }}
                        </td>
                        <td class="px-4 py-3 text-text-light dark:text-text-dark">
                            {{ $transaction->user->name ?? '-' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center py-8">
            <span class="material-icons text-gray-400 text-4xl mb-2">history</span>
            <p class="text-text-muted-light dark:text-text-muted-dark">Belum ada riwayat transaksi stok</p>
        </div>
            @endif
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div id="editProductModal" class="modal fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full justify-center items-center" style="display: none;">
        <div class="relative mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-card-light dark:bg-card-dark">
            <div class="mt-3">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg leading-6 font-medium text-text-light dark:text-text-dark">Edit Produk</h3>
                    <button type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 closeModalBtn">
                        <span class="material-icons">close</span>
                    </button>
                </div>
                
                <form id="editProductForm" class="space-y-4" onsubmit="return false;">
                    @csrf
                    @method('PUT')
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="edit_code" class="block text-sm font-medium text-text-light dark:text-text-dark">Kode Barang</label>
                            <input type="text" id="edit_code" name="code" class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm" required readonly>
                        </div>
                        
                        <div>
                            <label for="edit_name" class="block text-sm font-medium text-text-light dark:text-text-dark">Nama Barang</label>
                            <input type="text" id="edit_name" name="name" class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm" required>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="edit_category_id" class="block text-sm font-medium text-text-light dark:text-text-dark">Kategori</label>
                            <select id="edit_category_id" name="category_id" class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm" required>
                            </select>
                        </div>
                        
                        <div>
                            <label for="edit_unit" class="block text-sm font-medium text-text-light dark:text-text-dark">Satuan</label>
                            <input type="text" id="edit_unit" name="unit" class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm" required>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="edit_purchase_price" class="block text-sm font-medium text-text-light dark:text-text-dark">Harga Beli</label>
                            <input type="number" id="edit_purchase_price" name="purchase_price" min="0" step="0.01" class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm" required>
                        </div>
                        
                        <div>
                            <label for="edit_selling_price" class="block text-sm font-medium text-text-light dark:text-text-dark">Harga Jual</label>
                            <input type="number" id="edit_selling_price" name="selling_price" min="0" step="0.01" class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm" required>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="edit_current_stock" class="block text-sm font-medium text-text-light dark:text-text-dark">Stok Saat Ini</label>
                            <input type="number" id="edit_current_stock" name="current_stock" min="0" class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm" required>
                        </div>
                        
                        <div>
                            <label for="edit_minimum_stock" class="block text-sm font-medium text-text-light dark:text-text-dark">Stok Minimum</label>
                            <input type="number" id="edit_minimum_stock" name="minimum_stock" min="0" class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm" required>
                        </div>
                    </div>
                    
                    <div>
                        <label for="edit_location" class="block text-sm font-medium text-text-light dark:text-text-dark">Lokasi</label>
                        <input type="text" id="edit_location" name="location" class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
                    </div>
                    
                    <div>
                        <label for="edit_description" class="block text-sm font-medium text-text-light dark:text-text-dark">Deskripsi</label>
                        <textarea id="edit_description" name="description" rows="3" class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm"></textarea>
                    </div>
                    
                    <div class="flex justify-end gap-2 mt-6">
                        <button type="button" class="closeModalBtn px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500">
                            Batal
                        </button>
                        <button type="submit" class="px-4 py-2 bg-primary text-white rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Adjust Stock Modal -->
    <div id="adjustStockModal" class="modal fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full justify-center items-center" style="display: none;">
        <div class="relative mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-card-light dark:bg-card-dark">
            <div class="mt-3">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg leading-6 font-medium text-text-light dark:text-text-dark">Sesuaikan Stok</h3>
                    <button type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 closeModalBtn">
                        <span class="material-icons">close</span>
                    </button>
                </div>
                
                <form id="adjustStockForm" class="space-y-4" onsubmit="return false;">
                    @csrf
                    
                    <div>
                        <label class="block text-sm font-medium text-text-light dark:text-text-dark mb-2">Jenis Penyesuaian</label>
                        <div class="grid grid-cols-3 gap-2">
                            <label class="flex items-center">
                                <input type="radio" name="adjustment_type" value="add" class="mr-2" required>
                                <span class="text-sm">Tambah</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="adjustment_type" value="subtract" class="mr-2" required>
                                <span class="text-sm">Kurangi</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="adjustment_type" value="set" class="mr-2" required>
                                <span class="text-sm">Set</span>
                            </label>
                        </div>
                    </div>
                    
                    <div>
                        <label for="adjust_quantity" class="block text-sm font-medium text-text-light dark:text-text-dark">Jumlah</label>
                        <input type="number" id="adjust_quantity" name="quantity" min="1" class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm" required>
                    </div>
                    
                    <div>
                        <label for="adjust_notes" class="block text-sm font-medium text-text-light dark:text-text-dark">Catatan</label>
                        <textarea id="adjust_notes" name="notes" rows="3" class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm" placeholder="Alasan penyesuaian stok..."></textarea>
                    </div>
                    
                    <div class="flex justify-end gap-2 mt-6">
                        <button type="button" class="closeModalBtn px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500">
                            Batal
                        </button>
                        <button type="submit" class="px-4 py-2 bg-primary text-white rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                            Sesuaikan Stok
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Adjust Stock Modal -->
    <div id="adjustStockModal" class="modal fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full justify-center items-center">
        <div class="relative mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-card-light dark:bg-card-dark">
            <div class="mt-3">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg leading-6 font-medium text-text-light dark:text-text-dark">Sesuaikan Stok</h3>
                    <button class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 closeModalBtn">
                        <span class="material-icons">close</span>
                    </button>
                </div>
                
                <form id="adjustStockForm" class="space-y-4">
                    @csrf
                    
                    <div>
                        <label class="block text-sm font-medium text-text-light dark:text-text-dark mb-2">Jenis Penyesuaian</label>
                        <div class="grid grid-cols-3 gap-2">
                            <label class="flex items-center">
                                <input type="radio" name="adjustment_type" value="add" class="mr-2" required>
                                <span class="text-sm">Tambah</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="adjustment_type" value="subtract" class="mr-2" required>
                                <span class="text-sm">Kurangi</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="adjustment_type" value="set" class="mr-2" required>
                                <span class="text-sm">Set</span>
                            </label>
                        </div>
                    </div>
                    
                    <div>
                        <label for="adjust_quantity" class="block text-sm font-medium text-text-light dark:text-text-dark">Jumlah</label>
                        <input type="number" id="adjust_quantity" name="quantity" min="1" class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm" required>
                    </div>
                    
                    <div>
                        <label for="adjust_notes" class="block text-sm font-medium text-text-light dark:text-text-dark">Catatan</label>
                        <textarea id="adjust_notes" name="notes" rows="3" class="mt-1 block w-full px-3 py-2 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm" placeholder="Alasan penyesuaian stok..."></textarea>
                    </div>
                    
                    <div class="flex justify-end gap-2 mt-6">
                        <button type="button" class="closeModalBtn px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500">
                            Batal
                        </button>
                        <button type="submit" class="px-4 py-2 bg-primary text-white rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                            Sesuaikan Stok
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Global variables to track modal state
let currentModal = null;
let isModalOpening = false;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize stock movement chart
    @if($stockMovements->isNotEmpty())
    const ctx = document.getElementById('stockMovementChart').getContext('2d');
    const stockMovementChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($stockMovements->pluck('date')),
            datasets: [{
                label: 'Stok Masuk',
                data: @json($stockMovements->pluck('stock_in')),
                borderColor: 'rgb(34, 197, 94)',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                fill: true
            }, {
                label: 'Stok Keluar',
                data: @json($stockMovements->pluck('stock_out')),
                borderColor: 'rgb(239, 68, 68)',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    @endif

    // Modal functionality
    const modals = document.querySelectorAll('.modal');
    const closeModalBtns = document.querySelectorAll('.closeModalBtn');

    // Function to close all modals
    window.closeAllModals = function() {
        if (isModalOpening) return; // Prevent closing while opening
        
        modals.forEach(modal => {
            modal.classList.remove('active');
            modal.style.display = 'none';
        });
        currentModal = null;
    }

    // Function to open specific modal
    window.openModal = function(modalId) {
        if (isModalOpening) return;
        
        isModalOpening = true;
        
        // Close all modals first
        closeAllModals();
        
        setTimeout(() => {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'flex';
                modal.classList.add('active');
                currentModal = modalId;
            }
            isModalOpening = false;
        }, 150);
    }

    // Close modal buttons
    closeModalBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            closeAllModals();
        });
    });

    // Close modal when clicking outside
    window.addEventListener('click', (event) => {
        modals.forEach(modal => {
            if (event.target === modal && !isModalOpening) {
                closeAllModals();
            }
        });
    });

    // Prevent modal content clicks from closing modal
    document.querySelectorAll('.modal > div').forEach(modalContent => {
        modalContent.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });

    // Transaction type filter
    const transactionTypeFilter = document.getElementById('transactionTypeFilter');
    if (transactionTypeFilter) {
        transactionTypeFilter.addEventListener('change', function() {
            const filterValue = this.value;
            const rows = document.querySelectorAll('.transaction-row');
            
            rows.forEach(row => {
                if (filterValue === '' || row.dataset.type === filterValue) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // Initialize all modals as hidden
    modals.forEach(modal => {
        modal.style.display = 'none';
        modal.classList.remove('active');
    });
});

// Edit product function
function editProduct(productId) {
    // Prevent multiple calls
    if (isModalOpening) return;
    
    fetch(`/stock/${productId}/edit`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const product = data.data;
                
                // Fill form with product data
                document.getElementById('edit_code').value = product.code;
                document.getElementById('edit_name').value = product.name;
                document.getElementById('edit_unit').value = product.unit;
                document.getElementById('edit_purchase_price').value = product.purchase_price;
                document.getElementById('edit_selling_price').value = product.selling_price;
                document.getElementById('edit_current_stock').value = product.current_stock;
                document.getElementById('edit_minimum_stock').value = product.minimum_stock;
                document.getElementById('edit_location').value = product.location || '';
                document.getElementById('edit_description').value = product.description || '';
                
                // Load categories
                const categorySelect = document.getElementById('edit_category_id');
                categorySelect.innerHTML = '';
                data.categories.forEach(category => {
                    const option = document.createElement('option');
                    option.value = category.id;
                    option.textContent = category.name;
                    option.selected = category.id === product.category_id;
                    categorySelect.appendChild(option);
                });
                
                // Set form action
                document.getElementById('editProductForm').action = `/stock/${productId}`;
                
                // Open only edit modal
                openModal('editProductModal');
            } else {
                alert('Gagal memuat data produk: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat memuat data produk');
        });
}

// Adjust stock function
function adjustStock(productId) {
    // Prevent multiple calls
    if (isModalOpening) return;
    
    // Reset form
    const form = document.getElementById('adjustStockForm');
    if (form) {
        form.reset();
        form.action = `/stock/${productId}/adjust-stock`;
    }
    
    // Open only adjust stock modal
    openModal('adjustStockModal');
}

// Handle edit product form submission
document.getElementById('editProductForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const productId = {{ $product->id }};
    
    fetch(`/stock/${productId}`, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal
            document.getElementById('editProductModal').classList.remove('active');
            
            // Show success message and reload page
            alert('Produk berhasil diperbarui');
            location.reload();
        } else {
            alert('Gagal memperbarui produk: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat memperbarui produk');
    });
});

// Handle adjust stock form submission
document.getElementById('adjustStockForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const productId = {{ $product->id }};
    
    fetch(`/stock/${productId}/adjust-stock`, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal
            document.getElementById('adjustStockModal').classList.remove('active');
            
            // Show success message and reload page
            alert(`Stok berhasil disesuaikan. Stok sebelumnya: ${data.data.stock_before}, Stok sekarang: ${data.data.stock_after}`);
            location.reload();
        } else {
            alert('Gagal menyesuaikan stok: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat menyesuaikan stok');
    });
});
</script>
@endpush