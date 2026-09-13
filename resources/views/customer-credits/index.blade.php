@extends('layouts.app')

@section('title', 'Kredit Customer')
@section('page-title', 'Kredit Customer')
@section('page-description', 'Kelola data kredit customer')

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
                <p class="text-text-muted-light dark:text-text-muted-dark text-sm">Total Kredit</p>
                <h3 class="text-2xl font-bold text-text-light dark:text-text-dark mt-1">{{ $stats['total_credits'] }}</h3>
            </div>
            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                <span class="material-icons text-primary">credit_card</span>
            </div>
        </div>
    </div>

    <div class="bg-card-light dark:bg-card-dark rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-text-muted-light dark:text-text-muted-dark text-sm">Kredit Aktif</p>
                <h3 class="text-2xl font-bold text-orange-600 mt-1">{{ $stats['active_credits'] }}</h3>
            </div>
            <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900 rounded-full flex items-center justify-center">
                <span class="material-icons text-orange-600">pending</span>
            </div>
        </div>
    </div>

    <div class="bg-card-light dark:bg-card-dark rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-text-muted-light dark:text-text-muted-dark text-sm">Lunas</p>
                <h3 class="text-2xl font-bold text-green-600 mt-1">{{ $stats['paid_credits'] }}</h3>
            </div>
            <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                <span class="material-icons text-green-600">check_circle</span>
            </div>
        </div>
    </div>

    <div class="bg-card-light dark:bg-card-dark rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-text-muted-light dark:text-text-muted-dark text-sm">Jatuh Tempo</p>
                <h3 class="text-2xl font-bold text-red-600 mt-1">{{ $stats['overdue_credits'] }}</h3>
            </div>
            <div class="w-12 h-12 bg-red-100 dark:bg-red-900 rounded-full flex items-center justify-center">
                <span class="material-icons text-red-600">warning</span>
            </div>
        </div>
    </div>
</div>

<!-- Filter and Table Card -->
<div class="bg-card-light dark:bg-card-dark rounded-lg shadow">
    <div class="p-6">
        <!-- Filter Section -->
        <form action="{{ route('customer-credits.index') }}" method="GET" id="filterForm">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                <div class="relative w-full md:w-auto">
                    <span class="material-icons absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">search</span>
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ request('search') }}"
                        class="pl-10 pr-4 py-2 w-full md:w-80 border border-gray-300 dark:border-gray-600 rounded-lg bg-background-light dark:bg-background-dark text-sm focus:ring-primary focus:border-primary" 
                        placeholder="Cari berdasarkan nama atau kode..."
                    />
                </div>
                
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 w-full md:w-auto">
                    <div class="flex items-center gap-2 flex-wrap">
                        <select 
                            name="status" 
                            class="w-full sm:w-auto px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-background-light dark:bg-background-dark text-sm focus:ring-primary focus:border-primary"
                        >
                            <option value="">Semua Status</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Belum Lunas</option>
                            <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Lunas</option>
                            <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Jatuh Tempo</option>
                        </select>

                        <button 
                            type="button"
                            class="flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                            id="filter-btn"
                        >
                            <span class="material-icons text-sm">filter_list</span> Filter
                        </button>

                       <div class="relative dropdown" id="export-dropdown">
                            <button 
                                type="button"
                                class="flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 w-full sm:w-auto justify-center" 
                                id="export-btn"
                            >
                                <span class="material-icons text-sm">download</span> Export
                                <span class="material-icons text-sm">arrow_drop_down</span>
                            </button>
                            <div class="dropdown-menu absolute right-0 mt-2 w-48 bg-card-light dark:bg-card-dark rounded-md shadow-lg py-1 z-10">
                                <a href="#" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    Export ke CSV
                                </a>
                                <a href="#" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    Export ke Excel
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <button 
                        type="button"
                        class="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg text-sm w-full sm:w-auto justify-center" 
                        id="add-credit-btn"
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
                        <th class="px-6 py-3">Kode</th>
                        <th class="px-6 py-3">Tanggal</th>
                        <th class="px-6 py-3">Nama Customer</th>
                        <th class="px-6 py-3">Total Kredit</th>
                        <th class="px-6 py-3">Dibayar</th>
                        <th class="px-6 py-3">Sisa</th>
                        <th class="px-6 py-3">Jatuh Tempo</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($credits as $credit)
                    <tr class="bg-card-light dark:bg-card-dark border-b dark:border-gray-700">
                        <td class="px-6 py-4">{{ $credit->credit_number }}</td>
                        <td class="px-6 py-4">{{ $credit->created_at->format('d/m/Y') }}</td>
                        <td class="px-6 py-4">{{ $credit->customer->name }}</td>
                        <td class="px-6 py-4">Rp {{ number_format($credit->total_credit, 0, ',', '.') }}</td>
                        <td class="px-6 py-4">Rp {{ number_format($credit->paid_amount, 0, ',', '.') }}</td>
                        <td class="px-6 py-4">Rp {{ number_format($credit->remaining_amount, 0, ',', '.') }}</td>
                        <td class="px-6 py-4">{{ $credit->due_date->format('d/m/Y') }}</td>
                        <td class="px-6 py-4">
                            @if($credit->status == 'active')
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">Belum Lunas</span>
                            @elseif($credit->status == 'paid')
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Lunas</span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Jatuh Tempo</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 flex justify-center gap-2">
                            <button class="px-3 py-1 bg-blue-500 text-white rounded text-xs view-btn" data-id="{{ $credit->id }}">Lihat</button>
                            <button class="px-3 py-1 bg-yellow-500 text-white rounded text-xs edit-btn" data-id="{{ $credit->id }}">Edit</button>
                            <button class="px-3 py-1 bg-red-600 text-white rounded text-xs delete-btn" data-id="{{ $credit->id }}">Hapus</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-6 py-8 text-center text-text-muted-light dark:text-text-muted-dark">
                            <span class="material-icons text-4xl mb-2">inbox</span>
                            <p>Tidak ada data kredit customer</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $credits->links() }}
        </div>
    </div>
