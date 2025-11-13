@extends('layouts.app')

@section('title', 'Laporan Laba Rugi')
@section('page-title', 'Laporan Laba Rugi')
@section('page-description', 'Laporan profit & loss statement')

@section('content')
    <!-- Filter Section -->
    <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow mb-6">
        <div class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-text-light dark:text-text-dark mb-2">Dari Tanggal</label>
                <input type="date" id="date_from" value="{{ request('date_from', now()->startOfMonth()->format('Y-m-d')) }}" 
                       class="bg-background-light dark:bg-background-dark border border-gray-300 dark:border-gray-600 text-text-light dark:text-text-dark text-sm rounded-lg focus:ring-primary focus:border-primary block w-full p-2.5">
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-text-light dark:text-text-dark mb-2">Sampai Tanggal</label>
                <input type="date" id="date_to" value="{{ request('date_to', now()->format('Y-m-d')) }}" 
                       class="bg-background-light dark:bg-background-dark border border-gray-300 dark:border-gray-600 text-text-light dark:text-text-dark text-sm rounded-lg focus:ring-primary focus:border-primary block w-full p-2.5">
            </div>
            <div>
                <button onclick="loadProfitLoss()" class="px-6 py-2.5 bg-primary text-white rounded-lg hover:bg-blue-600 flex items-center">
                    <span class="material-icons mr-2 text-sm">search</span>
                    Tampilkan
                </button>
            </div>
            <div>
                <button onclick="printReport()" class="px-6 py-2.5 bg-gray-600 text-white rounded-lg hover:bg-gray-700 flex items-center">
                    <span class="material-icons mr-2 text-sm">print</span>
                    Print
                </button>
            </div>
        </div>
    </div>

    <!-- Profit Loss Statement -->
    <div class="bg-card-light dark:bg-card-dark rounded-lg shadow" id="profitLossReport">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="text-center">
                <h2 class="text-2xl font-bold text-text-light dark:text-text-dark">LAPORAN LABA RUGI</h2>
                <p class="text-sm text-text-muted-light dark:text-text-muted-dark mt-1">UD Fadlan</p>
                <p class="text-sm text-text-muted-light dark:text-text-muted-dark" id="reportPeriod">
                    Periode: Loading...
                </p>
            </div>
        </div>

        <div class="p-6" id="reportContent">
            <div class="flex justify-center py-8">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    loadProfitLoss();
});

function loadProfitLoss() {
    const dateFrom = document.getElementById('date_from').value;
    const dateTo = document.getElementById('date_to').value;

    if (!dateFrom || !dateTo) {
        alert('Mohon isi tanggal dari dan sampai');
        return;
    }

    // Show loading
    document.getElementById('reportContent').innerHTML = `
        <div class="flex justify-center py-8">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
        </div>
    `;

    fetch(`{{ route('financial-reports.profit-loss-data') }}?date_from=${dateFrom}&date_to=${dateTo}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderProfitLoss(data.data);
            } else {
                showError('Gagal memuat data');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Terjadi kesalahan saat memuat data');
        });
}

function renderProfitLoss(data) {
    // Update period
    document.getElementById('reportPeriod').textContent = `Periode: ${data.period.label}`;

    // Render content
    const content = `
        <div class="space-y-6">
            <!-- PENDAPATAN -->
            <div>
                <h3 class="text-lg font-semibold text-text-light dark:text-text-dark mb-3 border-b-2 border-gray-300 dark:border-gray-600 pb-2">
                    PENDAPATAN
                </h3>
                <div class="space-y-2 ml-4">
                    <div class="flex justify-between">
                        <span class="text-text-muted-light dark:text-text-muted-dark">Penjualan Kotor</span>
                        <span class="text-text-light dark:text-text-dark font-medium">
                            Rp ${formatNumber(data.revenue.gross_revenue)}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-muted-light dark:text-text-muted-dark">Potongan Harga</span>
                        <span class="text-red-500">
                            (Rp ${formatNumber(data.revenue.discount)})
                        </span>
                    </div>
                    <div class="flex justify-between pt-2 border-t border-gray-200 dark:border-gray-700">
                        <span class="font-semibold text-text-light dark:text-text-dark">Penjualan Bersih</span>
                        <span class="font-bold text-text-light dark:text-text-dark">
                            Rp ${formatNumber(data.revenue.net_revenue)}
                        </span>
                    </div>
                </div>
            </div>

            <!-- HARGA POKOK PENJUALAN -->
            <div>
                <h3 class="text-lg font-semibold text-text-light dark:text-text-dark mb-3 border-b-2 border-gray-300 dark:border-gray-600 pb-2">
                    HARGA POKOK PENJUALAN
                </h3>
                <div class="space-y-2 ml-4">
                    <div class="flex justify-between">
                        <span class="text-text-muted-light dark:text-text-muted-dark">HPP</span>
                        <span class="text-text-light dark:text-text-dark font-medium">
                            Rp ${formatNumber(data.costs.cogs)}
                        </span>
                    </div>
                </div>
            </div>

            <!-- LABA KOTOR -->
            <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                <div class="flex justify-between items-center">
                    <span class="text-lg font-bold text-text-light dark:text-text-dark">LABA KOTOR</span>
                    <span class="text-xl font-bold text-blue-600 dark:text-blue-400">
                        Rp ${formatNumber(data.profit.gross_profit)}
                    </span>
                </div>
            </div>

            <!-- BEBAN OPERASIONAL -->
            <div>
                <h3 class="text-lg font-semibold text-text-light dark:text-text-dark mb-3 border-b-2 border-gray-300 dark:border-gray-600 pb-2">
                    BEBAN OPERASIONAL
                </h3>
                <div class="space-y-2 ml-4">
                    <div class="flex justify-between">
                        <span class="text-text-muted-light dark:text-text-muted-dark">Beban Operasional</span>
                        <span class="text-text-light dark:text-text-dark font-medium">
                            Rp ${formatNumber(data.costs.operational_expenses)}
                        </span>
                    </div>
                    <div class="flex justify-between pt-2 border-t border-gray-200 dark:border-gray-700">
                        <span class="font-semibold text-text-light dark:text-text-dark">Total Beban</span>
                        <span class="font-bold text-text-light dark:text-text-dark">
                            Rp ${formatNumber(data.costs.total_costs)}
                        </span>
                    </div>
                </div>
            </div>

            <!-- LABA BERSIH -->
            <div class="bg-green-50 dark:bg-green-900/20 p-6 rounded-lg border-2 border-green-500">
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-xl font-bold text-text-light dark:text-text-dark">LABA BERSIH</span>
                        <span class="text-2xl font-bold ${data.profit.net_profit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'}">
                            Rp ${formatNumber(data.profit.net_profit)}
                        </span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-text-muted-light dark:text-text-muted-dark">Margin Laba</span>
                        <span class="font-semibold ${data.profit.profit_margin >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'}">
                            ${data.profit.profit_margin}%
                        </span>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.getElementById('reportContent').innerHTML = content;
}

function formatNumber(num) {
    return new Intl.NumberFormat('id-ID').format(num);
}

function showError(message) {
    document.getElementById('reportContent').innerHTML = `
        <div class="text-center py-8">
            <span class="material-icons text-red-500 text-5xl mb-3">error_outline</span>
            <p class="text-text-muted-light dark:text-text-muted-dark">${message}</p>
        </div>
    `;
}

function printReport() {
    window.print();
}
</script>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    #profitLossReport, #profitLossReport * {
        visibility: visible;
    }
    #profitLossReport {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
    .no-print {
        display: none !important;
    }
}
</style>
@endpush