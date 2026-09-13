@extends('layouts.app')

@section('title', 'Data Supplier')
@section('page-title', 'Data Supplier')
@section('page-description', 'Kelola data supplier toko Anda')

@push('styles')
<style>
    .modal,
    .toast,
    .dropdown-menu {
        display: none;
    }
    .modal.show,
    .toast.show,
    .dropdown.open .dropdown-menu {
        display: flex;
    }
    .tab-content {
        display: none;
    }
    .tab-content.active {
        display: block;
    }
</style>
@endpush

@section('content')
<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-card-light dark:bg-card-dark rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-text-muted-light dark:text-text-muted-dark text-sm">Total Supplier</p>
                <h3 class="text-2xl font-bold text-text-light dark:text-text-dark mt-1">{{ $stats['total_suppliers'] }}</h3>
            </div>
            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                <span class="material-icons text-primary">local_shipping</span>
            </div>
        </div>
    </div>

    <div class="bg-card-light dark:bg-card-dark rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-text-muted-light dark:text-text-muted-dark text-sm">Supplier Aktif</p>
                <h3 class="text-2xl font-bold text-green-600 mt-1">{{ $stats['active_suppliers'] }}</h3>
            </div>
            <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                <span class="material-icons text-green-600">check_circle</span>
            </div>
        </div>
    </div>

    <div class="bg-card-light dark:bg-card-dark rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-text-muted-light dark:text-text-muted-dark text-sm">Supplier Tidak Aktif</p>
                <h3 class="text-2xl font-bold text-red-600 mt-1">{{ $stats['inactive_suppliers'] }}</h3>
            </div>
            <div class="w-12 h-12 bg-red-100 dark:bg-red-900 rounded-full flex items-center justify-center">
                <span class="material-icons text-red-600">cancel</span>
            </div>
        </div>
    </div>

    <div class="bg-card-light dark:bg-card-dark rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-text-muted-light dark:text-text-muted-dark text-sm">Supplier Baru (30 Hari)</p>
                <h3 class="text-2xl font-bold text-orange-600 mt-1">{{ $stats['recent_suppliers'] }}</h3>
            </div>
            <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900 rounded-full flex items-center justify-center">
                <span class="material-icons text-orange-600">fiber_new</span>
            </div>
        </div>
    </div>
</div>