</div>

<!-- Modal View Detail -->
<div class="modal fixed inset-0 bg-black bg-opacity-50 justify-center items-center z-50" id="view-modal">
    <div class="bg-card-light dark:bg-card-dark p-8 rounded-lg shadow-lg w-full max-w-5xl max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Detail Kredit Customer</h2>
            <button class="text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-white" id="close-view-modal">
                <span class="material-icons">close</span>
            </button>
        </div>

        <!-- Detail Content -->
        <div class="space-y-6">
            <!-- Informasi Dasar -->
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <h3 class="font-bold text-lg mb-4">1. Informasi Dasar</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <p class="font-medium text-gray-500 dark:text-gray-400">Kode Kredit</p>
                        <p class="text-gray-800 dark:text-white" id="view-credit-number">-</p>
                    </div>
                    <div>
                        <p class="font-medium text-gray-500 dark:text-gray-400">Tanggal Kredit</p>
                        <p class="text-gray-800 dark:text-white" id="view-credit-date">-</p>
                    </div>
                    <div>
                        <p class="font-medium text-gray-500 dark:text-gray-400">Jatuh Tempo</p>
                        <p class="text-gray-800 dark:text-white" id="view-due-date">-</p>
                    </div>
                    <div>
                        <p class="font-medium text-gray-500 dark:text-gray-400">Status</p>
                        <p id="view-status">-</p>
                    </div>
                    <div>
                        <p class="font-medium text-gray-500 dark:text-gray-400">Tenor</p>
                        <p class="text-gray-800 dark:text-white" id="view-tenor">-</p>
                    </div>
                </div>
            </div>

            <!-- Informasi Pelanggan -->
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <h3 class="font-bold text-lg mb-4">2. Informasi Pelanggan</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="font-medium text-gray-500 dark:text-gray-400">Nama</p>
                        <p class="text-gray-800 dark:text-white" id="view-customer-name">-</p>
                    </div>
                    <div>
                        <p class="font-medium text-gray-500 dark:text-gray-400">No. Telepon</p>
                        <p class="text-gray-800 dark:text-white" id="view-customer-phone">-</p>
                    </div>
                    <div>
                        <p class="font-medium text-gray-500 dark:text-gray-400">Alamat</p>
                        <p class="text-gray-800 dark:text-white" id="view-customer-address">-</p>
                    </div>
                    <div>
                        <p class="font-medium text-gray-500 dark:text-gray-400">Email</p>
                        <p class="text-gray-800 dark:text-white" id="view-customer-email">-</p>
                    </div>
                </div>
            </div>

            <!-- Ringkasan Keuangan -->
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <h3 class="font-bold text-lg mb-4">3. Ringkasan Keuangan</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
                    <div>
                        <p class="font-medium text-gray-500 dark:text-gray-400">Total Kredit</p>
                        <p class="text-gray-800 dark:text-white font-semibold" id="view-total-credit">-</p>
                    </div>
                    <div>
                        <p class="font-medium text-gray-500 dark:text-gray-400">Dibayar</p>
                        <p class="text-gray-800 dark:text-white font-semibold" id="view-paid-amount">-</p>
                    </div>
                    <div>
                        <p class="font-medium text-gray-500 dark:text-gray-400">Sisa Tagihan</p>
                        <p class="text-gray-800 dark:text-white font-semibold" id="view-remaining-amount">-</p>
                    </div>
                    <div>
                        <p class="font-medium text-gray-500 dark:text-gray-400">Progres Pembayaran</p>
                        <div class="w-full bg-gray-200 rounded-full h-5 dark:bg-gray-700 mt-1">
                            <div class="bg-green-600 h-5 rounded-full text-xs text-white flex items-center justify-center" id="view-progress" style="width: 0%">0%</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Actions -->
        <div class="mt-6 flex justify-end gap-4">
            <button class="px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700" onclick="document.getElementById('view-modal').classList.remove('show')">
                Tutup
            </button>
            <button class="px-6 py-2 bg-blue-600 text-white rounded-lg text-sm flex items-center gap-2" id="send-reminder-btn">
                <span class="material-icons text-sm">sms</span> Kirim Pengingat
            </button>
            <button class="px-6 py-2 bg-primary text-white rounded-lg text-sm flex items-center gap-2" id="accept-payment-btn">
                <span class="material-icons text-sm">payment</span> Terima Pembayaran
            </button>
        </div>
    </div>
