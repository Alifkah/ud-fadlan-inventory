@extends('layouts.app')

@section('title', 'Kategori Barang')
@section('page-title', 'Kategori Barang')
@section('page-description', 'Kelola dan pantau kategori barang')

@section('content')
<!-- Statistics Cards -->
<section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg">
        <p class="text-text-muted-light dark:text-text-muted-dark">Total Kategori</p>
        <p class="text-3xl font-bold my-2 text-text-light dark:text-text-dark">{{ $overallStats['total_categories'] }}</p>
    </div>
    <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg">
        <p class="text-text-muted-light dark:text-text-muted-dark">Kategori Aktif</p>
        <p class="text-3xl font-bold my-2 text-text-light dark:text-text-dark">{{ $overallStats['active_categories'] }}</p>
    </div>
    <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg">
        <p class="text-text-muted-light dark:text-text-muted-dark">Stok Menipis</p>
        <p class="text-3xl font-bold my-2 text-text-light dark:text-text-dark">{{ $overallStats['low_stock_items'] }}</p>
    </div>
    <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg">
        <p class="text-text-muted-light dark:text-text-muted-dark">Total Produk</p>
        <p class="text-3xl font-bold my-2 text-text-light dark:text-text-dark">{{ $overallStats['total_products'] }}</p>
    </div>
</section>

<!-- Categories Table -->
<section class="bg-card-light dark:bg-card-dark p-6 rounded-lg">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-text-light dark:text-text-dark">Daftar Kategori Barang</h2>
        <div class="flex items-center space-x-2">
            <button class="px-4 py-2 text-sm font-medium rounded-md bg-primary text-white" id="filterAll">Semua</button>
            <button class="px-4 py-2 text-sm font-medium rounded-md bg-gray-200 dark:bg-gray-600 text-text-light dark:text-text-dark" id="filterActive">Aktif</button>
            <button class="px-4 py-2 text-sm font-medium rounded-md bg-gray-200 dark:bg-gray-600 text-text-light dark:text-text-dark" id="filterInactive">Tidak Aktif</button>
            <button class="px-4 py-2 text-sm font-medium rounded-md bg-gray-200 dark:bg-gray-600 text-text-light dark:text-text-dark flex items-center">
                Filter <span class="material-icons text-sm ml-1">expand_more</span>
            </button>
            <button class="px-4 py-2 text-sm font-medium rounded-md bg-gray-200 dark:bg-gray-600 text-text-light dark:text-text-dark flex items-center" id="exportBtn">
                Export <span class="material-icons text-sm ml-1">file_download</span>
            </button>
            <button class="px-4 py-2 text-sm font-medium rounded-md bg-primary text-white flex items-center" id="openAddModal">
                <span class="material-icons text-sm mr-1">add</span>Tambah Kategori
            </button>
        </div>
    </div>

    <!-- Categories Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" id="categoriesGrid">
        @foreach($categoryStats as $category)
        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 flex flex-col category-card" data-status="{{ $category['is_active'] ? 'active' : 'inactive' }}">
            <div class="flex items-center mb-2">
                <span class="font-bold text-lg text-text-light dark:text-text-dark mr-2">{{ $category['name'] }}</span>
                @if($category['is_active'])
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300">AKTIF</span>
                @else
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300">TIDAK AKTIF</span>
                @endif
            </div>
            <p class="text-sm text-text-muted-light dark:text-text-muted-dark mb-4 flex-grow">
                {{ Str::limit($category['description'] ?? 'Tidak ada deskripsi', 50) }}
            </p>
            <div class="flex justify-between text-sm text-text-muted-light dark:text-text-muted-dark mb-4">
                <div>
                    <p class="text-lg font-bold text-text-light dark:text-text-dark">{{ $category['total_products'] }}</p>
                    <p>Produk</p>
                </div>
                <div>
                    <p class="text-lg font-bold text-text-light dark:text-text-dark">{{ $category['active_products'] }}</p>
                    <p>Aktif</p>
                </div>
                <div>
                    <p class="text-lg font-bold text-text-light dark:text-text-dark">{{ $category['low_stock_products'] }}</p>
                    <p>Stok Habis</p>
                </div>
            </div>
            <div class="flex space-x-2">
                <button class="flex-1 bg-primary text-white py-2 rounded-md text-sm font-medium view-category" 
                        data-id="{{ $category['id'] }}" 
                        data-name="{{ $category['name'] }}" 
                        data-description="{{ $category['description'] }}"
                        data-is-active="{{ $category['is_active'] }}"
                        data-total-products="{{ $category['total_products'] }}"
                        data-active-products="{{ $category['active_products'] }}"
                        data-low-stock="{{ $category['low_stock_products'] }}"
                        data-total-stock="{{ $category['total_stock'] }}">
                    Lihat
                </button>
                <button class="flex-1 bg-amber-500 text-white py-2 rounded-md text-sm font-medium edit-category" 
                        data-id="{{ $category['id'] }}">
                    Edit
                </button>
                @if($category['total_products'] == 0)
                <button class="flex-1 bg-red-500 text-white py-2 rounded-md text-sm font-medium delete-category" 
                        data-id="{{ $category['id'] }}" data-name="{{ $category['name'] }}">
                    Hapus
                </button>
                @endif
            </div>
        </div>
        @endforeach

        <!-- Add New Category Card -->
        <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 flex flex-col items-center justify-center text-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors" id="openAddCard">
            <div class="w-16 h-16 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center mb-4">
                <span class="material-icons text-4xl text-text-muted-light dark:text-text-muted-dark">add</span>
            </div>
            <p class="font-semibold text-text-light dark:text-text-dark">Tambah Kategori Baru</p>
        </div>
    </div>
