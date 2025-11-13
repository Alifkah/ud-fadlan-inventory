@extends('layouts.app')

@section('title', 'Buat Transaksi Pembelian')
@section('page-title', 'Transaksi Pembelian')
@section('page-description', 'Buat dan kelola transaksi pembelian')

@section('content')
<div x-data="purchaseTransaction()" x-init="init()">
    <!-- Action Buttons -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <a href="{{ route('purchases.index') }}" class="flex items-center text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
                <span class="material-icons mr-2">arrow_back</span>
                Kembali ke Daftar
            </a>
        </div>
        <button @click="resetForm()" class="flex items-center justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
            <span class="material-icons mr-2">refresh</span> Reset Form
        </button>
    </div>

    <div class="grid grid-cols-12 gap-6">
        <!-- Left Section - Transaction Details -->
        <div class="col-span-12 lg:col-span-8">
            <!-- Transaction Info Card -->
            <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow-sm">
                <h2 class="text-lg font-semibold mb-4 text-text-light dark:text-text-dark">Detail Transaksi</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">ID Transaksi</label>
                        <input type="text" x-model="transaction.invoice_number" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary sm:text-sm bg-gray-50 dark:bg-gray-700 text-text-light dark:text-text-dark" disabled/>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Transaksi</label>
                        <input type="date" x-model="transaction.purchase_date" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary sm:text-sm bg-white dark:bg-gray-700 text-text-light dark:text-text-dark"/>
                    </div>
                    <div class="relative">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Supplier</label>
                        <div class="mt-1 flex rounded-md shadow-sm">
                            <input type="text" x-model="supplierSearch" @input="searchSupplier()" @focus="showSupplierDropdown = true" placeholder="Ketik nama supplier..." class="flex-1 min-w-0 block w-full px-3 py-2 rounded-none rounded-l-md focus:ring-primary focus:border-primary sm:text-sm border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-text-light dark:text-text-dark"/>
                            <button @click="openAddSupplierModal()" class="inline-flex items-center px-3 rounded-r-md border border-l-0 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400 text-sm hover:bg-gray-100 dark:hover:bg-gray-600">
                                <span class="material-icons text-sm">business</span>
                            </button>
                        </div>
                        <!-- Supplier Dropdown -->
                        <div x-show="showSupplierDropdown && filteredSuppliers.length > 0" @click.outside="showSupplierDropdown = false" class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-800 shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm">
                            <template x-for="supplier in filteredSuppliers" :key="supplier.id">
                                <div @click="selectSupplier(supplier)" class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <div class="flex items-center">
                                        <span class="font-normal block truncate" x-text="supplier.name"></span>
                                    </div>
                                    <span class="text-gray-500 dark:text-gray-400 text-xs block" x-text="supplier.company_name || supplier.phone"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Staff</label>
                        <input type="text" value="{{ auth()->user()->name }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary sm:text-sm bg-gray-50 dark:bg-gray-700 text-text-light dark:text-text-dark" disabled/>
                    </div>
                </div>

                <!-- Search & Add Items -->
                <div class="border-t border-border-light dark:border-border-dark pt-6">
                    <h3 class="text-md font-semibold mb-4 text-text-light dark:text-text-dark">Cari & Tambah Item</h3>
                    <div class="flex items-center space-x-2">
                        <div class="relative flex-grow">
                            <input type="text" x-model="productSearch" @input="searchProduct()" @focus="showProductDropdown = true" placeholder="Masukkan kode atau nama barang..." class="w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md leading-5 bg-white dark:bg-gray-700 text-text-light dark:text-text-dark placeholder-gray-400 focus:outline-none focus:placeholder-gray-500 focus:ring-1 focus:ring-primary focus:border-primary sm:text-sm"/>
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="material-icons text-gray-400">search</span>
                            </div>
                            <!-- Product Dropdown -->
                            <div x-show="showProductDropdown && filteredProducts.length > 0" @click.outside="showProductDropdown = false" class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-800 shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm">
                                <template x-for="product in filteredProducts" :key="product.id">
                                    <div @click="addItemToCart(product)" class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-gray-100 dark:hover:bg-gray-700">
                                        <div class="flex justify-between">
                                            <div>
                                                <span class="font-normal block" x-text="product.name"></span>
                                                <span class="text-gray-500 dark:text-gray-400 text-xs" x-text="product.code"></span>
                                            </div>
                                            <div class="text-right">
                                                <span class="text-primary font-semibold" x-text="formatCurrency(product.purchase_price)"></span>
                                                <span class="text-gray-500 dark:text-gray-400 text-xs block">Stok: <span x-text="product.current_stock"></span></span>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                        <button @click="manualAddItem()" class="flex items-center justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                            <span class="material-icons mr-2">add</span> Tambah Manual
                        </button>
                    </div>
                </div>
            </div>

            <!-- Items List Card -->
            <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow-sm mt-6">
                <h2 class="text-lg font-semibold text-text-light dark:text-text-dark mb-4">Daftar Item Dibeli</h2>
                
                <!-- Empty State -->
                <div x-show="items.length === 0" class="text-center py-12">
                    <span class="material-icons text-gray-400 text-6xl mb-4">shopping_cart</span>
                    <p class="text-gray-500 dark:text-gray-400">Belum ada item ditambahkan</p>
                    <p class="text-sm text-gray-400 dark:text-gray-500">Cari dan tambahkan produk di atas</p>
                </div>

                <!-- Items Table -->
                <div x-show="items.length > 0" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border-light dark:divide-border-dark">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama Barang</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Jumlah</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Harga Satuan</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Subtotal</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-card-light dark:bg-card-dark divide-y divide-border-light dark:divide-border-dark">
                            <template x-for="(item, index) in items" :key="index">
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-text-light dark:text-text-dark font-medium" x-text="item.product_name"></div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400" x-text="item.product_code"></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <input type="number" x-model.number="item.quantity" @input="updateItemTotal(index)" min="1" class="w-20 rounded-md border-gray-300 dark:border-gray-600 shadow-sm bg-white dark:bg-gray-700 text-center text-sm"/>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <input type="number" x-model.number="item.unit_price" @input="updateItemTotal(index)" min="0" step="0.01" class="w-24 rounded-md border-gray-300 dark:border-gray-600 shadow-sm bg-white dark:bg-gray-700 text-center text-sm"/>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-text-light dark:text-text-dark" x-text="formatCurrency(item.total_price)"></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        <button @click="removeItem(index)" class="text-red-600 hover:text-red-700">
                                            <span class="material-icons text-base">delete</span>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right Section - Payment Summary -->
        <div class="col-span-12 lg:col-span-4">
            <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow-sm sticky top-4">
                <h2 class="text-lg font-semibold mb-4 text-text-light dark:text-text-dark">Ringkasan Pembayaran</h2>
                <div class="space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">Subtotal</span>
                        <span class="font-semibold text-text-light dark:text-text-dark" x-text="formatCurrency(summary.subtotal)"></span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-500 dark:text-gray-400">Diskon</span>
                        <div class="flex items-center space-x-2">
                            <input type="number" x-model.number="summary.discount_percent" @input="calculateSummary()" min="0" max="100" class="w-16 text-right rounded-md border-gray-300 dark:border-gray-600 shadow-sm bg-white dark:bg-gray-700 text-text-light dark:text-text-dark sm:text-sm"/>
                            <span class="text-gray-500 dark:text-gray-400">%</span>
                        </div>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">Nilai Diskon</span>
                        <span class="font-semibold text-text-light dark:text-text-dark" x-text="formatCurrency(summary.discount)"></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">Pajak (PPN 11%)</span>
                        <span class="font-semibold text-text-light dark:text-text-dark" x-text="formatCurrency(summary.tax)"></span>
                    </div>
                    <div class="border-t border-border-light dark:border-border-dark my-2"></div>
                    <div class="flex justify-between font-bold text-lg">
                        <span class="text-text-light dark:text-text-dark">Total</span>
                        <span class="text-primary" x-text="formatCurrency(summary.total)"></span>
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Metode Pembayaran</label>
                        <select x-model="payment.method" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary sm:text-sm bg-white dark:bg-gray-700 text-text-light dark:text-text-dark">
                            <option value="cash">Cash</option>
                            <option value="transfer">Transfer Bank</option>
                            <option value="credit">Kredit</option>
                        </select>
                    </div>

                    <!-- Transfer Fields -->
                    <div x-show="payment.method === 'transfer'" class="space-y-3 mt-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Bank</label>
                            <input type="text" x-model="payment.bank_name" placeholder="Contoh: BCA, Mandiri, BRI" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary sm:text-sm bg-white dark:bg-gray-700 text-text-light dark:text-text-dark"/>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">No. Rekening</label>
                            <input type="text" x-model="payment.account_number" placeholder="Nomor rekening tujuan" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary sm:text-sm bg-white dark:bg-gray-700 text-text-light dark:text-text-dark"/>
                        </div>
                    </div>

                    <!-- Credit Fields -->
                    <div x-show="payment.method === 'credit'" class="space-y-3 mt-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal Jatuh Tempo</label>
                            <input type="date" x-model="payment.due_date" :min="getTomorrowDate()" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary sm:text-sm bg-white dark:bg-gray-700 text-text-light dark:text-text-dark"/>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Suku Bunga (%)</label>
                            <input type="number" x-model.number="payment.interest_rate" min="0" max="100" step="0.1" placeholder="0" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary sm:text-sm bg-white dark:bg-gray-700 text-text-light dark:text-text-dark"/>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Catatan Kredit</label>
                            <textarea x-model="payment.credit_notes" rows="2" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary sm:text-sm bg-white dark:bg-gray-700 text-text-light dark:text-text-dark" placeholder="Catatan tambahan untuk kredit..."></textarea>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Catatan</label>
                        <textarea x-model="transaction.notes" rows="2" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary sm:text-sm bg-white dark:bg-gray-700 text-text-light dark:text-text-dark" placeholder="Catatan tambahan..."></textarea>
                    </div>
                </div>

                <div class="mt-8 flex flex-col space-y-3">
                    <button @click="processTransaction()" :disabled="!canProcess()" :class="canProcess() ? 'bg-primary hover:bg-green-600' : 'bg-gray-400 cursor-not-allowed'" class="w-full flex items-center justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        <span class="material-icons mr-2">payment</span> Proses Transaksi
                    </button>
                    <button @click="saveDraft()" class="w-full flex items-center justify-center py-3 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-card-light dark:bg-card-dark hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <span class="material-icons mr-2">save</span> Simpan Draft
                    </button>
                    <button @click="resetForm()" class="w-full flex items-center justify-center py-3 px-4 border border-red-500 rounded-md shadow-sm text-sm font-medium text-red-600 bg-red-50 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        <span class="material-icons mr-2">cancel</span> Batalkan Transaksi
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div x-show="showSuccessModal" @keydown.escape.window="showSuccessModal = false" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center" x-cloak>
        <div @click="showSuccessModal = false" class="fixed inset-0 bg-gray-900 bg-opacity-50"></div>
        <div @click.stop class="bg-card-light dark:bg-card-dark rounded-lg shadow-lg w-full max-w-md mx-auto p-6 z-50 text-center">
            <div class="flex justify-center items-center">
                <div class="w-16 h-16 rounded-full bg-primary flex items-center justify-center mb-4">
                    <span class="material-icons text-white text-4xl">check</span>
                </div>
            </div>
            <h2 class="text-2xl font-bold text-text-light dark:text-text-dark mb-2">Transaksi Berhasil</h2>
            <p class="text-gray-600 dark:text-gray-300 mb-6">Transaksi pembelian telah berhasil dibuat.</p>
            <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-4 space-y-3 text-left mb-6">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">ID Transaksi:</span>
                    <span class="font-semibold text-text-light dark:text-text-dark" x-text="savedTransaction.invoice_number"></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Total:</span>
                    <span class="font-semibold text-text-light dark:text-text-dark" x-text="formatCurrency(savedTransaction.total)"></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Status:</span>
                    <span class="font-semibold text-text-light dark:text-text-dark" x-text="savedTransaction.status"></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Tanggal:</span>
                    <span class="font-semibold text-text-light dark:text-text-dark" x-text="formatDate(savedTransaction.purchase_date)"></span>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row justify-center space-y-2 sm:space-y-0 sm:space-x-3">
                <button @click="newTransaction()" class="w-full flex items-center justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    <span class="material-icons mr-2">add_shopping_cart</span> Transaksi Baru
                </button>
                <a :href="`/purchases/${savedTransaction.purchase_id}`" class="w-full flex items-center justify-center py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-card-light dark:bg-card-dark hover:bg-gray-50 dark:hover:bg-gray-600">
                    <span class="material-icons mr-2">visibility</span> Lihat Detail
                </a>
            </div>
        </div>
    </div>

    <!-- Add Supplier Modal -->
    <div x-show="showAddSupplierModal" @keydown.escape.window="showAddSupplierModal = false" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center" x-cloak>
        <div @click="showAddSupplierModal = false" class="fixed inset-0 bg-gray-900 bg-opacity-50"></div>
        <div @click.stop class="bg-card-light dark:bg-card-dark rounded-lg shadow-lg w-full max-w-md mx-auto p-6 z-50">
            <div class="flex justify-between items-center pb-3 border-b border-border-light dark:border-border-dark mb-4">
                <h2 class="text-xl font-bold text-text-light dark:text-text-dark">Tambah Supplier Baru</h2>
                <button @click="showAddSupplierModal = false" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <span class="material-icons">close</span>
                </button>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Supplier</label>
                    <input type="text" x-model="newSupplier.name" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary sm:text-sm bg-white dark:bg-gray-700 text-text-light dark:text-text-dark"/>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Perusahaan</label>
                    <input type="text" x-model="newSupplier.company_name" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary sm:text-sm bg-white dark:bg-gray-700 text-text-light dark:text-text-dark"/>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">No. Telepon</label>
                    <input type="text" x-model="newSupplier.phone" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary sm:text-sm bg-white dark:bg-gray-700 text-text-light dark:text-text-dark"/>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email (Opsional)</label>
                    <input type="email" x-model="newSupplier.email" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary sm:text-sm bg-white dark:bg-gray-700 text-text-light dark:text-text-dark"/>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Alamat (Opsional)</label>
                    <textarea x-model="newSupplier.address" rows="2" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary focus:ring-primary sm:text-sm bg-white dark:bg-gray-700 text-text-light dark:text-text-dark"></textarea>
                </div>
            </div>
            <div class="flex justify-end space-x-3 mt-6">
                <button @click="showAddSupplierModal = false" class="px-4 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500">
                    Batal
                </button>
                <button @click="saveNewSupplier()" class="px-4 py-2 rounded-md text-sm font-medium text-white bg-primary hover:bg-green-600">
                    Simpan Supplier
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function purchaseTransaction() {
    return {
        // Data
        transaction: {
            invoice_number: '{{ $invoiceNumber ?? "PBL-" . date("Ymd") . "-001" }}',
            purchase_date: new Date().toISOString().split('T')[0],
            supplier_id: null,
            notes: ''
        },
        items: [],
        summary: {
            subtotal: 0,
            discount_percent: 0,
            discount: 0,
            tax: 0,
            total: 0
        },
        payment: {
            method: 'cash',
            bank_name: '',
            account_number: '',
            due_date: '',
            interest_rate: 0,
            credit_notes: ''
        },
        
        // Search & Dropdown
        suppliers: @json($suppliers ?? []),
        products: @json($products ?? []),
        supplierSearch: '',
        productSearch: '',
        filteredSuppliers: [],
        filteredProducts: [],
        showSupplierDropdown: false,
        showProductDropdown: false,
        
        // Modals
        showSuccessModal: false,
        showAddSupplierModal: false,
        
        // New Supplier
        newSupplier: {
            name: '',
            company_name: '',
            phone: '',
            email: '',
            address: ''
        },
        
        // Saved Transaction
        savedTransaction: {},

        init() {
            console.log('Initializing purchase transaction...');
            this.filteredSuppliers = this.suppliers;
            this.filteredProducts = this.products;
            console.log('Suppliers loaded:', this.suppliers.length);
            console.log('Products loaded:', this.products.length);
        },

        searchSupplier() {
            console.log('Searching supplier:', this.supplierSearch);
            const search = this.supplierSearch.toLowerCase();
            this.filteredSuppliers = this.suppliers.filter(s => 
                s.name.toLowerCase().includes(search) || 
                (s.company_name && s.company_name.toLowerCase().includes(search)) ||
                (s.phone && s.phone.includes(search))
            );
            this.showSupplierDropdown = this.filteredSuppliers.length > 0;
            console.log('Filtered suppliers:', this.filteredSuppliers.length);
        },

        selectSupplier(supplier) {
            console.log('Selecting supplier:', supplier);
            this.transaction.supplier_id = supplier.id;
            this.supplierSearch = supplier.name;
            this.showSupplierDropdown = false;
            console.log('Supplier selected, ID:', this.transaction.supplier_id);
        },

        searchProduct() {
            console.log('Searching product:', this.productSearch);
            const search = this.productSearch.toLowerCase();
            this.filteredProducts = this.products.filter(p => 
                p.name.toLowerCase().includes(search) || 
                (p.code && p.code.toLowerCase().includes(search))
            );
            this.showProductDropdown = this.filteredProducts.length > 0;
            console.log('Filtered products:', this.filteredProducts.length);
        },

        addItemToCart(product) {
            console.log('Adding product to cart:', product);
            
            // Check if product already in cart
            const existingIndex = this.items.findIndex(item => item.product_id === product.id);
            
            if (existingIndex !== -1) {
                // Increase quantity
                this.items[existingIndex].quantity++;
                this.updateItemTotal(existingIndex);
            } else {
                // Add new item
                this.items.push({
                    product_id: product.id,
                    product_code: product.code,
                    product_name: product.name,
                    quantity: 1,
                    unit_price: product.purchase_price,
                    total_price: product.purchase_price
                });
            }
            
            this.productSearch = '';
            this.showProductDropdown = false;
            this.calculateSummary();
            console.log('Items in cart:', this.items.length);
        },

        updateItemTotal(index) {
            const item = this.items[index];
            
            // Validate quantity
            if (item.quantity < 1) {
                item.quantity = 1;
            }
            
            // Validate unit price
            if (item.unit_price < 0) {
                item.unit_price = 0;
            }
            
            item.total_price = item.quantity * item.unit_price;
            this.calculateSummary();
        },

        removeItem(index) {
            if (confirm('Hapus item ini dari daftar?')) {
                this.items.splice(index, 1);
                this.calculateSummary();
            }
        },

        calculateSummary() {
            this.summary.subtotal = this.items.reduce((sum, item) => sum + item.total_price, 0);
            this.summary.discount = (this.summary.subtotal * this.summary.discount_percent) / 100;
            const afterDiscount = this.summary.subtotal - this.summary.discount;
            this.summary.tax = afterDiscount * 0.11; // PPN 11%
            this.summary.total = afterDiscount + this.summary.tax;
        },

        canProcess() {
            console.log('Checking canProcess:', {
                items: this.items.length,
                supplier_id: this.transaction.supplier_id,
                payment_method: this.payment.method,
                due_date: this.payment.due_date
            });

            if (this.items.length === 0) {
                console.log('❌ No items');
                return false;
            }
            
            if (!this.transaction.supplier_id) {
                console.log('❌ No supplier selected');
                return false;
            }
            
            if (this.payment.method === 'transfer') {
                if (!this.payment.bank_name || !this.payment.account_number) {
                    console.log('❌ Transfer details incomplete');
                    return false;
                }
            }
            
            if (this.payment.method === 'credit') {
                if (!this.payment.due_date) {
                    console.log('❌ No due date for credit');
                    return false;
                }
            }
            
            console.log('✅ Can process');
            return true;
        },

        async processTransaction() {
            console.log('Processing transaction...');
            
            if (!this.canProcess()) {
                alert('Mohon lengkapi semua data transaksi!');
                return;
            }

            if (!confirm('Proses transaksi pembelian ini?')) {
                return;
            }

            const formData = {
                supplier_id: this.transaction.supplier_id,
                purchase_date: this.transaction.purchase_date,
                items: this.items,
                subtotal: this.summary.subtotal,
                discount: this.summary.discount,
                tax: this.summary.tax,
                total: this.summary.total,
                payment_method: this.payment.method,
                notes: this.transaction.notes
            };

            // Add payment specific data
            if (this.payment.method === 'transfer') {
                formData.bank_name = this.payment.bank_name;
                formData.account_number = this.payment.account_number;
            } else if (this.payment.method === 'credit') {
                formData.due_date = this.payment.due_date;
                formData.interest_rate = this.payment.interest_rate;
                formData.credit_notes = this.payment.credit_notes;
            }

            console.log('Sending data:', formData);

            try {
                const response = await fetch('{{ route("purchases.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const result = await response.json();
                console.log('Response:', result);

                if (result.success) {
                    this.savedTransaction = result.data;
                    this.showSuccessModal = true;
                } else {
                    alert('Error: ' + (result.message || 'Terjadi kesalahan'));
                    console.error('Error response:', result);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat memproses transaksi: ' + error.message);
            }
        },

        async saveDraft() {
            console.log('Saving draft...');
            
            if (this.items.length === 0) {
                alert('Tambahkan item terlebih dahulu!');
                return;
            }

            if (!confirm('Simpan transaksi sebagai draft?')) {
                return;
            }

            const formData = {
                supplier_id: this.transaction.supplier_id,
                purchase_date: this.transaction.purchase_date,
                items: this.items,
                subtotal: this.summary.subtotal,
                discount: this.summary.discount,
                tax: this.summary.tax,
                total: this.summary.total,
                payment_method: this.payment.method,
                status: 'draft',
                notes: this.transaction.notes
            };

            console.log('Saving draft data:', formData);

            try {
                const response = await fetch('{{ route("purchases.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const result = await response.json();
                console.log('Draft response:', result);

                if (result.success) {
                    alert('Draft berhasil disimpan!');
                    window.location.href = '{{ route("purchases.index") }}';
                } else {
                    alert('Error: ' + (result.message || 'Terjadi kesalahan'));
                    console.error('Error response:', result);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menyimpan draft: ' + error.message);
            }
        },

        resetForm() {
            if (confirm('Reset form transaksi? Semua data akan hilang.')) {
                this.items = [];
                this.transaction.supplier_id = null;
                this.supplierSearch = '';
                this.productSearch = '';
                this.summary = {
                    subtotal: 0,
                    discount_percent: 0,
                    discount: 0,
                    tax: 0,
                    total: 0
                };
                this.payment = {
                    method: 'cash',
                    bank_name: '',
                    account_number: '',
                    due_date: '',
                    interest_rate: 0,
                    credit_notes: ''
                };
                console.log('Form reset');
            }
        },

        newTransaction() {
            this.showSuccessModal = false;
            this.resetForm();
            window.location.reload();
        },

        openAddSupplierModal() {
            this.newSupplier = {
                name: '',
                company_name: '',
                phone: '',
                email: '',
                address: ''
            };
            this.showAddSupplierModal = true;
        },

        async saveNewSupplier() {
            if (!this.newSupplier.name || !this.newSupplier.phone) {
                alert('Nama dan No. Telepon wajib diisi!');
                return;
            }

            try {
                const response = await fetch('{{ route("suppliers.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.newSupplier)
                });

                const result = await response.json();

                if (result.success) {
                    // Add to suppliers list
                    this.suppliers.push(result.data);
                    
                    // Select the new supplier
                    this.selectSupplier(result.data);
                    
                    // Close modal
                    this.showAddSupplierModal = false;
                    
                    alert('Supplier berhasil ditambahkan!');
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menambahkan supplier');
            }
        },

        manualAddItem() {
            const productName = prompt('Nama Produk:');
            if (!productName) return;
            
            const quantity = parseInt(prompt('Jumlah:', '1'));
            if (!quantity || quantity < 1) return;
            
            const unitPrice = parseFloat(prompt('Harga Satuan:'));
            if (!unitPrice || unitPrice < 0) return;
            
            this.items.push({
                product_id: null,
                product_code: 'MANUAL',
                product_name: productName,
                quantity: quantity,
                unit_price: unitPrice,
                total_price: quantity * unitPrice
            });
            
            this.calculateSummary();
        },

        getTomorrowDate() {
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            return tomorrow.toISOString().split('T')[0];
        },

        formatCurrency(value) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(value || 0);
        },

        formatDate(dateString) {
            const options = { year: 'numeric', month: 'long', day: 'numeric' };
            return new Date(dateString).toLocaleDateString('id-ID', options);
        }
    }
}
</script>
@endpush
@endsection