<!-- Filter and Table Card -->
<div class="bg-card-light dark:bg-card-dark rounded-lg shadow">
    <div class="p-6">
        <!-- Filter Section -->
        <form action="{{ route('suppliers.index') }}" method="GET" id="filterForm">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                <div class="relative w-full md:w-auto">
                    <span class="material-icons absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">search</span>
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ request('search') }}"
                        class="pl-10 pr-4 py-2 w-full md:w-80 border border-gray-300 dark:border-gray-600 rounded-lg bg-background-light dark:bg-background-dark text-sm focus:ring-primary focus:border-primary" 
                        placeholder="Cari supplier..."
                    />
                </div>
                
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 w-full md:w-auto">
                    <div class="flex items-center gap-2 flex-wrap">
                        <select 
                            name="status" 
                            class="w-full sm:w-auto px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-background-light dark:bg-background-dark text-sm focus:ring-primary focus:border-primary"
                        >
                            <option value="">Semua Status</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
                        </select>
                        
                        <select 
                            name="sort_field" 
                            class="w-full sm:w-auto px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-background-light dark:bg-background-dark text-sm focus:ring-primary focus:border-primary"
                        >
                            <option value="created_at" {{ request('sort_field') == 'created_at' ? 'selected' : '' }}>Tanggal Terbaru</option>
                            <option value="name" {{ request('sort_field') == 'name' ? 'selected' : '' }}>Nama</option>
                            <option value="code" {{ request('sort_field') == 'code' ? 'selected' : '' }}>Kode</option>
                        </select>

                       <div class="relative dropdown" id="export-dropdown">
                            <button 
                                type="button"
                                class="flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 w-full sm:w-auto justify-center" 
                                id="export-btn"
                            >
                                <span class="material-icons text-sm">download</span> Ekspor
                                <span class="material-icons text-sm">arrow_drop_down</span>
                            </button>
                            <div class="dropdown-menu absolute right-0 mt-2 w-48 bg-card-light dark:bg-card-dark rounded-md shadow-lg py-1 z-10">
                                <a href="#" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    Ekspor ke CSV
                                </a>
                                <a href="#" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    Ekspor ke Excel
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <button 
                        type="button"
                        class="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg text-sm w-full sm:w-auto justify-center" 
                        id="add-supplier-btn"
                    >
                        <span class="material-icons text-sm">add_circle</span> Tambah Supplier
                    </button>
                </div>
            </div>
        </form>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 dark:text-gray-300 uppercase bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3">Kode</th>
                        <th class="px-6 py-3">Nama Supplier</th>
                        <th class="px-6 py-3">Perusahaan</th>
                        <th class="px-6 py-3">Email</th>
                        <th class="px-6 py-3">Telepon</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Tanggal Kerja Sama</th>
                        <th class="px-6 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="supplier-table-body">
                    @forelse($suppliers as $supplier)
                    <tr class="bg-card-light dark:bg-card-dark border-b dark:border-gray-700" data-supplier-id="{{ $supplier->id }}">
                        <td class="px-6 py-4">{{ $supplier->code }}</td>
                        <td class="px-6 py-4">{{ $supplier->name }}</td>
                        <td class="px-6 py-4">{{ $supplier->company_name ?? '-' }}</td>
                        <td class="px-6 py-4">{{ $supplier->email ?? '-' }}</td>
                        <td class="px-6 py-4">{{ $supplier->phone ?? '-' }}</td>
                        <td class="px-6 py-4">
                            @if($supplier->is_active)
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">Aktif</span>
                            @else
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">Tidak Aktif</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">{{ $supplier->created_at->format('d/m/Y') }}</td>
                        <td class="px-6 py-4 flex justify-center gap-2">
                            <button class="px-3 py-1 bg-blue-500 text-white rounded text-xs view-btn" data-id="{{ $supplier->id }}">Lihat</button>
                            <button class="px-3 py-1 bg-yellow-500 text-white rounded text-xs edit-btn" data-id="{{ $supplier->id }}">Edit</button>
                            <button class="px-3 py-1 bg-red-600 text-white rounded text-xs delete-btn" data-id="{{ $supplier->id }}">Hapus</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-8 text-center text-text-muted-light dark:text-text-muted-dark">
                            <span class="material-icons text-4xl mb-2">inbox</span>
                            <p>Tidak ada data supplier</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $suppliers->links() }}
        </div>
    </div>
</div>