</section>

<!-- Add Category Modal -->
<div class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 hidden" id="addCategoryModal">
    <div class="bg-card-light dark:bg-card-dark rounded-lg shadow-xl w-full max-w-md p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-text-light dark:text-text-dark">Tambah Kategori Baru</h3>
            <button class="text-text-muted-light dark:text-text-muted-dark hover:text-text-light dark:hover:text-text-dark closeModal">
                <span class="material-icons">close</span>
            </button>
        </div>
        <form id="addCategoryForm" novalidate>
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-text-muted-light dark:text-text-muted-dark mb-1" for="category_name_add">Nama Kategori</label>
                <input class="w-full bg-background-light dark:bg-background-dark border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-text-light dark:text-text-dark focus:outline-none focus:ring-1 focus:ring-primary" 
                       id="category_name_add" name="name" placeholder="Contoh: Bahan Bangunan" required type="text"/>
                <div class="invalid-feedback hidden text-red-500 text-sm mt-1">Nama kategori tidak boleh kosong.</div>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-text-muted-light dark:text-text-muted-dark mb-1" for="description_add">Deskripsi</label>
                <textarea class="w-full bg-background-light dark:bg-background-dark border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-text-light dark:text-text-dark focus:outline-none focus:ring-1 focus:ring-primary" 
                          id="description_add" name="description" placeholder="Deskripsi singkat mengenai kategori" rows="3"></textarea>
            </div>
            <div class="mb-6">
                <label class="flex items-center">
                    <input type="checkbox" name="is_active" id="is_active_add" checked class="rounded text-primary focus:ring-primary">
                    <span class="ml-2 text-sm text-text-light dark:text-text-dark">Kategori Aktif</span>
                </label>
            </div>
            <div class="flex justify-end space-x-3">
                <button class="px-4 py-2 text-sm font-medium rounded-md bg-gray-200 dark:bg-gray-600 text-text-light dark:text-text-dark hover:bg-gray-300 dark:hover:bg-gray-500 closeModal" type="button">Batal</button>
                <button class="px-4 py-2 text-sm font-medium rounded-md bg-primary text-white hover:bg-primary/90 flex items-center" type="submit">
                    <span class="material-icons text-sm mr-1">save</span> Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- View Category Modal -->
