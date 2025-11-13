@extends('layouts.app')

@section('title', 'Data Customer')
@section('page-title', 'Data Customer')
@section('page-description', 'Kelola data pelanggan toko Anda')

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
                <p class="text-text-muted-light dark:text-text-muted-dark text-sm">Total Customer</p>
                <h3 class="text-2xl font-bold text-text-light dark:text-text-dark mt-1">{{ $stats['total_customers'] }}</h3>
            </div>
            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                <span class="material-icons text-primary">people</span>
            </div>
        </div>
    </div>

    <div class="bg-card-light dark:bg-card-dark rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-text-muted-light dark:text-text-muted-dark text-sm">Customer Aktif</p>
                <h3 class="text-2xl font-bold text-green-600 mt-1">{{ $stats['active_customers'] }}</h3>
            </div>
            <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                <span class="material-icons text-green-600">check_circle</span>
            </div>
        </div>
    </div>

    <div class="bg-card-light dark:bg-card-dark rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-text-muted-light dark:text-text-muted-dark text-sm">Customer Tidak Aktif</p>
                <h3 class="text-2xl font-bold text-red-600 mt-1">{{ $stats['inactive_customers'] }}</h3>
            </div>
            <div class="w-12 h-12 bg-red-100 dark:bg-red-900 rounded-full flex items-center justify-center">
                <span class="material-icons text-red-600">cancel</span>
            </div>
        </div>
    </div>

    <div class="bg-card-light dark:bg-card-dark rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-text-muted-light dark:text-text-muted-dark text-sm">Customer Kredit</p>
                <h3 class="text-2xl font-bold text-orange-600 mt-1">{{ $stats['customers_with_credit'] }}</h3>
            </div>
            <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900 rounded-full flex items-center justify-center">
                <span class="material-icons text-orange-600">credit_card</span>
            </div>
        </div>
    </div>
</div>

<!-- Filter and Table Card -->
<div class="bg-card-light dark:bg-card-dark rounded-lg shadow">
    <div class="p-6">
        <!-- Filter Section -->
        <form action="{{ route('customers.index') }}" method="GET" id="filterForm">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                <div class="relative w-full md:w-auto">
                    <span class="material-icons absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">search</span>
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ request('search') }}"
                        class="pl-10 pr-4 py-2 w-full md:w-80 border border-gray-300 dark:border-gray-600 rounded-lg bg-background-light dark:bg-background-dark text-sm focus:ring-primary focus:border-primary" 
                        placeholder="Cari berdasarkan nama atau ID..."
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
                            <option value="created_at" {{ request('sort_field') == 'created_at' ? 'selected' : '' }}>Tanggal Daftar</option>
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
                                <a href="#" onclick="exportData('csv')" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    Ekspor ke CSV
                                </a>
                                <a href="#" onclick="exportData('excel')" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    Ekspor ke Excel
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <button 
                        type="button"
                        class="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg text-sm w-full sm:w-auto justify-center" 
                        id="add-customer-btn"
                    >
                        <span class="material-icons text-sm">add_circle</span> Tambah Customer
                    </button>
                </div>
            </div>
        </form>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 dark:text-gray-300 uppercase bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3">ID</th>
                        <th class="px-6 py-3">Nama Customer</th>
                        <th class="px-6 py-3">Email</th>
                        <th class="px-6 py-3">Telepon</th>
                        <th class="px-6 py-3">Alamat</th>
                        <th class="px-6 py-3">Tanggal Daftar</th>
                        <th class="px-6 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="customer-table-body">
                    @forelse($customers as $customer)
                    <tr class="bg-card-light dark:bg-card-dark border-b dark:border-gray-700" data-customer-id="{{ $customer->id }}">
                        <td class="px-6 py-4">{{ $customer->code }}</td>
                        <td class="px-6 py-4">{{ $customer->name }}</td>
                        <td class="px-6 py-4">{{ $customer->email ?? '-' }}</td>
                        <td class="px-6 py-4">{{ $customer->phone ?? '-' }}</td>
                        <td class="px-6 py-4">{{ Str::limit($customer->address ?? '-', 30) }}</td>
                        <td class="px-6 py-4">{{ $customer->created_at->format('d/m/Y') }}</td>
                        <td class="px-6 py-4 flex justify-center gap-2">
                            <button class="px-3 py-1 bg-blue-500 text-white rounded text-xs view-btn" data-id="{{ $customer->id }}">Lihat</button>
                            <button class="px-3 py-1 bg-yellow-500 text-white rounded text-xs edit-btn" data-id="{{ $customer->id }}">Edit</button>
                            <button class="px-3 py-1 bg-red-600 text-white rounded text-xs delete-btn" data-id="{{ $customer->id }}">Hapus</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-text-muted-light dark:text-text-muted-dark">
                            <span class="material-icons text-4xl mb-2">inbox</span>
                            <p>Tidak ada data customer</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $customers->links() }}
        </div>
    </div>