</div>

<!-- Modal Accept Payment -->
<div class="modal fixed inset-0 bg-black bg-opacity-50 justify-center items-center z-50" id="payment-modal">
    <div class="bg-card-light dark:bg-card-dark p-8 rounded-lg shadow-lg w-full max-w-2xl">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Terima Pembayaran Kredit</h2>
            <button class="text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-white" id="close-payment-modal">
                <span class="material-icons">close</span>
            </button>
        </div>

        <div class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-4 mb-6">
            <div class="flex items-start gap-3">
                <span class="material-icons text-blue-600 dark:text-blue-400">info</span>
                <div class="text-sm">
                    <p class="text-blue-800 dark:text-blue-200">
                        Sisa tagihan untuk customer <strong id="payment-customer-name"></strong> (<strong id="payment-credit-number"></strong>) adalah <strong id="payment-remaining"></strong>
                    </p>
                </div>
            </div>
        </div>

        <form id="payment-form">
            @csrf
            <input type="hidden" id="payment-credit-id">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Jumlah Pembayaran</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">Rp</span>
                        <input 
                            type="number" 
                            id="payment-amount"
                            name="payment_amount"
                            class="w-full pl-12 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-background-light dark:bg-background-dark text-sm focus:ring-primary focus:border-primary"
                            placeholder="Masukkan jumlah pembayaran"
                            required
                        >
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Metode Pembayaran</label>
                    <select 
                        id="payment-method"
                        name="payment_method"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-background-light dark:bg-background-dark text-sm focus:ring-primary focus:border-primary"
                        required
                    >
                        <option value="">Pilih metode pembayaran...</option>
                        <option value="cash">Cash</option>
                        <option value="transfer">Transfer Bank</option>
                        <option value="debit">Kartu Debit</option>
                        <option value="credit">Kartu Kredit</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal Pembayaran</label>
                    <input 
                        type="date" 
                        id="payment-date"
                        name="payment_date"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-background-light dark:bg-background-dark text-sm focus:ring-primary focus:border-primary"
                        required
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Keterangan (Opsional)</label>
                    <textarea 
                        id="payment-notes"
                        name="notes"
                        rows="3"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-background-light dark:bg-background-dark text-sm focus:ring-primary focus:border-primary"
                        placeholder="Contoh: Pembayaran cicilan kedua"
                    ></textarea>
                </div>

                <hr class="my-4 border-gray-200 dark:border-gray-700">

                <div class="flex justify-between items-center text-sm">
                    <span class="font-medium text-gray-700 dark:text-gray-300">Sisa Tagihan Setelah Pembayaran:</span>
                    <span class="text-lg font-bold text-green-600" id="new-remaining">Rp 0</span>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-4">
                <button 
                    type="button"
                    class="px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                    onclick="document.getElementById('payment-modal').classList.remove('show')"
                >
                    Batal
                </button>
                <button 
                    type="submit"
                    class="px-6 py-2 bg-primary text-white rounded-lg text-sm flex items-center gap-2"
                >
                    <span class="material-icons text-sm">send</span> Submit Pembayaran
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Toast Notifications -->
<div class="fixed top-5 right-5 z-50 space-y-3" id="toast-container">
    <div class="toast items-center p-4 w-full max-w-xs text-gray-500 bg-white rounded-lg shadow dark:text-gray-400 dark:bg-gray-800" id="success-toast">
        <div class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 text-green-500 bg-green-100 rounded-lg dark:bg-green-800 dark:text-green-200">
            <span class="material-icons">check_circle</span>
        </div>
        <div class="ml-3 text-sm font-normal" id="success-toast-message">Operasi berhasil</div>
        <button 
            class="ml-auto -mx-1.5 -my-1.5 bg-white text-gray-400 hover:text-gray-900 rounded-lg p-1.5 hover:bg-gray-100 inline-flex h-8 w-8 dark:text-gray-500 dark:hover:text-white dark:bg-gray-800 dark:hover:bg-gray-700" 
            onclick="this.parentElement.classList.remove('show')"
        >
            <span class="material-icons">close</span>
        </button>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const viewModal = document.getElementById('view-modal');
    const paymentModal = document.getElementById('payment-modal');
    const successToast = document.getElementById('success-toast');
    const successToastMessage = document.getElementById('success-toast-message');
    const exportDropdown = document.getElementById('export-dropdown');
    const exportBtn = document.getElementById('export-btn');

    let currentCreditId = null;
    let currentRemainingAmount = 0;

    // Toast Functions
    const showToast = (message) => {
        successToastMessage.textContent = message;
        successToast.classList.add('show');
        setTimeout(() => successToast.classList.remove('show'), 3000);
    };

    // View Credit Detail
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const creditId = btn.dataset.id;
            currentCreditId = creditId;

            try {
                const response = await fetch(`/customer-credits/${creditId}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const result = await response.json();

                if (result.success) {
                    const credit = result.data.credit;
                    const customer = credit.customer;

                    document.getElementById('view-credit-number').textContent = credit.credit_number;
                    document.getElementById('view-credit-date').textContent = new Date(credit.created_at).toLocaleDateString('id-ID');
                    document.getElementById('view-due-date').textContent = new Date(credit.due_date).toLocaleDateString('id-ID');
                    
                    // Status badge
                    let statusHtml = '';
                    if (credit.status === 'active') {
                        statusHtml = '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">Belum Lunas</span>';
                    } else if (credit.status === 'paid') {
                        statusHtml = '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Lunas</span>';
                    } else {
                        statusHtml = '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Jatuh Tempo</span>';
                    }
                    document.getElementById('view-status').innerHTML = statusHtml;

                    // Calculate tenor
                    const createdDate = new Date(credit.created_at);
                    const dueDate = new Date(credit.due_date);
                    const diffTime = Math.abs(dueDate - createdDate);
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                    document.getElementById('view-tenor').textContent = diffDays + ' Hari';

                    document.getElementById('view-customer-name').textContent = customer.name;
                    document.getElementById('view-customer-phone').textContent = customer.phone || '-';
                    document.getElementById('view-customer-address').textContent = customer.address || '-';
                    document.getElementById('view-customer-email').textContent = customer.email || '-';

                    document.getElementById('view-total-credit').textContent = 'Rp ' + Number(credit.total_credit).toLocaleString('id-ID');
                    document.getElementById('view-paid-amount').textContent = 'Rp ' + Number(credit.paid_amount).toLocaleString('id-ID');
                    document.getElementById('view-remaining-amount').textContent = 'Rp ' + Number(credit.remaining_amount).toLocaleString('id-ID');

                    // Progress bar
                    const progress = (credit.paid_amount / credit.total_credit * 100).toFixed(0);
                    const progressBar = document.getElementById('view-progress');
                    progressBar.style.width = progress + '%';
                    progressBar.textContent = progress + '%';

                    currentRemainingAmount = credit.remaining_amount;

                    viewModal.classList.add('show');
                } else {
                    alert(result.message || 'Gagal memuat data');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat memuat data');
            }
        });
    });

    // Close view modal
    document.getElementById('close-view-modal').addEventListener('click', () => {
        viewModal.classList.remove('show');
    });

    // Send Reminder
    document.getElementById('send-reminder-btn').addEventListener('click', async () => {
        if (!currentCreditId) return;

        if (!confirm('Apakah Anda yakin ingin mengirim pengingat pembayaran?')) return;

        try {
            const response = await fetch(`/customer-credits/${currentCreditId}/send-reminder`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();

            if (result.success) {
                showToast(result.message);
            } else {
                alert(result.message || 'Gagal mengirim pengingat');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat mengirim pengingat');
        }
    });

    // Open Payment Modal
    document.getElementById('accept-payment-btn').addEventListener('click', () => {
        document.getElementById('payment-credit-id').value = currentCreditId;
        document.getElementById('payment-customer-name').textContent = document.getElementById('view-customer-name').textContent;
        document.getElementById('payment-credit-number').textContent = document.getElementById('view-credit-number').textContent;
        document.getElementById('payment-remaining').textContent = document.getElementById('view-remaining-amount').textContent;
        
        // Set default payment date to today
        document.getElementById('payment-date').valueAsDate = new Date();
        
        viewModal.classList.remove('show');
        paymentModal.classList.add('show');
    });

    // Close payment modal
    document.getElementById('close-payment-modal').addEventListener('click', () => {
        paymentModal.classList.remove('show');
        document.getElementById('payment-form').reset();
    });

    // Calculate remaining after payment
    document.getElementById('payment-amount').addEventListener('input', (e) => {
        const paymentAmount = parseFloat(e.target.value) || 0;
        const newRemaining = currentRemainingAmount - paymentAmount;
        document.getElementById('new-remaining').textContent = 'Rp ' + Math.max(0, newRemaining).toLocaleString('id-ID');
    });

    // Submit Payment
    document.getElementById('payment-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        const creditId = document.getElementById('payment-credit-id').value;
        const formData = new FormData(e.target);
        const data = {
            payment_amount: formData.get('payment_amount'),
            payment_method: formData.get('payment_method'),
            payment_date: formData.get('payment_date'),
            notes: formData.get('notes'),
            _token: '{{ csrf_token() }}'
        };

        try {
            const response = await fetch(`/customer-credits/${creditId}/accept-payment`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                showToast(result.message);
                paymentModal.classList.remove('show');
                setTimeout(() => location.reload(), 1000);
            } else {
                alert(result.message || 'Gagal menerima pembayaran');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat menerima pembayaran');
        }
    });

    // Add Credit Button - redirect to create page
    document.getElementById('add-credit-btn').addEventListener('click', () => {
        window.location.href = '{{ route("customer-credits.create") }}';
    });

    // Edit Credit
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const creditId = btn.dataset.id;
            window.location.href = `/customer-credits/${creditId}/edit`;
        });
    });

    // Delete Credit
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const creditId = btn.dataset.id;

            if (!confirm('Apakah Anda yakin ingin menghapus data kredit ini?')) return;

            try {
                const response = await fetch(`/customer-credits/${creditId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                const result = await response.json();

                if (result.success) {
                    showToast(result.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert(result.message || 'Gagal menghapus kredit');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menghapus kredit');
            }
        });
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

    // --- FUNGSI exportData ---
    async function exportData(type) {
        try {
            // Get current filter parameters from the URL
            const params = new URLSearchParams(window.location.search);

            // Build the correct export URL based on your defined route
            let exportUrl = '/customer-credits/export/data';

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
                XLSX.utils.book_append_sheet(workbook, worksheet, 'Data Kredit Customer');

                // Set column widths for better readability
                const wscols = [
                    {wch: 15},  // Kode Kredit
                    {wch: 20},  // Tanggal
                    {wch: 30},  // Nama Customer
                    {wch: 20},  // Total Kredit
                    {wch: 20},  // Dibayar
                    {wch: 20},  // Sisa
                    {wch: 20},  // Jatuh Tempo
                    {wch: 20},  // Status
                    {wch: 20}   // Terakhir Diperbarui
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
                showToast(`Data berhasil diekspor (${result.data.length} kredit customer)`);

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
                exportBtn.innerHTML = '<span class="material-icons text-sm">download</span> Export <span class="material-icons text-sm">arrow_drop_down</span>';
            }
        }
    }

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