<div class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 hidden" id="viewCategoryModal">
    <div class="bg-card-light dark:bg-card-dark rounded-lg shadow-xl w-full max-w-2xl p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-text-light dark:text-text-dark">Detail Kategori</h3>
            <button class="text-text-muted-light dark:text-text-muted-dark hover:text-text-light dark:hover:text-text-dark closeModal">
                <span class="material-icons">close</span>
            </button>
        </div>
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-text-muted-light dark:text-text-muted-dark mb-1">Nama Kategori</label>
                <input class="w-full bg-background-light dark:bg-background-dark border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-text-light dark:text-text-dark" 
                       id="category_name_view" readonly type="text"/>
            </div>
            <div>
                <label class="block text-sm font-medium text-text-muted-light dark:text-text-muted-dark mb-1">Deskripsi</label>
                <textarea class="w-full bg-background-light dark:bg-background-dark border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-text-light dark:text-text-dark" 
                          id="description_view" readonly rows="3"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-text-muted-light dark:text-text-muted-dark mb-1">Status</label>
                <input class="w-full bg-background-light dark:bg-background-dark border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-text-light dark:text-text-dark" 
                       id="status_view" readonly type="text"/>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-text-muted-light dark:text-text-muted-dark mb-1">Total Produk</label>
                    <input class="w-full bg-background-light dark:bg-background-dark border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-text-light dark:text-text-dark" 
                           id="total_products_view" readonly type="text"/>
                </div>
                <div>
                    <label class="block text-sm font-medium text-text-muted-light dark:text-text-muted-dark mb-1">Produk Aktif</label>
                    <input class="w-full bg-background-light dark:bg-background-dark border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-text-light dark:text-text-dark" 
                           id="active_products_view" readonly type="text"/>
                </div>
                <div>
                    <label class="block text-sm font-medium text-text-muted-light dark:text-text-muted-dark mb-1">Stok Rendah</label>
                    <input class="w-full bg-background-light dark:bg-background-dark border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-text-light dark:text-text-dark" 
                           id="low_stock_view" readonly type="text"/>
                </div>
                <div>
                    <label class="block text-sm font-medium text-text-muted-light dark:text-text-muted-dark mb-1">Total Stok</label>
                    <input class="w-full bg-background-light dark:bg-background-dark border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-text-light dark:text-text-dark" 
                           id="total_stock_view" readonly type="text"/>
                </div>
            </div>
        </div>
        <div class="flex justify-end mt-6">
            <button class="px-4 py-2 text-sm font-medium rounded-md bg-gray-200 dark:bg-gray-600 text-text-light dark:text-text-dark hover:bg-gray-300 dark:hover:bg-gray-500 closeModal" type="button">Tutup</button>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 hidden" id="editCategoryModal">
    <div class="bg-card-light dark:bg-card-dark rounded-lg shadow-xl w-full max-w-md p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-text-light dark:text-text-dark">Edit Kategori</h3>
            <button class="text-text-muted-light dark:text-text-muted-dark hover:text-text-light dark:hover:text-text-dark closeModal">
                <span class="material-icons">close</span>
            </button>
        </div>
        <form id="editCategoryForm" novalidate>
            @csrf
            @method('PUT')
            <input type="hidden" id="edit_category_id" name="id">
            <div class="mb-4">
                <label class="block text-sm font-medium text-text-muted-light dark:text-text-muted-dark mb-1" for="category_name_edit">Nama Kategori</label>
                <input class="w-full bg-background-light dark:bg-background-dark border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-text-light dark:text-text-dark focus:outline-none focus:ring-1 focus:ring-primary" 
                       id="category_name_edit" name="name" placeholder="Contoh: Bahan Bangunan" required type="text"/>
                <div class="invalid-feedback hidden text-red-500 text-sm mt-1">Nama kategori tidak boleh kosong.</div>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-text-muted-light dark:text-text-muted-dark mb-1" for="description_edit">Deskripsi</label>
                <textarea class="w-full bg-background-light dark:bg-background-dark border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-text-light dark:text-text-dark focus:outline-none focus:ring-1 focus:ring-primary" 
                          id="description_edit" name="description" placeholder="Deskripsi singkat mengenai kategori" rows="3"></textarea>
            </div>
            <div class="mb-6">
                <label class="flex items-center">
                    <input type="checkbox" name="is_active" id="is_active_edit" class="rounded text-primary focus:ring-primary">
                    <span class="ml-2 text-sm text-text-light dark:text-text-dark">Kategori Aktif</span>
                </label>
            </div>
            <div class="flex justify-end space-x-3">
                <button class="px-4 py-2 text-sm font-medium rounded-md bg-gray-200 dark:bg-gray-600 text-text-light dark:text-text-dark hover:bg-gray-300 dark:hover:bg-gray-500 closeModal" type="button">Batal</button>
                <button class="px-4 py-2 text-sm font-medium rounded-md bg-primary text-white hover:bg-primary/90 flex items-center" type="submit">
                    <span class="material-icons text-sm mr-1">save</span> Update
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<!-- Include SheetJS Library from CDN -->
<script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>

