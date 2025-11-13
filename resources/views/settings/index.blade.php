@extends('layouts.app')

@section('title', 'Pengaturan')
@section('page-title', 'Pengaturan')
@section('page-description', 'Kelola pengaturan sistem dan preferensi aplikasi')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Profil Toko -->
    <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow-sm">
        <h3 class="text-lg font-semibold mb-4 text-text-light dark:text-text-dark">Profil Toko</h3>
        <form id="storeProfileForm" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-text-muted-light dark:text-text-muted-dark" for="store_name">Nama Toko</label>
                <input class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-background-light dark:bg-input-dark text-text-light dark:text-text-dark shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" 
                       id="store_name" 
                       name="store_name" 
                       type="text"
                       value="{{ $settings->get('store_name')->value ?? 'UD FADLAN' }}"
                       required/>
            </div>
            <div>
                <label class="block text-sm font-medium text-text-muted-light dark:text-text-muted-dark" for="store_address">Alamat</label>
                <input class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-background-light dark:bg-input-dark text-text-light dark:text-text-dark shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" 
                       id="store_address" 
                       name="store_address" 
                       type="text"
                       value="{{ $settings->get('store_address')->value ?? '' }}"/>
            </div>
            <div>
                <label class="block text-sm font-medium text-text-muted-light dark:text-text-muted-dark" for="store_phone">Telepon</label>
                <input class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-background-light dark:bg-input-dark text-text-light dark:text-text-dark shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" 
                       id="store_phone" 
                       name="store_phone" 
                       type="tel"
                       value="{{ $settings->get('store_phone')->value ?? '' }}"/>
            </div>
            <div>
                <label class="block text-sm font-medium text-text-muted-light dark:text-text-muted-dark" for="store_email">Email</label>
                <input class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-background-light dark:bg-input-dark text-text-light dark:text-text-dark shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" 
                       id="store_email" 
                       name="store_email" 
                       type="email"
                       value="{{ $settings->get('store_email')->value ?? '' }}"/>
            </div>
            <div class="pt-2">
                <button class="w-auto px-4 py-2 bg-primary text-white rounded-md text-sm font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary" type="submit">Simpan Perubahan</button>
            </div>
        </form>
    </div>

    <!-- Pengaturan Pengguna -->
    <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow-sm">
        <h3 class="text-lg font-semibold mb-4 text-text-light dark:text-text-dark">Pengaturan Pengguna</h3>
        <div class="flex items-center mb-6">
            <div class="w-16 h-16 bg-primary rounded-full flex items-center justify-center text-white font-bold text-3xl">
                {{ substr($user->name, 0, 1) }}
            </div>
            <div class="ml-4">
                <p class="font-semibold text-lg text-text-light dark:text-text-dark">{{ $user->name }}</p>
                <p class="text-sm text-text-muted-light dark:text-text-muted-dark">{{ ucfirst($user->role ?? 'Admin') }}</p>
            </div>
        </div>
        <form id="userSettingsForm" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-text-muted-light dark:text-text-muted-dark" for="name">Nama Lengkap</label>
                <input class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-background-light dark:bg-input-dark text-text-light dark:text-text-dark shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" 
                       id="name" 
                       name="name" 
                       type="text"
                       value="{{ $user->name }}"
                       required/>
            </div>
            <div>
                <label class="block text-sm font-medium text-text-muted-light dark:text-text-muted-dark" for="email">Email</label>
                <input class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-background-light dark:bg-input-dark text-text-light dark:text-text-dark shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" 
                       id="email" 
                       name="email" 
                       type="email"
                       value="{{ $user->email }}"
                       required/>
            </div>
            <div>
                <label class="block text-sm font-medium text-text-muted-light dark:text-text-muted-dark" for="current_password">Password Lama</label>
                <input class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-background-light dark:bg-input-dark text-text-light dark:text-text-dark shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" 
                       id="current_password" 
                       name="current_password" 
                       type="password"/>
                <p class="mt-1 text-xs text-text-muted-light dark:text-text-muted-dark">Kosongkan jika tidak ingin mengubah password</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-text-muted-light dark:text-text-muted-dark" for="new_password">Password Baru</label>
                <input class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-background-light dark:bg-input-dark text-text-light dark:text-text-dark shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" 
                       id="new_password" 
                       name="new_password" 
                       type="password"/>
            </div>
            <div>
                <label class="block text-sm font-medium text-text-muted-light dark:text-text-muted-dark" for="new_password_confirmation">Konfirmasi Password Baru</label>
                <input class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-background-light dark:bg-input-dark text-text-light dark:text-text-dark shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" 
                       id="new_password_confirmation" 
                       name="new_password_confirmation" 
                       type="password"/>
            </div>
            <div class="pt-2">
                <button class="w-auto px-4 py-2 bg-primary text-white rounded-md text-sm font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary" type="submit">Update Profil</button>
            </div>
        </form>
    </div>

    <!-- Manajemen Pengguna -->
    <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow-sm col-span-1 lg:col-span-2">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-text-light dark:text-text-dark">Manajemen Pengguna</h3>
            <button class="flex items-center px-4 py-2 bg-primary text-white rounded-md text-sm font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary" type="button" onclick="alert('Fitur tambah pengguna akan segera tersedia')">
                <span class="material-icons mr-2">add</span>
                Tambah Pengguna
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-text-muted-light dark:text-text-muted-dark">
                <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-700 text-text-muted-light dark:text-text-muted-dark">
                    <tr>
                        <th class="px-6 py-3" scope="col">Nama</th>
                        <th class="px-6 py-3" scope="col">Email</th>
                        <th class="px-6 py-3" scope="col">Peran</th>
                        <th class="px-6 py-3" scope="col">Status</th>
                        <th class="px-6 py-3" scope="col">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $u)
                    <tr class="bg-card-light dark:bg-card-dark border-b dark:border-gray-700">
                        <td class="px-6 py-4 font-medium text-text-light dark:text-text-dark whitespace-nowrap">{{ $u->name }}</td>
                        <td class="px-6 py-4">{{ $u->email }}</td>
                        <td class="px-6 py-4">{{ ucfirst($u->role ?? 'Staff') }}</td>
                        <td class="px-6 py-4">
                            @if($u->is_active)
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">Aktif</span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">Tidak Aktif</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <a class="font-medium text-primary hover:underline cursor-pointer" onclick="alert('Fitur edit pengguna akan segera tersedia')">Edit</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-text-muted-light dark:text-text-muted-dark">Tidak ada data pengguna</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pengaturan Sistem -->
    <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow-sm">
        <h3 class="text-lg font-semibold mb-4 text-text-light dark:text-text-dark">Pengaturan Sistem</h3>
        <form id="systemSettingsForm" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-text-muted-light dark:text-text-muted-dark" for="timezone">Zona Waktu</label>
                <select class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-background-light dark:bg-input-dark text-text-light dark:text-text-dark shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" 
                        id="timezone" 
                        name="timezone"
                        required>
                    @foreach($timezones as $key => $value)
                        <option value="{{ $key }}" {{ ($settings->get('timezone')->value ?? 'Asia/Jakarta') == $key ? 'selected' : '' }}>{{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-text-muted-light dark:text-text-muted-dark" for="date_format">Format Tanggal</label>
                <select class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-background-light dark:bg-input-dark text-text-light dark:text-text-dark shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" 
                        id="date_format" 
                        name="date_format"
                        required>
                    <option value="d/m/Y" {{ ($settings->get('date_format')->value ?? 'd/m/Y') == 'd/m/Y' ? 'selected' : '' }}>DD/MM/YYYY</option>
                    <option value="m/d/Y" {{ ($settings->get('date_format')->value ?? 'd/m/Y') == 'm/d/Y' ? 'selected' : '' }}>MM/DD/YYYY</option>
                    <option value="Y-m-d" {{ ($settings->get('date_format')->value ?? 'd/m/Y') == 'Y-m-d' ? 'selected' : '' }}>YYYY-MM-DD</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-text-muted-light dark:text-text-muted-dark" for="currency">Mata Uang</label>
                <select class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-background-light dark:bg-input-dark text-text-light dark:text-text-dark shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" 
                        id="currency" 
                        name="currency"
                        required>
                    @foreach($currencies as $key => $value)
                        <option value="{{ $key }}" {{ ($settings->get('currency')->value ?? 'IDR') == $key ? 'selected' : '' }}>{{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-text-muted-light dark:text-text-muted-dark" for="language">Bahasa</label>
                <select class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-background-light dark:bg-input-dark text-text-light dark:text-text-dark shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" 
                        id="language" 
                        name="language"
                        required>
                    @foreach($languages as $key => $value)
                        <option value="{{ $key }}" {{ ($settings->get('language')->value ?? 'id') == $key ? 'selected' : '' }}>{{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="pt-2">
                <button class="w-auto px-4 py-2 bg-primary text-white rounded-md text-sm font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary" type="submit">Simpan Pengaturan</button>
            </div>
        </form>
    </div>

    <!-- Notifikasi -->
    <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow-sm">
        <h3 class="text-lg font-semibold mb-4 text-text-light dark:text-text-dark">Notifikasi</h3>
        <form id="notificationSettingsForm">
            @csrf
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-text-light dark:text-text-dark">Notifikasi Stok Rendah</p>
                        <p class="text-sm text-text-muted-light dark:text-text-muted-dark">Dapatkan pemberitahuan ketika stok barang menipis</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input class="sr-only peer toggle-notification" 
                               type="checkbox" 
                               name="notification_low_stock"
                               value="1"
                               {{ ($settings->get('notification_low_stock')->value ?? true) ? 'checked' : '' }}/>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary"></div>
                    </label>
                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-text-light dark:text-text-dark">Notifikasi Transaksi</p>
                        <p class="text-sm text-text-muted-light dark:text-text-muted-dark">Pemberitahuan untuk setiap transaksi baru</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input class="sr-only peer toggle-notification" 
                               type="checkbox" 
                               name="notification_transaction"
                               value="1"
                               {{ ($settings->get('notification_transaction')->value ?? true) ? 'checked' : '' }}/>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary"></div>
                    </label>
                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-text-light dark:text-text-dark">Notifikasi Jatuh Tempo</p>
                        <p class="text-sm text-text-muted-light dark:text-text-muted-dark">Reminder untuk kredit yang akan jatuh tempo</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input class="sr-only peer toggle-notification" 
                               type="checkbox" 
                               name="notification_due_date"
                               value="1"
                               {{ ($settings->get('notification_due_date')->value ?? false) ? 'checked' : '' }}/>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary"></div>
                    </label>
                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-text-light dark:text-text-dark">Notifikasi Email</p>
                        <p class="text-sm text-text-muted-light dark:text-text-muted-dark">Terima notifikasi melalui email</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input class="sr-only peer toggle-notification" 
                               type="checkbox" 
                               name="notification_email"
                               value="1"
                               {{ ($settings->get('notification_email')->value ?? true) ? 'checked' : '' }}/>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary"></div>
                    </label>
                </div>
            </div>
        </form>
    </div>

    <!-- Backup & Restore -->
    <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow-sm">
        <h3 class="text-lg font-semibold mb-4 text-text-light dark:text-text-dark">Backup & Restore</h3>
        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-md mb-6">
            <p class="text-sm text-text-light dark:text-text-dark">
                <strong>Backup Terakhir:</strong> 
                {{ $backup_info['last_backup'] ? \Carbon\Carbon::parse($backup_info['last_backup'])->format('d F Y, H:i') . ' WITA' : 'Belum ada backup' }}
            </p>
            <p class="text-sm text-text-light dark:text-text-dark">
                <strong>Ukuran Database:</strong> {{ $backup_info['database_size'] ?? 'Unknown' }}
            </p>
        </div>
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-text-muted-light dark:text-text-muted-dark" for="backup_frequency">Backup Otomatis</label>
                <select class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-background-light dark:bg-input-dark text-text-light dark:text-text-dark shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" 
                        id="backup_frequency">
                    <option value="daily">Harian</option>
                    <option value="weekly">Mingguan</option>
                    <option value="monthly">Bulanan</option>
                    <option value="manual" selected>Manual</option>
                </select>
            </div>
            <div class="pt-2 flex space-x-3">
                <button id="btnBackupNow" class="w-auto px-4 py-2 bg-primary text-white rounded-md text-sm font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary" type="button">Backup Sekarang</button>
                <button class="w-auto px-4 py-2 bg-gray-600 text-white rounded-md text-sm font-medium hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500" type="button" onclick="alert('Fitur restore akan segera tersedia')">Restore Data</button>
            </div>
        </div>
    </div>

    <!-- Keamanan -->
    <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow-sm">
        <h3 class="text-lg font-semibold mb-4 text-text-light dark:text-text-dark">Keamanan</h3>
        <form id="securitySettingsForm" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-text-muted-light dark:text-text-muted-dark" for="session_timeout">Timeout Sesi (menit)</label>
                <input class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-background-light dark:bg-input-dark text-text-light dark:text-text-dark shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" 
                       id="session_timeout" 
                       name="session_timeout" 
                       type="number"
                       min="5"
                       max="1440"
                       value="{{ $settings->get('session_timeout')->value ?? 120 }}"
                       required/>
            </div>
            <div class="flex items-center justify-between pt-2">
                <div>
                    <p class="font-medium text-text-light dark:text-text-dark">Autentikasi Dua Faktor</p>
                    <p class="text-sm text-text-muted-light dark:text-text-muted-dark">Tingkatkan keamanan dengan verifikasi 2 langkah</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input class="sr-only peer toggle-security" 
                           type="checkbox" 
                           name="two_factor_auth"
                           value="1"
                           {{ ($settings->get('two_factor_auth')->value ?? false) ? 'checked' : '' }}/>
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary"></div>
                </label>
            </div>
            <div class="flex items-center justify-between pt-2">
                <div>
                    <p class="font-medium text-text-light dark:text-text-dark">Log Aktivitas</p>
                    <p class="text-sm text-text-muted-light dark:text-text-muted-dark">Lacak semua aktivitas pengguna</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input class="sr-only peer toggle-security" 
                           type="checkbox" 
                           name="login_attempts"
                           value="1"
                           {{ ($settings->get('login_attempts')->value ?? true) ? 'checked' : '' }}/>
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary"></div>
                </label>
            </div>
            <div class="pt-2">
                <button class="w-auto px-4 py-2 bg-primary text-white rounded-md text-sm font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary" type="submit">Simpan Pengaturan Keamanan</button>
            </div>
            <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                <button id="btnResetSettings" class="w-auto px-4 py-2 bg-red-600 text-white rounded-md text-sm font-medium hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500" type="button">Reset Riwayat Pengaturan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // CSRF Token
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // Helper function untuk menampilkan notifikasi
    function showNotification(message, type = 'success') {
        const bgColor = type === 'success' ? 'bg-green-500' : 'bg-red-500';
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg z-50 transition-opacity duration-300`;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    // Form Profil Toko
    document.getElementById('storeProfileForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        try {
            const response = await fetch('{{ route("settings.update-store-profile") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showNotification(data.message, 'success');
            } else {
                showNotification(data.message || 'Terjadi kesalahan', 'error');
            }
        } catch (error) {
            showNotification('Gagal menyimpan perubahan', 'error');
            console.error('Error:', error);
        }
    });

    // Form Pengaturan Pengguna
    document.getElementById('userSettingsForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        // Validasi password
        const newPassword = formData.get('new_password');
        const confirmPassword = formData.get('new_password_confirmation');
        
        if (newPassword && newPassword !== confirmPassword) {
            showNotification('Password baru dan konfirmasi tidak cocok', 'error');
            return;
        }
        
        try {
            const response = await fetch('{{ route("settings.update-user-settings") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showNotification(data.message, 'success');
                // Reset password fields
                document.getElementById('current_password').value = '';
                document.getElementById('new_password').value = '';
                document.getElementById('new_password_confirmation').value = '';
            } else {
                showNotification(data.message || 'Terjadi kesalahan', 'error');
            }
        } catch (error) {
            showNotification('Gagal menyimpan perubahan', 'error');
            console.error('Error:', error);
        }
    });

    // Form Pengaturan Sistem
    document.getElementById('systemSettingsForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        try {
            const response = await fetch('{{ route("settings.update-system-settings") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showNotification(data.message, 'success');
            } else {
                showNotification(data.message || 'Terjadi kesalahan', 'error');
            }
        } catch (error) {
            showNotification('Gagal menyimpan pengaturan', 'error');
            console.error('Error:', error);
        }
    });

    // Toggle Notifikasi (auto-save)
    document.querySelectorAll('.toggle-notification').forEach(toggle => {
        toggle.addEventListener('change', async function() {
            const formData = new FormData();
            formData.append('_token', csrfToken);
            
            // Ambil semua toggle notification
            document.querySelectorAll('.toggle-notification').forEach(t => {
                formData.append(t.name, t.checked ? '1' : '0');
            });
            
            try {
                const response = await fetch('{{ route("settings.update-notification-settings") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification('Pengaturan notifikasi berhasil disimpan', 'success');
                } else {
                    showNotification(data.message || 'Terjadi kesalahan', 'error');
                }
            } catch (error) {
                showNotification('Gagal menyimpan pengaturan notifikasi', 'error');
                console.error('Error:', error);
            }
        });
    });

    // Form Pengaturan Keamanan
    document.getElementById('securitySettingsForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        // Tambahkan nilai toggle
        document.querySelectorAll('.toggle-security').forEach(toggle => {
            formData.append(toggle.name, toggle.checked ? '1' : '0');
        });
        
        try {
            const response = await fetch('{{ route("settings.update-security-settings") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showNotification(data.message, 'success');
            } else {
                showNotification(data.message || 'Terjadi kesalahan', 'error');
            }
        } catch (error) {
            showNotification('Gagal menyimpan pengaturan keamanan', 'error');
            console.error('Error:', error);
        }
    });

    // Backup Now
    document.getElementById('btnBackupNow').addEventListener('click', async function() {
        if (!confirm('Apakah Anda yakin ingin membuat backup sekarang?')) {
            return;
        }
        
        this.disabled = true;
        this.textContent = 'Memproses...';
        
        try {
            const response = await fetch('{{ route("settings.create-backup") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                showNotification('Backup berhasil dibuat', 'success');
                // Reload halaman setelah 1 detik untuk update info backup
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification(data.message || 'Gagal membuat backup', 'error');
            }
        } catch (error) {
            showNotification('Gagal membuat backup', 'error');
            console.error('Error:', error);
        } finally {
            this.disabled = false;
            this.textContent = 'Backup Sekarang';
        }
    });

    // Reset Settings
    document.getElementById('btnResetSettings').addEventListener('click', async function() {
        if (!confirm('Apakah Anda yakin ingin mereset semua pengaturan ke default? Tindakan ini tidak dapat dibatalkan!')) {
            return;
        }
        
        if (!confirm('Peringatan: Profil toko dan data pengguna tidak akan terpengaruh. Lanjutkan?')) {
            return;
        }
        
        this.disabled = true;
        this.textContent = 'Memproses...';
        
        try {
            const response = await fetch('{{ route("settings.reset-settings") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                showNotification('Pengaturan berhasil direset', 'success');
                // Reload halaman setelah 1 detik
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification(data.message || 'Gagal reset pengaturan', 'error');
            }
        } catch (error) {
            showNotification('Gagal reset pengaturan', 'error');
            console.error('Error:', error);
        } finally {
            this.disabled = false;
            this.textContent = 'Reset Riwayat Pengaturan';
        }
    });
});
</script>
@endpush