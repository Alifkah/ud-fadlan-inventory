@extends('layouts.app')

@section('title', 'Pengaturan Dashboard')
@section('page-title', 'Pengaturan Dashboard')
@section('page-description', 'Kustomisasi tampilan dan preferensi dashboard Anda')

@section('content')
<div class="max-w-4xl mx-auto">
    <form action="{{ route('dashboard.update') }}" method="POST" id="dashboardSettingsForm">
        @csrf
        @method('PUT')

        <!-- Card: Tampilan Umum -->
        <div class="bg-card-light dark:bg-card-dark rounded-lg shadow mb-6">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-semibold text-text-light dark:text-text-dark">Tampilan Umum</h2>
                <p class="text-sm text-text-muted-light dark:text-text-muted-dark mt-1">Atur preferensi tampilan dashboard</p>
            </div>
            <div class="p-6 space-y-4">
                <!-- Default Date Range -->
                <div>
                    <label class="block text-sm font-medium text-text-light dark:text-text-dark mb-2">
                        Rentang Tanggal Default
                    </label>
                    <select name="default_date_range" class="bg-background-light dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-text-light dark:text-text-dark text-sm rounded-lg focus:ring-primary focus:border-primary block w-full p-2.5">
                        <option value="today" {{ old('default_date_range', $settings->default_date_range ?? 'today') == 'today' ? 'selected' : '' }}>Hari Ini</option>
                        <option value="week" {{ old('default_date_range', $settings->default_date_range ?? '') == 'week' ? 'selected' : '' }}>Minggu Ini</option>
                        <option value="month" {{ old('default_date_range', $settings->default_date_range ?? '') == 'month' ? 'selected' : '' }}>Bulan Ini</option>
                        <option value="year" {{ old('default_date_range', $settings->default_date_range ?? '') == 'year' ? 'selected' : '' }}>Tahun Ini</option>
                    </select>
                </div>

                <!-- Default Chart Period -->
                <div>
                    <label class="block text-sm font-medium text-text-light dark:text-text-dark mb-2">
                        Periode Chart Default
                    </label>
                    <select name="default_chart_period" class="bg-background-light dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-text-light dark:text-text-dark text-sm rounded-lg focus:ring-primary focus:border-primary block w-full p-2.5">
                        <option value="day" {{ old('default_chart_period', $settings->default_chart_period ?? '') == 'day' ? 'selected' : '' }}>Harian</option>
                        <option value="week" {{ old('default_chart_period', $settings->default_chart_period ?? '') == 'week' ? 'selected' : '' }}>Mingguan</option>
                        <option value="month" {{ old('default_chart_period', $settings->default_chart_period ?? 'month') == 'month' ? 'selected' : '' }}>Bulanan</option>
                    </select>
                </div>

                <!-- Items Per Page -->
                <div>
                    <label class="block text-sm font-medium text-text-light dark:text-text-dark mb-2">
                        Jumlah Transaksi Ditampilkan
                    </label>
                    <select name="items_per_page" class="bg-background-light dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-text-light dark:text-text-dark text-sm rounded-lg focus:ring-primary focus:border-primary block w-full p-2.5">
                        <option value="5" {{ old('items_per_page', $settings->items_per_page ?? '') == '5' ? 'selected' : '' }}>5 Transaksi</option>
                        <option value="6" {{ old('items_per_page', $settings->items_per_page ?? '6') == '6' ? 'selected' : '' }}>6 Transaksi</option>
                        <option value="10" {{ old('items_per_page', $settings->items_per_page ?? '') == '10' ? 'selected' : '' }}>10 Transaksi</option>
                        <option value="15" {{ old('items_per_page', $settings->items_per_page ?? '') == '15' ? 'selected' : '' }}>15 Transaksi</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Card: Widget Visibility -->
        <div class="bg-card-light dark:bg-card-dark rounded-lg shadow mb-6">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-semibold text-text-light dark:text-text-dark">Tampilan Widget</h2>
                <p class="text-sm text-text-muted-light dark:text-text-muted-dark mt-1">Pilih widget yang ingin ditampilkan</p>
            </div>
            <div class="p-6 space-y-3">
                <!-- Revenue Widget -->
                <div class="flex items-center">
                    <input type="checkbox" name="show_revenue" id="show_revenue" value="1" 
                           {{ old('show_revenue', $settings->show_revenue ?? true) ? 'checked' : '' }}
                           class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary dark:focus:ring-primary dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                    <label for="show_revenue" class="ml-2 text-sm font-medium text-text-light dark:text-text-dark">
                        Pendapatan Hari Ini
                    </label>
                </div>

                <!-- Transactions Widget -->
                <div class="flex items-center">
                    <input type="checkbox" name="show_transactions" id="show_transactions" value="1"
                           {{ old('show_transactions', $settings->show_transactions ?? true) ? 'checked' : '' }}
                           class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary dark:focus:ring-primary dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                    <label for="show_transactions" class="ml-2 text-sm font-medium text-text-light dark:text-text-dark">
                        Transaksi Hari Ini
                    </label>
                </div>

                <!-- Low Stock Widget -->
                <div class="flex items-center">
                    <input type="checkbox" name="show_low_stock" id="show_low_stock" value="1"
                           {{ old('show_low_stock', $settings->show_low_stock ?? true) ? 'checked' : '' }}
                           class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary dark:focus:ring-primary dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                    <label for="show_low_stock" class="ml-2 text-sm font-medium text-text-light dark:text-text-dark">
                        Stok Menipis
                    </label>
                </div>

                <!-- Products Widget -->
                <div class="flex items-center">
                    <input type="checkbox" name="show_products" id="show_products" value="1"
                           {{ old('show_products', $settings->show_products ?? true) ? 'checked' : '' }}
                           class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary dark:focus:ring-primary dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                    <label for="show_products" class="ml-2 text-sm font-medium text-text-light dark:text-text-dark">
                        Total Produk
                    </label>
                </div>

                <!-- Sales Chart -->
                <div class="flex items-center">
                    <input type="checkbox" name="show_sales_chart" id="show_sales_chart" value="1"
                           {{ old('show_sales_chart', $settings->show_sales_chart ?? true) ? 'checked' : '' }}
                           class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary dark:focus:ring-primary dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                    <label for="show_sales_chart" class="ml-2 text-sm font-medium text-text-light dark:text-text-dark">
                        Chart Trend Penjualan
                    </label>
                </div>

                <!-- Category Chart -->
                <div class="flex items-center">
                    <input type="checkbox" name="show_category_chart" id="show_category_chart" value="1"
                           {{ old('show_category_chart', $settings->show_category_chart ?? true) ? 'checked' : '' }}
                           class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary dark:focus:ring-primary dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                    <label for="show_category_chart" class="ml-2 text-sm font-medium text-text-light dark:text-text-dark">
                        Chart Kategori Penjualan
                    </label>
                </div>

                <!-- Recent Transactions -->
                <div class="flex items-center">
                    <input type="checkbox" name="show_recent_transactions" id="show_recent_transactions" value="1"
                           {{ old('show_recent_transactions', $settings->show_recent_transactions ?? true) ? 'checked' : '' }}
                           class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary dark:focus:ring-primary dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                    <label for="show_recent_transactions" class="ml-2 text-sm font-medium text-text-light dark:text-text-dark">
                        Transaksi Terbaru
                    </label>
                </div>
            </div>
        </div>

        <!-- Card: Notifikasi -->
        <div class="bg-card-light dark:bg-card-dark rounded-lg shadow mb-6">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-semibold text-text-light dark:text-text-dark">Notifikasi</h2>
                <p class="text-sm text-text-muted-light dark:text-text-muted-dark mt-1">Atur preferensi notifikasi dashboard</p>
            </div>
            <div class="p-6 space-y-3">
                <!-- Low Stock Alert -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input type="checkbox" name="notify_low_stock" id="notify_low_stock" value="1"
                               {{ old('notify_low_stock', $settings->notify_low_stock ?? true) ? 'checked' : '' }}
                               class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary dark:focus:ring-primary dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                        <label for="notify_low_stock" class="ml-2 text-sm font-medium text-text-light dark:text-text-dark">
                            Peringatan Stok Menipis
                        </label>
                    </div>
                </div>

                <!-- Credit Due Alert -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input type="checkbox" name="notify_credit_due" id="notify_credit_due" value="1"
                               {{ old('notify_credit_due', $settings->notify_credit_due ?? true) ? 'checked' : '' }}
                               class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary dark:focus:ring-primary dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                        <label for="notify_credit_due" class="ml-2 text-sm font-medium text-text-light dark:text-text-dark">
                            Peringatan Kredit Jatuh Tempo
                        </label>
                    </div>
                </div>

                <!-- Daily Summary -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input type="checkbox" name="notify_daily_summary" id="notify_daily_summary" value="1"
                               {{ old('notify_daily_summary', $settings->notify_daily_summary ?? false) ? 'checked' : '' }}
                               class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary dark:focus:ring-primary dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                        <label for="notify_daily_summary" class="ml-2 text-sm font-medium text-text-light dark:text-text-dark">
                            Ringkasan Harian
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card: Advanced Settings -->
        <div class="bg-card-light dark:bg-card-dark rounded-lg shadow mb-6">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-semibold text-text-light dark:text-text-dark">Pengaturan Lanjutan</h2>
                <p class="text-sm text-text-muted-light dark:text-text-muted-dark mt-1">Opsi tambahan untuk dashboard</p>
            </div>
            <div class="p-6 space-y-4">
                <!-- Auto Refresh -->
                <div>
                    <label class="block text-sm font-medium text-text-light dark:text-text-dark mb-2">
                        Auto Refresh Data (menit)
                    </label>
                    <select name="auto_refresh" class="bg-background-light dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-text-light dark:text-text-dark text-sm rounded-lg focus:ring-primary focus:border-primary block w-full p-2.5">
                        <option value="0" {{ old('auto_refresh', $settings->auto_refresh ?? '0') == '0' ? 'selected' : '' }}>Tidak Aktif</option>
                        <option value="1" {{ old('auto_refresh', $settings->auto_refresh ?? '') == '1' ? 'selected' : '' }}>1 Menit</option>
                        <option value="5" {{ old('auto_refresh', $settings->auto_refresh ?? '') == '5' ? 'selected' : '' }}>5 Menit</option>
                        <option value="10" {{ old('auto_refresh', $settings->auto_refresh ?? '') == '10' ? 'selected' : '' }}>10 Menit</option>
                        <option value="30" {{ old('auto_refresh', $settings->auto_refresh ?? '') == '30' ? 'selected' : '' }}>30 Menit</option>
                    </select>
                    <p class="text-xs text-text-muted-light dark:text-text-muted-dark mt-1">
                        Dashboard akan memuat ulang data secara otomatis
                    </p>
                </div>

                <!-- Currency Format -->
                <div>
                    <label class="block text-sm font-medium text-text-light dark:text-text-dark mb-2">
                        Format Mata Uang
                    </label>
                    <select name="currency_format" class="bg-background-light dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-text-light dark:text-text-dark text-sm rounded-lg focus:ring-primary focus:border-primary block w-full p-2.5">
                        <option value="id" {{ old('currency_format', $settings->currency_format ?? 'id') == 'id' ? 'selected' : '' }}>Rp 1.000.000</option>
                        <option value="en" {{ old('currency_format', $settings->currency_format ?? '') == 'en' ? 'selected' : '' }}>Rp 1,000,000</option>
                    </select>
                </div>

                <!-- Show Animation -->
                <div class="flex items-center">
                    <input type="checkbox" name="show_animations" id="show_animations" value="1"
                           {{ old('show_animations', $settings->show_animations ?? true) ? 'checked' : '' }}
                           class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary dark:focus:ring-primary dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                    <label for="show_animations" class="ml-2 text-sm font-medium text-text-light dark:text-text-dark">
                        Aktifkan Animasi
                    </label>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap gap-4 justify-end">
            <a href="{{ route('dashboard.index') }}" class="px-6 py-2.5 text-sm font-medium text-text-light dark:text-text-dark bg-background-light dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 focus:ring-4 focus:outline-none focus:ring-gray-200 dark:focus:ring-gray-700">
                <span class="material-icons text-sm align-middle mr-1">close</span>
                Batal
            </a>
            <button type="button" onclick="resetToDefault()" class="px-6 py-2.5 text-sm font-medium text-white bg-yellow-500 rounded-lg hover:bg-yellow-600 focus:ring-4 focus:outline-none focus:ring-yellow-300">
                <span class="material-icons text-sm align-middle mr-1">refresh</span>
                Reset ke Default
            </button>
            <button type="submit" class="px-6 py-2.5 text-sm font-medium text-white bg-primary rounded-lg hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300">
                <span class="material-icons text-sm align-middle mr-1">save</span>
                Simpan Pengaturan
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function resetToDefault() {
    if (confirm('Apakah Anda yakin ingin mereset semua pengaturan ke default?')) {
        // Set default values
        document.querySelector('[name="default_date_range"]').value = 'today';
        document.querySelector('[name="default_chart_period"]').value = 'month';
        document.querySelector('[name="items_per_page"]').value = '6';
        
        // Check all widgets
        document.querySelectorAll('[name^="show_"]').forEach(checkbox => {
            checkbox.checked = true;
        });
        
        // Check notification preferences
        document.getElementById('notify_low_stock').checked = true;
        document.getElementById('notify_credit_due').checked = true;
        document.getElementById('notify_daily_summary').checked = false;
        
        // Advanced settings
        document.querySelector('[name="auto_refresh"]').value = '0';
        document.querySelector('[name="currency_format"]').value = 'id';
        document.getElementById('show_animations').checked = true;
    }
}

// Form validation
document.getElementById('dashboardSettingsForm').addEventListener('submit', function(e) {
    // Show loading state
    const submitBtn = this.querySelector('[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="material-icons text-sm align-middle animate-spin mr-1">refresh</span> Menyimpan...';
    
    // Re-enable after 3 seconds (as backup)
    setTimeout(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }, 3000);
});
</script>
@endpush