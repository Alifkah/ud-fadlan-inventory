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
                        data-id="{{ $category['id'] }}" data-name="{{ $category['name'] }}" data-description="{{ $category['description'] }}">
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
        <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 flex flex-col items-center justify-center text-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800" id="openAddCard">
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
                <div class="invalid-feedback hidden text-red-500 text-sm mt-1">Deskripsi tidak boleh kosong.</div>
            </div>
            <div class="mb-6">
                <label class="flex items-center">
                    <input type="checkbox" name="is_active" id="is_active_add" checked class="rounded">
                    <span class="ml-2 text-sm text-text-light dark:text-text-dark">Kategori Aktif</span>
                </label>
            </div>
            <div class="flex justify-end space-x-3">
                <button class="px-4 py-2 text-sm font-medium rounded-md bg-gray-200 dark:bg-gray-600 text-text-light dark:text-text-dark closeModal" type="button">Batal</button>
                <button class="px-4 py-2 text-sm font-medium rounded-md bg-primary text-white flex items-center" type="submit">
                    <span class="material-icons text-sm mr-1">save</span> Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- View Category Modal -->
<div class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 hidden" id="viewCategoryModal">
    <div class="bg-card-light dark:bg-card-dark rounded-lg shadow-xl w-full max-w-md p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-text-light dark:text-text-dark">Detail Kategori</h3>
            <button class="text-text-muted-light dark:text-text-muted-dark hover:text-text-light dark:hover:text-text-dark closeModal">
                <span class="material-icons">close</span>
            </button>
        </div>
        <form>
            <div class="mb-4">
                <label class="block text-sm font-medium text-text-muted-light dark:text-text-muted-dark mb-1" for="category_name_view">Nama Kategori</label>
                <input class="w-full bg-background-light dark:bg-background-dark border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-text-light dark:text-text-dark focus:outline-none focus:ring-1 focus:ring-primary" 
                       id="category_name_view" readonly type="text"/>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-text-muted-light dark:text-text-muted-dark mb-1" for="description_view">Deskripsi</label>
                <textarea class="w-full bg-background-light dark:bg-background-dark border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-text-light dark:text-text-dark focus:outline-none focus:ring-1 focus:ring-primary" 
                          id="description_view" readonly rows="3"></textarea>
            </div>
            <div class="flex justify-end">
                <button class="px-4 py-2 text-sm font-medium rounded-md bg-gray-200 dark:bg-gray-600 text-text-light dark:text-text-dark closeModal" type="button">Tutup</button>
            </div>
        </form>
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
                <div class="invalid-feedback hidden text-red-500 text-sm mt-1">Deskripsi tidak boleh kosong.</div>
            </div>
            <div class="mb-6">
                <label class="flex items-center">
                    <input type="checkbox" name="is_active" id="is_active_edit" class="rounded">
                    <span class="ml-2 text-sm text-text-light dark:text-text-dark">Kategori Aktif</span>
                </label>
            </div>
            <div class="flex justify-end space-x-3">
                <button class="px-4 py-2 text-sm font-medium rounded-md bg-gray-200 dark:bg-gray-600 text-text-light dark:text-text-dark closeModal" type="button">Batal</button>
                <button class="px-4 py-2 text-sm font-medium rounded-md bg-primary text-white flex items-center" type="submit">
                    <span class="material-icons text-sm mr-1">save</span> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
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
        // Reset form inside the modal if it exists
        const form = modalElement.querySelector('form');
        if (form) {
            form.reset();
            form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            form.querySelectorAll('.invalid-feedback').forEach(el => el.classList.add('hidden'));
        }
    }

    // Filter functionality
    function filterCategories(status) {
        // Update button states
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

        // Update active button
        const activeButton = status === 'all' ? filterAll : 
                           status === 'active' ? filterActive : filterInactive;
        activeButton.classList.remove('bg-gray-200', 'dark:bg-gray-600', 'text-text-light', 'dark:text-text-dark');
        activeButton.classList.add('bg-primary', 'text-white');
    }

    // Event listeners for filters
    filterAll.addEventListener('click', () => filterCategories('all'));
    filterActive.addEventListener('click', () => filterCategories('active'));
    filterInactive.addEventListener('click', () => filterCategories('inactive'));

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
            closeModal(modal);
        });
    });

    window.addEventListener('click', function(event) {
        if (event.target.classList.contains('fixed')) {
            closeModal(event.target);
        }
    });

    // View category
    document.querySelectorAll('.view-category').forEach(button => {
        button.addEventListener('click', function() {
            const name = this.dataset.name;
            const description = this.dataset.description;
            
            document.getElementById('category_name_view').value = name;
            document.getElementById('description_view').value = description || '';
            
            openModal('viewCategoryModal');
        });
    });

    // Edit category
    document.querySelectorAll('.edit-category').forEach(button => {
        button.addEventListener('click', function() {
            const categoryId = this.dataset.id;
            
            fetch(`/categories/${categoryId}/edit`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const category = data.data;
                        document.getElementById('edit_category_id').value = category.id;
                        document.getElementById('category_name_edit').value = category.name;
                        document.getElementById('description_edit').value = category.description || '';
                        document.getElementById('is_active_edit').checked = category.is_active;
                        
                        openModal('editCategoryModal');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Gagal mengambil data kategori');
                });
        });
    });

    // Delete category
    document.querySelectorAll('.delete-category').forEach(button => {
        button.addEventListener('click', function() {
            const categoryId = this.dataset.id;
            const categoryName = this.dataset.name;
            
            if (confirm(`Apakah Anda yakin ingin menghapus kategori "${categoryName}"?`)) {
                fetch(`/categories/${categoryId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
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
            
            fetch('/categories', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Kategori berhasil ditambahkan');
                    closeModal(this.closest('.fixed'));
                    location.reload();
                } else {
                    alert(data.message || 'Gagal menambahkan kategori');
                }
            })
            .catch(error => {
                console.error('Error:', error);
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
            const formData = new FormData(this);
            
            fetch(`/categories/${categoryId}`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Kategori berhasil diperbarui');
                    closeModal(this.closest('.fixed'));
                    location.reload();
                } else {
                    alert(data.message || 'Gagal memperbarui kategori');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat memperbarui kategori');
            });
        });
    }

    // Export functionality
    document.getElementById('exportBtn').addEventListener('click', function() {
        window.location.href = '/categories/export';
    });
});
</script>
@endpush