</div>

<!-- Modal Customer -->
<div class="modal fixed inset-0 bg-black bg-opacity-50 justify-center items-center z-50" id="customer-modal">
    <div class="bg-card-light dark:bg-card-dark p-8 rounded-lg shadow-lg w-full max-w-3xl max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white" id="modal-title">Manajemen Customer</h2>
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
        <!-- Create/Edit Tab -->
        <div>
            <div class="tab-content active" id="create-edit-tab">
                <form id="customer-form">
                    @csrf
                    <input type="hidden" id="customer-id-hidden" name="id">
                    <input type="hidden" id="form-method" name="_method" value="POST">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="customer-name">Nama Customer</label>
                            <input 
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-background-light dark:bg-background-dark text-sm focus:ring-primary focus:border-primary" 
                                id="customer-name" 
                                name="name" 
                                required 
                                type="text"
                            />
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="customer-code">Kode Customer</label>
                            <input 
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-background-light dark:bg-background-dark text-sm focus:ring-primary focus:border-primary" 
                                id="customer-code" 
                                name="code" 
                                type="text"
                                placeholder="Otomatis jika dikosongkan"
                            />
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="customer-email">Email</label>
                            <input 
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-background-light dark:bg-background-dark text-sm focus:ring-primary focus:border-primary" 
                                id="customer-email" 
                                name="email" 
                                type="email"
                            />
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="customer-phone">Telepon</label>
                            <input 
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-background-light dark:bg-background-dark text-sm focus:ring-primary focus:border-primary" 
                                id="customer-phone" 
                                name="phone" 
                                type="tel"
                            />
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="customer-address">Alamat</label>
                            <textarea 
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-background-light dark:bg-background-dark text-sm focus:ring-primary focus:border-primary" 
                                id="customer-address" 
                                name="address" 
                                rows="3"
                            ></textarea>
                        </div>

                        <div class="md:col-span-2">
                            <label class="flex items-center">
                                <input 
                                    type="checkbox" 
                                    name="is_active" 
                                    id="customer-active"
                                    value="1"
                                    checked
                                    class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary dark:focus:ring-primary dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                                >
                                <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">Customer Aktif</span>
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
                        <p class="font-medium text-gray-500 dark:text-gray-400">Nama Customer</p>
                        <p class="text-gray-800 dark:text-white" id="view-name">-</p>
                    </div>
                    <div>
                        <p class="font-medium text-gray-500 dark:text-gray-400">Kode Customer</p>
                        <p class="text-gray-800 dark:text-white" id="view-code">-</p>
                    </div>
                    <div>
                        <p class="font-medium text-gray-500 dark:text-gray-400">Email</p>
                        <p class="text-gray-800 dark:text-white" id="view-email">-</p>
                    </div>
                    <div>
                        <p class="font-medium text-gray-500 dark:text-gray-400">Telepon</p>
                        <p class="text-gray-800 dark:text-white" id="view-phone">-</p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="font-medium text-gray-500 dark:text-gray-400">Alamat</p>
                        <p class="text-gray-800 dark:text-white" id="view-address">-</p>
                    </div>
                    <div>
                        <p class="font-medium text-gray-500 dark:text-gray-400">Tanggal Daftar</p>
                        <p class="text-gray-800 dark:text-white" id="view-join-date">-</p>
                    </div>
                    <div>
                        <p class="font-medium text-gray-500 dark:text-gray-400">Status</p>
                        <p class="text-gray-800 dark:text-white" id="view-status">-</p>
                    </div>
                </div>
            </div>

            <!-- Delete Tab -->
            <div class="tab-content" id="delete-tab">
                <div class="text-center">
                    <span class="material-icons text-6xl text-red-500 mb-4">warning_amber</span>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Hapus Customer</h3>
                    <div class="mt-2">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Apakah Anda yakin ingin menghapus data customer ini secara permanen? Tindakan ini tidak dapat diurungkan.
                        </p>
                        <p class="text-sm font-semibold text-gray-800 dark:text-white mt-2" id="delete-customer-info"></p>
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
    const addCustomerBtn = document.getElementById("add-customer-btn");
    const customerModal = document.getElementById("customer-modal");
    const closeModalBtn = document.getElementById("close-modal-btn");
    const customerForm = document.getElementById("customer-form");
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
    
    let currentCustomerId = null;
    let isEditMode = false;

    // Modal Functions
    const openModal = () => customerModal.classList.add("show");
    const closeModal = () => {
        customerModal.classList.remove("show");
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
        customerForm.reset();
        document.getElementById('customer-id-hidden').value = '';
        document.getElementById('form-method').value = 'POST';
        document.getElementById('customer-active').checked = true;
        currentCustomerId = null;
        isEditMode = false;
        document.getElementById('save-btn').textContent = 'Simpan';
    };

    // Add Customer
    addCustomerBtn.addEventListener("click", () => {
        resetForm();
        switchTab('create-edit');
        openModal();
    });

    // Close Modal
    closeModalBtn.addEventListener("click", closeModal);
    cancelBtn.addEventListener("click", closeModal);
    cancelDeleteBtn.addEventListener("click", () => switchTab('create-edit'));

    // Submit Form
    customerForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        
        const formData = new FormData(customerForm);
        const data = {
            name: formData.get('name'),
            code: formData.get('code'),
            email: formData.get('email'),
            phone: formData.get('phone'),
            address: formData.get('address'),
            is_active: formData.get('is_active') ? 1 : 0,
            _token: '{{ csrf_token() }}'
        };

        const url = isEditMode 
            ? `/customers/${currentCustomerId}` 
            : '{{ route("customers.store") }}';
        
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
                // Handle validation errors
                if (result.errors) {
                    const errorMessages = Object.values(result.errors).flat().join('\n');
                    alert('Validasi gagal:\n' + errorMessages);
                } else {
                    alert(result.message || 'Terjadi kesalahan');
                }
                
                // Re-enable button
                saveBtn.disabled = false;
                saveBtn.textContent = originalText;
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat menyimpan data: ' + error.message);
            
            // Re-enable button
            saveBtn.disabled = false;
            saveBtn.textContent = originalText;
        }
    });

    // View Customer
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const customerId = btn.dataset.id;
            
            try {
                const response = await fetch(`/customers/${customerId}`, {
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
                    const customer = result.data.customer;
                    document.getElementById('view-name').textContent = customer.name || '-';
                    document.getElementById('view-code').textContent = customer.code || '-';
                    document.getElementById('view-email').textContent = customer.email || '-';
                    document.getElementById('view-phone').textContent = customer.phone || '-';
                    document.getElementById('view-address').textContent = customer.address || '-';
                    
                    // Format tanggal dengan lebih aman
                    if (customer.created_at) {
                        const date = new Date(customer.created_at);
                        document.getElementById('view-join-date').textContent = date.toLocaleDateString('id-ID', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric'
                        });
                    } else {
                        document.getElementById('view-join-date').textContent = '-';
                    }
                    
                    document.getElementById('view-status').textContent = customer.is_active ? 'Aktif' : 'Tidak Aktif';
                    
                    switchTab('view');
                    openModal();
                } else {
                    alert(result.message || 'Gagal memuat data customer');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Gagal memuat data customer: ' + error.message);
            }
        });
    });

    // Edit Customer
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const customerId = btn.dataset.id;
            
            try {
                const response = await fetch(`/customers/${customerId}/edit`, {
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
                    const customer = result.data;
                    document.getElementById('customer-name').value = customer.name || '';
                    document.getElementById('customer-code').value = customer.code || '';
                    document.getElementById('customer-email').value = customer.email || '';
                    document.getElementById('customer-phone').value = customer.phone || '';
                    document.getElementById('customer-address').value = customer.address || '';
                    document.getElementById('customer-active').checked = customer.is_active;
                    document.getElementById('customer-id-hidden').value = customer.id;
                    document.getElementById('form-method').value = 'PUT';
                    
                    currentCustomerId = customer.id;
                    isEditMode = true;
                    document.getElementById('save-btn').textContent = 'Perbarui';
                    
                    switchTab('create-edit');
                    openModal();
                } else {
                    alert(result.message || 'Gagal memuat data customer');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Gagal memuat data customer: ' + error.message);
            }
        });
    });

    // Delete Customer
    let customerToDelete = null;

    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const customerId = btn.dataset.id;
            
            try {
                const response = await fetch(`/customers/${customerId}`, {
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
                    const customer = result.data.customer;
                    customerToDelete = customer.id;
                    document.getElementById('delete-customer-info').textContent = 
                        `${customer.name} (${customer.code})`;
                    
                    switchTab('delete');
                    openModal();
                } else {
                    alert(result.message || 'Gagal memuat data customer');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Gagal memuat data customer: ' + error.message);
            }
        });
    });

    // Confirm Delete
    confirmDeleteBtn.addEventListener('click', async () => {
        if (!customerToDelete) return;

        try {
            const response = await fetch(`/customers/${customerToDelete}`, {
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
                alert(result.message || 'Gagal menghapus customer');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat menghapus customer');
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

    // Auto submit filter on change
    document.querySelector('select[name="status"]').addEventListener('change', function() {
        document.getElementById('filterForm').submit();
    });

    document.querySelector('select[name="sort_field"]').addEventListener('change', function() {
        document.getElementById('filterForm').submit();
    });

    document.querySelector('input[name="search"]').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('filterForm').submit();
        }
    });
});

// Export Data Function
function exportData(type) {
    const params = new URLSearchParams(window.location.search);
    params.set('export', type);
    window.location.href = `/customers/export/data?${params.toString()}`;
}
</script>
@endpush