@extends('layouts.app')

@section('title', 'Edit Kredit Customer')
@section('page-title', 'Edit Kredit Customer')
@section('page-description', 'Perbarui data kredit customer')

@section('content')
<div class="bg-card-light dark:bg-card-dark rounded-lg shadow p-6">
    <form id="credit-form" action="{{ route('customer-credits.update', $customerCredit->id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Credit Number (Read Only) -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Nomor Kredit
                </label>
                <input 
                    type="text" 
                    value="{{ $customerCredit->credit_number }}"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-100 dark:bg-gray-700 text-sm"
                    readonly
                >
            </div>

            <!-- Status -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Status <span class="text-red-500">*</span>
                </label>
                <select 
                    name="status" 
                    id="status"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-background-light dark:bg-background-dark text-sm focus:ring-primary focus:border-primary"
                    required
                >
                    <option value="active" {{ $customerCredit->status == 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="paid" {{ $customerCredit->status == 'paid' ? 'selected' : '' }}>Lunas</option>
                    <option value="overdue" {{ $customerCredit->status == 'overdue' ? 'selected' : '' }}>Jatuh Tempo</option>
                </select>
            </div>

            <!-- Customer Selection -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Customer <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input 
                        type="text" 
                        id="customer-search"
                        value="{{ $customerCredit->customer->name }}"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-background-light dark:bg-background-dark text-sm focus:ring-primary focus:border-primary"
                        placeholder="Cari customer..."
                        autocomplete="off"
                    >
                    <input type="hidden" name="customer_id" id="customer-id" value="{{ $customerCredit->customer_id }}" required>
                    
                    <!-- Search Results Dropdown -->
                    <div id="customer-results" class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-60 overflow-y-auto hidden"></div>
                </div>
                
                <!-- Selected Customer Info -->
                <div id="selected-customer-info" class="mt-3 p-4 bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg">
                    <div class="flex items-start gap-3">
                        <span class="material-icons text-blue-600 dark:text-blue-400">person</span>
                        <div class="flex-1 text-sm">
                            <p class="font-semibold text-blue-900 dark:text-blue-100">{{ $customerCredit->customer->name }}</p>
                            <p class="text-blue-700 dark:text-blue-300 mt-1">
                                <strong>Kode:</strong> {{ $customerCredit->customer->code }} | 
                                <strong>Telepon:</strong> {{ $customerCredit->customer->phone ?? '-' }}
                            </p>
                            <p class="text-blue-700 dark:text-blue-300">{{ $customerCredit->customer->address ?? '-' }}</p>
                        </div>
                        <button type="button" id="clear-customer" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200">
                            <span class="material-icons">close</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Sale Selection (Optional) -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Transaksi Penjualan Terkait
                </label>
                <select 
                    name="sale_id" 
                    id="sale-id"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-background-light dark:bg-background-dark text-sm focus:ring-primary focus:border-primary"
                >
                    <option value="">-- Tidak ada transaksi terkait --</option>
                    @if($customerCredit->sale)
                        <option value="{{ $customerCredit->sale_id }}" selected>
                            {{ $customerCredit->sale->invoice_number }} - Rp {{ number_format($customerCredit->sale->total, 0, ',', '.') }}
                        </option>
                    @endif
                </select>
            </div>

            <!-- Total Credit -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Total Kredit <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">Rp</span>
                    <input 
                        type="number" 
                        name="total_credit" 
                        id="total-credit"
                        value="{{ $customerCredit->total_credit }}"
                        class="w-full pl-12 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-background-light dark:bg-background-dark text-sm focus:ring-primary focus:border-primary"
                        min="0"
                        step="0.01"
                        required
                    >
                </div>
            </div>

            <!-- Paid Amount -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Jumlah Dibayar
                </label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">Rp</span>
                    <input 
                        type="number" 
                        name="paid_amount" 
                        id="paid-amount"
                        value="{{ $customerCredit->paid_amount }}"
                        class="w-full pl-12 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-background-light dark:bg-background-dark text-sm focus:ring-primary focus:border-primary"
                        min="0"
                        step="0.01"
                    >
                </div>
            </div>

            <!-- Due Date -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Tanggal Jatuh Tempo <span class="text-red-500">*</span>
                </label>
                <input 
                    type="date" 
                    name="due_date" 
                    id="due-date"
                    value="{{ $customerCredit->due_date->format('Y-m-d') }}"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-background-light dark:bg-background-dark text-sm focus:ring-primary focus:border-primary"
                    required
                >
            </div>

            <!-- Remaining Amount (Read Only) -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Sisa Tagihan
                </label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">Rp</span>
                    <input 
                        type="text" 
                        id="remaining-amount"
                        value="{{ number_format($customerCredit->remaining_amount, 0, ',', '.') }}"
                        class="w-full pl-12 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-100 dark:bg-gray-700 text-sm"
                        readonly
                    >
                </div>
            </div>

            <!-- Notes -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Catatan
                </label>
                <textarea 
                    name="notes" 
                    id="notes"
                    rows="4"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-background-light dark:bg-background-dark text-sm focus:ring-primary focus:border-primary"
                    placeholder="Masukkan catatan tambahan (opsional)"
                >{{ $customerCredit->notes }}</textarea>
            </div>
        </div>

        <!-- Summary Card -->
        <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white mb-3">Ringkasan Kredit</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <p class="text-gray-500 dark:text-gray-400">Total Kredit</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white" id="summary-total">Rp {{ number_format($customerCredit->total_credit, 0, ',', '.') }}</p>
                </div>
                <div>
                    <p class="text-gray-500 dark:text-gray-400">Dibayar</p>
                    <p class="text-lg font-bold text-green-600" id="summary-paid">Rp {{ number_format($customerCredit->paid_amount, 0, ',', '.') }}</p>
                </div>
                <div>
                    <p class="text-gray-500 dark:text-gray-400">Sisa Tagihan</p>
                    <p class="text-lg font-bold text-orange-600" id="summary-remaining">Rp {{ number_format($customerCredit->remaining_amount, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="mt-8 flex justify-end gap-4">
            <a 
                href="{{ route('customer-credits.index') }}"
                class="px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
            >
                Batal
            </a>
            <button 
                type="submit"
                class="px-6 py-2 bg-primary text-white rounded-lg text-sm hover:bg-blue-700 flex items-center gap-2"
                id="submit-btn"
            >
                <span class="material-icons text-sm">save</span>
                Perbarui Kredit
            </button>
        </div>
    </form>
</div>

<!-- Toast Notifications -->
<div class="fixed top-5 right-5 z-50 space-y-3" id="toast-container">
    <div class="toast items-center p-4 w-full max-w-xs text-gray-500 bg-white rounded-lg shadow dark:text-gray-400 dark:bg-gray-800 hidden" id="success-toast">
        <div class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 text-green-500 bg-green-100 rounded-lg dark:bg-green-800 dark:text-green-200">
            <span class="material-icons">check_circle</span>
        </div>
        <div class="ml-3 text-sm font-normal" id="success-toast-message">Operasi berhasil</div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const customerSearch = document.getElementById('customer-search');
    const customerResults = document.getElementById('customer-results');
    const customerIdInput = document.getElementById('customer-id');
    const selectedCustomerInfo = document.getElementById('selected-customer-info');
    const totalCreditInput = document.getElementById('total-credit');
    const paidAmountInput = document.getElementById('paid-amount');
    const remainingAmountInput = document.getElementById('remaining-amount');
    const creditForm = document.getElementById('credit-form');

    let searchTimeout;

    // Customer Search
    customerSearch.addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        const query = e.target.value.trim();

        if (query.length < 2) {
            customerResults.classList.add('hidden');
            return;
        }

        searchTimeout = setTimeout(async () => {
            try {
                const response = await fetch(`/customers/search?search=${encodeURIComponent(query)}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const result = await response.json();

                if (result.success && result.data.length > 0) {
                    displayCustomerResults(result.data);
                } else {
                    customerResults.innerHTML = '<div class="p-4 text-sm text-gray-500 dark:text-gray-400">Tidak ada customer ditemukan</div>';
                    customerResults.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }, 300);
    });

    // Display Customer Results
    function displayCustomerResults(customers) {
        customerResults.innerHTML = customers.map(customer => `
            <div class="customer-result p-3 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer border-b dark:border-gray-600 last:border-b-0" 
                 data-customer='${JSON.stringify(customer)}'>
                <p class="font-semibold text-gray-900 dark:text-white">${customer.name}</p>
                <p class="text-xs text-gray-600 dark:text-gray-400">
                    ${customer.code} | ${customer.phone || '-'}
                </p>
            </div>
        `).join('');

        customerResults.classList.remove('hidden');

        // Add click handlers
        document.querySelectorAll('.customer-result').forEach(result => {
            result.addEventListener('click', () => {
                const customer = JSON.parse(result.dataset.customer);
                selectCustomer(customer);
            });
        });
    }

    // Select Customer
    function selectCustomer(customer) {
        customerIdInput.value = customer.id;
        customerSearch.value = customer.name;
        customerResults.classList.add('hidden');

        // Update info display
        selectedCustomerInfo.querySelector('.font-semibold').textContent = customer.name;
        selectedCustomerInfo.querySelector('.text-blue-700').innerHTML = `
            <strong>Kode:</strong> ${customer.code} | 
            <strong>Telepon:</strong> ${customer.phone || '-'}
        `;
        selectedCustomerInfo.querySelectorAll('.text-blue-700')[1].textContent = customer.address || '-';
    }

    // Clear Customer Selection
    document.getElementById('clear-customer').addEventListener('click', () => {
        customerIdInput.value = '';
        customerSearch.value = '';
        selectedCustomerInfo.classList.add('hidden');
    });

    // Calculate Remaining Amount
    function calculateRemaining() {
        const total = parseFloat(totalCreditInput.value) || 0;
        const paid = parseFloat(paidAmountInput.value) || 0;
        const remaining = total - paid;

        remainingAmountInput.value = remaining.toLocaleString('id-ID');
        
        // Update summary
        document.getElementById('summary-total').textContent = 'Rp ' + total.toLocaleString('id-ID');
        document.getElementById('summary-paid').textContent = 'Rp ' + paid.toLocaleString('id-ID');
        document.getElementById('summary-remaining').textContent = 'Rp ' + remaining.toLocaleString('id-ID');
    }

    totalCreditInput.addEventListener('input', calculateRemaining);
    paidAmountInput.addEventListener('input', calculateRemaining);

    // Form Submission
    creditForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const total = parseFloat(totalCreditInput.value) || 0;
        const paid = parseFloat(paidAmountInput.value) || 0;

        if (total <= 0) {
            alert('Total kredit harus lebih dari 0');
            return;
        }

        if (paid > total) {
            alert('Jumlah dibayar tidak boleh lebih dari total kredit');
            return;
        }

        const formData = new FormData(creditForm);
        const data = Object.fromEntries(formData.entries());
        data._method = 'PUT';
        data._token = '{{ csrf_token() }}';

        // Disable submit button
        const submitBtn = document.getElementById('submit-btn');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="material-icons text-sm animate-spin">refresh</span> Menyimpan...';

        try {
            const response = await fetch('{{ route("customer-credits.update", $customerCredit->id) }}', {
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
                // Show success toast
                const toast = document.getElementById('success-toast');
                document.getElementById('success-toast-message').textContent = result.message;
                toast.classList.remove('hidden');
                toast.classList.add('show');

                // Redirect after 1 second
                setTimeout(() => {
                    window.location.href = '{{ route("customer-credits.index") }}';
                }, 1000);
            } else {
                alert(result.message || 'Gagal memperbarui data');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat memperbarui data');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });

    // Click outside to close dropdown
    document.addEventListener('click', (e) => {
        if (!customerSearch.contains(e.target) && !customerResults.contains(e.target)) {
            customerResults.classList.add('hidden');
        }
    });
});
</script>
@endpush