<!-- Modal Supplier -->
<div class="modal fixed inset-0 bg-black bg-opacity-50 justify-center items-center z-50" id="supplier-modal">
    <div class="bg-card-light dark:bg-card-dark p-8 rounded-lg shadow-lg w-full max-w-3xl max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white" id="modal-title">Manajemen Supplier</h2>
            <button class="text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-white" id="close-modal-btn">
                <span class="material-icons">close</span>
            </button>
        </div>

        <!-- Tabs -->
        <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
            <nav class="-mb-px flex space-x-6" id="modal-tabs">
                <button class="tab-btn py-4 px-1 border-b-2 font-medium text-sm border-primary text-primary" data-tab="create-edit">
                    Buat/Edit
                </button>
                <button class="tab-btn py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:border-gray-600" data-tab="view">
                    Lihat
                </button>
                <button class="tab-btn py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:border-gray-600" data-tab="delete">
                    Hapus
                </button>
            </nav>
        </div>

        <!-- Tab Contents -->
        <div>
            <!-- Create/Edit Tab -->
            <div class="tab-content active" id="create-edit-tab">
                <form id="supplier-form">
                    @csrf
                    <input type="hidden" id="supplier-id-hidden" name="id">
                    <input type="hidden" id="form-method" name="_method" value="POST">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="supplier-name">Nama Supplier <span class="text-red-500">*</span></label>
                            <input 
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-background-light dark:bg-background-dark text-sm focus:ring-primary focus:border-primary" 
                                id="supplier-name" 
                                name="name" 
                                required 
                                type="text"
                            />
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="supplier-code">Kode Supplier</label>
                            <input 
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-background-light dark:bg-background-dark text-sm focus:ring-primary focus:border-primary" 
                                id="supplier-code" 
                                name="code" 
                                type="text"
                                placeholder="Otomatis jika dikosongkan"
                            />
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="supplier-company">Nama Perusahaan</label>
                            <input 
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-background-light dark:bg-background-dark text-sm focus:ring-primary focus:border-primary" 
                                id="supplier-company" 
                                name="company_name" 
                                type="text"
                            />
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="supplier-email">Email</label>
                            <input 
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-background-light dark:bg-background-dark text-sm focus:ring-primary focus:border-primary" 
                                id="supplier-email" 
                                name="email" 
                                type="email"
                            />
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="supplier-phone">Telepon <span class="text-red-500">*</span></label>
                            <input 
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-background-light dark:bg-background-dark text-sm focus:ring-primary focus:border-primary" 
                                id="supplier-phone" 
                                name="phone" 
                                required
                                type="tel"
                            />
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="supplier-address">Alamat</label>
                            <textarea 
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-background-light dark:bg-background-dark text-sm focus:ring-primary focus:border-primary" 
                                id="supplier-address" 
                                name="address" 
                                rows="3"
                            ></textarea>
                        </div>

                        <div class="md:col-span-2">
                            <label class="flex items-center">
                                <input 
                                    type="checkbox" 
                                    name="is_active" 
                                    id="supplier-active"
                                    value="1"
                                    checked
                                    class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary dark:focus:ring-primary dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                                >
                                <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">Supplier Aktif</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="mt-8 flex justify-end gap-4">
                        <button 
                            class="px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700" 
                            id="cancel-btn" 
                            type="button"
                        >
                            Batal
                        </button>
                        <button 
                            class="px-6 py-2 bg-primary text-white rounded-lg text-sm" 
                            id="save-btn" 
                            type="submit"
                        >
                            Simpan
                        </button>
                    </div>
                </form>
            </div>

            <!-- View Tab -->
            <div class="tab-content" id="view-tab">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <p class="font-medium text-gray-500 dark:text-gray-400">Kode Supplier</p>
                        <p class="text-gray-800 dark:text-white" id="view-code">-</p>
                    </div>
                    <div>
                        <p class="font-medium text-gray-500 dark:text-gray-400">Nama Supplier</p>
                        <p class="text-gray-800 dark:text-white" id="view-name">-</p>
                    </div>
                    <div>
                        <p class="font-medium text-gray-500 dark:text-gray-400">Nama Perusahaan</p>
                        <p class="text-gray-800 dark:text-white" id="view-company">-</p>
                    </div>
                    <div>
                        <p class="font-medium text-gray-500 dark:text-gray-400">Email</p>
                        <p class="text-gray-800 dark:text-white" id="view-email">-</p>
                    </div>
                    <div>
                        <p class="font-medium text-gray-500 dark:text-gray-400">Telepon</p>
                        <p class="text-gray-800 dark:text-white" id="view-phone">-</p>
                    </div>
                    <div>
                        <p class="font-medium text-gray-500 dark:text-gray-400">Status</p>
                        <p class="text-gray-800 dark:text-white" id="view-status">-</p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="font-medium text-gray-500 dark:text-gray-400">Alamat</p>
                        <p class="text-gray-800 dark:text-white" id="view-address">-</p>
                    </div>
                    <div>
                        <p class="font-medium text-gray-500 dark:text-gray-400">Tanggal Kerja Sama</p>
                        <p class="text-gray-800 dark:text-white" id="view-join-date">-</p>
                    </div>
                </div>
            </div>

            <!-- Delete Tab -->
            <div class="tab-content" id="delete-tab">
                <div class="text-center">
                    <span class="material-icons text-6xl text-red-500 mb-4">warning_amber</span>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Hapus Supplier</h3>
                    <div class="mt-2">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Apakah Anda yakin ingin menghapus data supplier ini secara permanen? Tindakan ini tidak dapat diurungkan.
                        </p>
                        <p class="text-sm font-semibold text-gray-800 dark:text-white mt-2" id="delete-supplier-info"></p>
                    </div>
                    <div class="mt-8 flex justify-center gap-4">
                        <button 
                            class="px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700" 
                            id="cancel-delete-btn" 
                            type="button"
                        >
                            Batal
                        </button>
                        <button 
                            class="px-6 py-2 bg-red-600 text-white rounded-lg text-sm" 
                            id="confirm-delete-btn" 
                            type="button"
                        >
                            Hapus
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast Notifications -->
<div class="fixed top-5 right-5 z-50 space-y-3" id="toast-container">
    <div class="toast items-center p-4 w-full max-w-xs text-gray-500 bg-white rounded-lg shadow dark:text-gray-400 dark:bg-gray-800" id="success-toast" role="alert">
        <div class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 text-green-500 bg-green-100 rounded-lg dark:bg-green-800 dark:text-green-200">
            <span class="material-icons">check_circle</span>
        </div>
        <div class="ml-3 text-sm font-normal" id="success-toast-message">Operasi berhasil</div>
        <button 
            aria-label="Close" 
            class="ml-auto -mx-1.5 -my-1.5 bg-white text-gray-400 hover:text-gray-900 rounded-lg focus:ring-2 focus:ring-gray-300 p-1.5 hover:bg-gray-100 inline-flex h-8 w-8 dark:text-gray-500 dark:hover:text-white dark:bg-gray-800 dark:hover:bg-gray-700" 
            onclick="this.parentElement.classList.remove('show')" 
            type="button"
        >
            <span class="sr-only">Close</span>
            <span class="material-icons">close</span>
        </button>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", () => {
    const addSupplierBtn = document.getElementById("add-supplier-btn");
    const supplierModal = document.getElementById("supplier-modal");
    const closeModalBtn = document.getElementById("close-modal-btn");
    const supplierForm = document.getElementById("supplier-form");
    const modalTabs = document.getElementById("modal-tabs");
    const tabContents = document.querySelectorAll(".tab-content");
    const tabBtns = document.querySelectorAll(".tab-btn");
    const successToast = document.getElementById("success-toast");
    const successToastMessage = document.getElementById("success-toast-message");
    const cancelBtn = document.getElementById("cancel-btn");
    const cancelDeleteBtn = document.getElementById("cancel-delete-btn");
    const confirmDeleteBtn = document.getElementById("confirm-delete-btn");
    const exportDropdown = document.getElementById('export-dropdown');
    const exportBtn = document.getElementById('export-btn');
    
    let currentSupplierId = null;
    let isEditMode = false;

    // Modal Functions
    const openModal = () => supplierModal.classList.add("show");
    const closeModal = () => {
        supplierModal.classList.remove("show");
        resetForm();
    };

    // Toast Functions
    const showToast = (message) => {
        successToastMessage.textContent = message;
        successToast.classList.add('show');
        setTimeout(() => {
            successToast.classList.remove('show');
        }, 3000);
    };

    // Tab Switching
    const switchTab = (tabName) => {
        tabBtns.forEach(btn => {
            if (btn.dataset.tab === tabName) {
                btn.classList.add('border-primary', 'text-primary');
                btn.classList.remove('border-transparent', 'text-gray-500');
            } else {
                btn.classList.remove('border-primary', 'text-primary');
                btn.classList.add('border-transparent', 'text-gray-500');
            }
        });

        tabContents.forEach(content => {
            content.classList.remove("active");
        });
        document.getElementById(`${tabName}-tab`).classList.add("active");
    };

    modalTabs.addEventListener("click", (e) => {
        if (e.target.classList.contains("tab-btn")) {
            switchTab(e.target.dataset.tab);
        }
    });

    // Reset Form
    const resetForm = () => {
        supplierForm.reset();
        document.getElementById('supplier-id-hidden').value = '';
        document.getElementById('form-method').value = 'POST';
        document.getElementById('supplier-active').checked = true;
        currentSupplierId = null;
        isEditMode = false;
        document.getElementById('save-btn').textContent = 'Simpan';
    };

    // Add Supplier
    addSupplierBtn.addEventListener("click", () => {
        resetForm();
        switchTab('create-edit');
        openModal();
    });

    // Close Modal
    closeModalBtn.addEventListener("click", closeModal);
    cancelBtn.addEventListener("click", closeModal);
    cancelDeleteBtn.addEventListener("click", () => switchTab('create-edit'));

    // Submit Form
    supplierForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        
        const formData = new FormData(supplierForm);
        const data = {
            name: formData.get('name'),
            code: formData.get('code'),
            company_name: formData.get('company_name'),
            email: formData.get('email'),
            phone: formData.get('phone'),
            address: formData.get('address'),
            is_active: formData.get('is_active') ? 1 : 0,
            _token: '{{ csrf_token() }}'
        };

        const url = isEditMode 
            ? `/suppliers/${currentSupplierId}` 
            : '{{ route("suppliers.store") }}';
        
        if (isEditMode) {
            data._method = 'PUT';
        }

        // Disable submit button
        const saveBtn = document.getElementById('save-btn');
        const originalText = saveBtn.textContent;
        saveBtn.disabled = true;
        saveBtn.textContent = 'Menyimpan...';

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                showToast(result.message);
                closeModal();
                setTimeout(() => location.reload(), 1000);
            } else {
                if (result.errors) {
                    const errorMessages = Object.values(result.errors).flat().join('\n');
                    alert('Validasi gagal:\n' + errorMessages);
                } else {
                    alert(result.message || 'Terjadi kesalahan');
                }
                
                saveBtn.disabled = false;
                saveBtn.textContent = originalText;
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat menyimpan data: ' + error.message);
            
            saveBtn.disabled = false;
            saveBtn.textContent = originalText;
        }
    });

    // View Supplier
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const supplierId = btn.dataset.id;
            
            try {
                const response = await fetch(`/suppliers/${supplierId}`, {
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
                    const supplier = result.data;
                    document.getElementById('view-code').textContent = supplier.code || '-';
                    document.getElementById('view-name').textContent = supplier.name || '-';
                    document.getElementById('view-company').textContent = supplier.company_name || '-';
                    document.getElementById('view-email').textContent = supplier.email || '-';
                    document.getElementById('view-phone').textContent = supplier.phone || '-';
                    document.getElementById('view-address').textContent = supplier.address || '-';
                    
                    if (supplier.created_at) {
                        const date = new Date(supplier.created_at);
                        document.getElementById('view-join-date').textContent = date.toLocaleDateString('id-ID', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric'
                        });
                    } else {
                        document.getElementById('view-join-date').textContent = '-';
                    }
                    
                    document.getElementById('view-status').textContent = supplier.is_active ? 'Aktif' : 'Tidak Aktif';
                    
                    switchTab('view');
                    openModal();
                } else {
                    alert(result.message || 'Gagal memuat data supplier');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Gagal memuat data supplier: ' + error.message);
            }
        });
    });

    // Edit Supplier
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const supplierId = btn.dataset.id;
            
            try {
                const response = await fetch(`/suppliers/${supplierId}/edit`, {
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
                    const supplier = result.data;
                    document.getElementById('supplier-name').value = supplier.name || '';
                    document.getElementById('supplier-code').value = supplier.code || '';
                    document.getElementById('supplier-company').value = supplier.company_name || '';
                    document.getElementById('supplier-email').value = supplier.email || '';
                    document.getElementById('supplier-phone').value = supplier.phone || '';
                    document.getElementById('supplier-address').value = supplier.address || '';
                    document.getElementById('supplier-active').checked = supplier.is_active;
                    document.getElementById('supplier-id-hidden').value = supplier.id;
                    document.getElementById('form-method').value = 'PUT';
                    
                    currentSupplierId = supplier.id;
                    isEditMode = true;
                    document.getElementById('save-btn').textContent = 'Perbarui';
                    
                    switchTab('create-edit');
                    openModal();
                } else {
                    alert(result.message || 'Gagal memuat data supplier');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Gagal memuat data supplier: ' + error.message);
            }
        });
    });

    // Delete Supplier
    let supplierToDelete = null;

    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const supplierId = btn.dataset.id;
            
            try {
                const response = await fetch(`/suppliers/${supplierId}`, {
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
                    const supplier = result.data;
                    supplierToDelete = supplier.id;
                    document.getElementById('delete-supplier-info').textContent = 
                        `${supplier.name} (${supplier.code})`;
                    
                    switchTab('delete');
                    openModal();
                } else {
                    alert(result.message || 'Gagal memuat data supplier');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Gagal memuat data supplier: ' + error.message);
            }
        });
    });

    // Confirm Delete
    confirmDeleteBtn.addEventListener('click', async () => {
        if (!supplierToDelete) return;

        try {
            const response = await fetch(`/suppliers/${supplierToDelete}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();

            if (result.success) {
                showToast(result.message);
                closeModal();
                setTimeout(() => location.reload(), 1000);
            } else {
                alert(result.message || 'Gagal menghapus supplier');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat menghapus supplier');
        }
    });
    
    // Export Dropdown
    if (exportBtn) {
        exportBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            exportDropdown.classList.toggle('open');
        });
    }
    document.addEventListener('click', (event) => {
        if (exportDropdown && !exportDropdown.contains(event.target)) {
            exportDropdown.classList.remove('open');
        }
    });

    // --- FUNGSI exportData YANG DIPINDAHKAN DAN DIPERBAIKI ---
    async function exportData(type) {
        try {
            // Get current filter parameters from the URL
            const params = new URLSearchParams(window.location.search);

            // Build the correct export URL based on your defined route
            let exportUrl = '/suppliers/export/data';

            // Append any existing filters as query parameters to the export URL
            if (params.toString()) {
                exportUrl += '?' + params.toString();
            }

            // Show loading state
            const exportBtn = document.getElementById('export-btn');
            const originalContent = exportBtn.innerHTML;
            exportBtn.disabled = true;
            exportBtn.innerHTML = '<span class="material-icons text-sm animate-spin">sync</span> Mengekspor...';

            // Fetch the export data from the server
            const response = await fetch(exportUrl, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();

            if (result.success && result.data && result.data.length > 0) {
                // Dynamically load SheetJS if not already loaded
                if (typeof XLSX === 'undefined') {
                    const script = document.createElement('script');
                    script.src = 'https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js';
                    document.head.appendChild(script);
                    await new Promise((resolve, reject) => {
                        script.onload = resolve;
                        script.onerror = reject;
                    });
                    // Wait a bit for XLSX to be available
                    await new Promise(resolve => setTimeout(resolve, 200));
                }

                // Check again if SheetJS is loaded
                if (typeof XLSX === 'undefined') {
                    alert('Library export tidak tersedia. Harap refresh halaman.');
                    return;
                }

                // Create worksheet from JSON data
                const worksheet = XLSX.utils.json_to_sheet(result.data);

                // Create new workbook
                const workbook = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(workbook, worksheet, 'Data Supplier');

                // Set column widths for better readability
                const wscols = [
                    {wch: 15},  // Kode Supplier
                    {wch: 30},  // Nama Supplier
                    {wch: 30},  // Perusahaan
                    {wch: 30},  // Email
                    {wch: 20},  // Telepon
                    {wch: 15},  // Status
                    {wch: 20},  // Tanggal Kerja Sama
                    {wch: 20},  // Terakhir Diperbarui
                    {wch: 18},  // Total Pembelian
                    {wch: 22},  // Nilai Total Pembelian
                    {wch: 20}   // Pembelian Terakhir
                ];
                worksheet['!cols'] = wscols;

                // Generate filename based on type
                const filename = type === 'csv' 
                    ? result.filename.replace('.xlsx', '.csv')
                    : result.filename;

                // Download file
                if (type === 'csv') {
                    XLSX.writeFile(workbook, filename, { bookType: 'csv' });
                } else {
                    XLSX.writeFile(workbook, filename);
                }

                // Show success message using showToast function
                showToast(`Data berhasil diekspor (${result.data.length} supplier)`);

            } else {
                throw new Error('Tidak ada data untuk diekspor');
            }

            // Restore button state
            exportBtn.disabled = false;
            exportBtn.innerHTML = originalContent;

            // Close dropdown
            document.getElementById('export-dropdown').classList.remove('open');

        } catch (error) {
            console.error('Export error:', error);
            alert('Terjadi kesalahan saat mengekspor data: ' + error.message);

            // Restore button state in case of error
            const exportBtn = document.getElementById('export-btn');
            if (exportBtn) {
                exportBtn.disabled = false;
                exportBtn.innerHTML = '<span class="material-icons text-sm">download</span> Ekspor <span class="material-icons text-sm">arrow_drop_down</span>';
            }
        }
    }

    // --- TAMBAHKAN EVENT LISTENER UNTUK TOMBOLEKSPOR ---
    // Ambil elemen tombol ekspor ke CSV dan Excel
    const exportToCsvBtn = document.querySelector('#export-dropdown .dropdown-menu a:nth-child(1)');
    const exportToExcelBtn = document.querySelector('#export-dropdown .dropdown-menu a:nth-child(2)');

    if (exportToCsvBtn) {
        exportToCsvBtn.addEventListener('click', (e) => {
            e.preventDefault(); // Cegah default action link
            exportData('csv');
        });
    }

    if (exportToExcelBtn) {
        exportToExcelBtn.addEventListener('click', (e) => {
            e.preventDefault(); // Cegah default action link
            exportData('excel');
        });
    }
});

</script>
@endpush