<script>
// Wait for library to load
let xlsxLoadAttempts = 0;
const maxAttempts = 10;

function checkXLSXLoaded() {
    if (typeof XLSX !== 'undefined') {
        console.log('SheetJS library loaded successfully');
        return true;
    }
    
    xlsxLoadAttempts++;
    if (xlsxLoadAttempts < maxAttempts) {
        setTimeout(checkXLSXLoaded, 100);
    } else {
        console.error('SheetJS library failed to load after multiple attempts');
    }
    return false;
}

// Start checking
checkXLSXLoaded();

document.addEventListener('DOMContentLoaded', function () {
    const openModalButtons = document.querySelectorAll('[data-modal]');
    const closeModalButtons = document.querySelectorAll('.closeModal');
    const openAddModalButton = document.getElementById('openAddModal');
    const openAddCard = document.getElementById('openAddCard');
    const addCategoryModal = document.getElementById('addCategoryModal');
    const viewCategoryModal = document.getElementById('viewCategoryModal');
    const editCategoryModal = document.getElementById('editCategoryModal');

    // Filter functions
    const filterAll = document.getElementById('filterAll');
    const filterActive = document.getElementById('filterActive');
    const filterInactive = document.getElementById('filterInactive');
    const categoryCards = document.querySelectorAll('.category-card');

    // Modal functions
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    }

    function closeModal(modalElement) {
        modalElement.classList.add('hidden');
        modalElement.classList.remove('flex');
        const form = modalElement.querySelector('form');
        if (form) {
            form.reset();
            form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            form.querySelectorAll('.invalid-feedback').forEach(el => el.classList.add('hidden'));
            form.querySelectorAll('.border-red-500').forEach(el => el.classList.remove('border-red-500'));
        }
    }

    // Filter functionality
    function filterCategories(status) {
        [filterAll, filterActive, filterInactive].forEach(btn => {
            btn.classList.remove('bg-primary', 'text-white');
            btn.classList.add('bg-gray-200', 'dark:bg-gray-600', 'text-text-light', 'dark:text-text-dark');
        });

        categoryCards.forEach(card => {
            if (status === 'all') {
                card.style.display = 'block';
            } else {
                const cardStatus = card.dataset.status;
                card.style.display = cardStatus === status ? 'block' : 'none';
            }
        });

        const activeButton = status === 'all' ? filterAll : 
                           status === 'active' ? filterActive : filterInactive;
        activeButton.classList.remove('bg-gray-200', 'dark:bg-gray-600', 'text-text-light', 'dark:text-text-dark');
        activeButton.classList.add('bg-primary', 'text-white');
    }

    if (filterAll) filterAll.addEventListener('click', () => filterCategories('all'));
    if (filterActive) filterActive.addEventListener('click', () => filterCategories('active'));
    if (filterInactive) filterInactive.addEventListener('click', () => filterCategories('inactive'));

    // Modal event listeners
    if (openAddModalButton) {
        openAddModalButton.addEventListener('click', () => openModal('addCategoryModal'));
    }

    if (openAddCard) {
        openAddCard.addEventListener('click', () => openModal('addCategoryModal'));
    }

    closeModalButtons.forEach(button => {
        button.addEventListener('click', () => {
            const modal = button.closest('.fixed');
            if (modal) {
                closeModal(modal);
            }
        });
    });

    window.addEventListener('click', function(event) {
        if (event.target.classList.contains('fixed') && event.target.classList.contains('bg-gray-900')) {
            closeModal(event.target);
        }
    });

    // View category
    document.querySelectorAll('.view-category').forEach(button => {
        button.addEventListener('click', function() {
            const name = this.dataset.name || '';
            const description = this.dataset.description || 'Tidak ada deskripsi';
            const isActive = this.dataset.isActive === '1';
            const totalProducts = this.dataset.totalProducts || '0';
            const activeProducts = this.dataset.activeProducts || '0';
            const lowStock = this.dataset.lowStock || '0';
            const totalStock = this.dataset.totalStock || '0';
            
            // Populate modal fields
            const nameInput = document.getElementById('category_name_view');
            const descInput = document.getElementById('description_view');
            const statusInput = document.getElementById('status_view');
            const totalProdInput = document.getElementById('total_products_view');
            const activeProdInput = document.getElementById('active_products_view');
            const lowStockInput = document.getElementById('low_stock_view');
            const totalStockInput = document.getElementById('total_stock_view');

            if (nameInput) nameInput.value = name;
            if (descInput) descInput.value = description;
            if (statusInput) statusInput.value = isActive ? 'Aktif' : 'Tidak Aktif';
            if (totalProdInput) totalProdInput.value = totalProducts;
            if (activeProdInput) activeProdInput.value = activeProducts;
            if (lowStockInput) lowStockInput.value = lowStock;
            if (totalStockInput) totalStockInput.value = totalStock;
            
            openModal('viewCategoryModal');
        });
    });

    // Edit category
    document.querySelectorAll('.edit-category').forEach(button => {
        button.addEventListener('click', function() {
            const categoryId = this.dataset.id;
            
            if (!categoryId) {
                alert('ID kategori tidak ditemukan');
                return;
            }
            
            fetch(`/categories/${categoryId}/edit`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.data) {
                    const category = data.data;
                    const idInput = document.getElementById('edit_category_id');
                    const nameInput = document.getElementById('category_name_edit');
                    const descInput = document.getElementById('description_edit');
                    const activeInput = document.getElementById('is_active_edit');

                    if (idInput) idInput.value = category.id;
                    if (nameInput) nameInput.value = category.name || '';
                    if (descInput) descInput.value = category.description || '';
                    if (activeInput) activeInput.checked = category.is_active == 1;
                    
                    openModal('editCategoryModal');
                } else {
                    alert(data.message || 'Gagal mengambil data kategori');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Gagal mengambil data kategori. Silakan coba lagi.');
            });
        });
    });

    // Delete category
    document.querySelectorAll('.delete-category').forEach(button => {
        button.addEventListener('click', function() {
            const categoryId = this.dataset.id;
            const categoryName = this.dataset.name;
            
            if (!categoryId) {
                alert('ID kategori tidak ditemukan');
                return;
            }
            
            if (confirm(`Apakah Anda yakin ingin menghapus kategori "${categoryName}"?`)) {
                fetch(`/categories/${categoryId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Kategori berhasil dihapus');
                        location.reload();
                    } else {
                        alert(data.message || 'Gagal menghapus kategori');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat menghapus kategori');
                });
            }
        });
    });

    // Form validation
    const validateForm = (form) => {
        let isValid = true;
        const inputs = form.querySelectorAll('[required]');
        
        inputs.forEach(input => {
            const errorDiv = input.parentNode.querySelector('.invalid-feedback');
            if (input.value.trim() === '') {
                input.classList.add('border-red-500');
                if (errorDiv) {
                    errorDiv.classList.remove('hidden');
                }
                isValid = false;
            } else {
                input.classList.remove('border-red-500');
                if (errorDiv) {
                    errorDiv.classList.add('hidden');
                }
            }
        });
        
        return isValid;
    }

    // Add category form
    const addForm = document.getElementById('addCategoryForm');
    if (addForm) {
        addForm.addEventListener('submit', function (event) {
            event.preventDefault();
            
            if (!validateForm(this)) {
                return;
            }
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="material-icons text-sm mr-1 animate-spin">sync</span> Menyimpan...';
            
            fetch('/categories', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
                
                if (data.success) {
                    alert('Kategori berhasil ditambahkan');
                    closeModal(addCategoryModal);
                    location.reload();
                } else {
                    if (data.errors) {
                        Object.keys(data.errors).forEach(key => {
                            const input = addForm.querySelector(`[name="${key}"]`);
                            if (input) {
                                input.classList.add('border-red-500');
                                const errorDiv = input.parentNode.querySelector('.invalid-feedback');
                                if (errorDiv) {
                                    errorDiv.textContent = data.errors[key][0];
                                    errorDiv.classList.remove('hidden');
                                }
                            }
                        });
                    } else {
                        alert(data.message || 'Gagal menambahkan kategori');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
                alert('Terjadi kesalahan saat menambahkan kategori');
            });
        });
    }

    // Edit category form
    const editForm = document.getElementById('editCategoryForm');
    if (editForm) {
        editForm.addEventListener('submit', function (event) {
            event.preventDefault();
            
            if (!validateForm(this)) {
                return;
            }
            
            const categoryId = document.getElementById('edit_category_id').value;
            if (!categoryId) {
                alert('ID kategori tidak ditemukan');
                return;
            }
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="material-icons text-sm mr-1 animate-spin">sync</span> Memperbarui...';
            
            fetch(`/categories/${categoryId}`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
                
                if (data.success) {
                    alert('Kategori berhasil diperbarui');
                    closeModal(editCategoryModal);
                    location.reload();
                } else {
                    if (data.errors) {
                        Object.keys(data.errors).forEach(key => {
                            const input = editForm.querySelector(`[name="${key}"]`);
                            if (input) {
                                input.classList.add('border-red-500');
                                const errorDiv = input.parentNode.querySelector('.invalid-feedback');
                                if (errorDiv) {
                                    errorDiv.textContent = data.errors[key][0];
                                    errorDiv.classList.remove('hidden');
                                }
                            }
                        });
                    } else {
                        alert(data.message || 'Gagal memperbarui kategori');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
                alert('Terjadi kesalahan saat memperbarui kategori');
            });
        });
    }

    // Export functionality
    const exportBtn = document.getElementById('exportBtn');
    if (exportBtn) {
        exportBtn.addEventListener('click', async function() {
            try {
                // Wait for XLSX to be available
                let waitAttempts = 0;
                while (typeof XLSX === 'undefined' && waitAttempts < 20) {
                    await new Promise(resolve => setTimeout(resolve, 100));
                    waitAttempts++;
                }

                // Check if SheetJS is loaded
                if (typeof XLSX === 'undefined') {
                    // Fallback: Load library dynamically
                    const script = document.createElement('script');
                    script.src = 'https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js';
                    document.head.appendChild(script);
                    
                    await new Promise((resolve, reject) => {
                        script.onload = resolve;
                        script.onerror = reject;
                    });
                    
                    // Wait a bit more for XLSX to be available
                    await new Promise(resolve => setTimeout(resolve, 200));
                }

                // Final check
                if (typeof XLSX === 'undefined') {
                    alert('Library export tidak tersedia. Harap refresh halaman.');
                    return;
                }

                // Show loading state
                const originalContent = this.innerHTML;
                this.disabled = true;
                this.innerHTML = '<span class="material-icons text-sm mr-1 animate-spin">sync</span>Mengekspor...';

                // Fetch export data from server
                const response = await fetch('/categories/export', {
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                const result = await response.json();

                if (result.success && result.data && result.data.length > 0) {
                    // Create worksheet from JSON data
                    const worksheet = XLSX.utils.json_to_sheet(result.data);
                    
                    // Create new workbook
                    const workbook = XLSX.utils.book_new();
                    XLSX.utils.book_append_sheet(workbook, worksheet, 'Kategori Barang');

                    // Set column widths
                    const wscols = [
                        {wch: 25}, // Nama Kategori
                        {wch: 40}, // Deskripsi
                        {wch: 15}, // Total Produk
                        {wch: 15}, // Total Stok
                        {wch: 20}, // Produk Stok Rendah
                        {wch: 20}, // Nilai Stok
                        {wch: 15}, // Status
                        {wch: 20}, // Dibuat
                        {wch: 20}  // Diperbarui
                    ];
                    worksheet['!cols'] = wscols;

                    // Generate and download
                    XLSX.writeFile(workbook, result.filename);

                    // Show success message
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center space-x-2';
                    alertDiv.innerHTML = `
                        <span class="material-icons">check_circle</span>
                        <span>Data berhasil diekspor (${result.data.length} kategori)</span>
                    `;
                    document.body.appendChild(alertDiv);
                    setTimeout(() => alertDiv.remove(), 3000);
                } else {
                    throw new Error('Tidak ada data untuk diekspor');
                }

                // Restore button
                this.disabled = false;
                this.innerHTML = originalContent;

            } catch (error) {
                console.error('Export error:', error);
                alert('Terjadi kesalahan saat mengekspor data: ' + error.message);
                
                // Restore button
                this.disabled = false;
                this.innerHTML = 'Export <span class="material-icons text-sm ml-1">file_download</span>';
            }
        });
    }
});
</script>
@endpush