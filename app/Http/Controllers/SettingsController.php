<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    /**
     * Tampilkan halaman pengaturan
     */
    public function index()
    {
        $user = Auth::user();
        
        // Ambil semua pengaturan sistem
        $settings = Setting::all()->keyBy('key');
        
        // Ambil semua user untuk manajemen pengguna
        $users = User::all();
        
        // Data untuk form
        $data = [
            'user' => $user,
            'users' => $users,
            'settings' => $settings,
            'timezones' => $this->getTimezones(),
            'currencies' => $this->getCurrencies(),
            'languages' => $this->getLanguages(),
            'backup_info' => $this->getBackupInfo()
        ];

        return view('settings.index', $data);
    }

    /**
     * Update profil toko/perusahaan
     */
    public function updateStoreProfile(Request $request)
    {
        $validated = $request->validate([
            'store_name' => 'required|string|max:255',
            'store_address' => 'nullable|string|max:500',
            'store_phone' => 'nullable|string|max:20',
            'store_email' => 'nullable|email|max:255'
        ]);

        try {
            foreach ($validated as $key => $value) {
                // Jika value kosong/null, set dengan string kosong
                $finalValue = $value ?? '';
                Setting::set($key, $finalValue, 'string', $this->getSettingDescription($key), 'store');
            }

            return response()->json([
                'success' => true,
                'message' => 'Profil toko berhasil diperbarui'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui profil toko: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update pengaturan pengguna
     */
    public function updateUserSettings(Request $request)
    {
        $user = User::find(Auth::id());
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'current_password' => 'nullable|required_with:new_password|string',
            'new_password' => 'nullable|string|min:8|confirmed'
        ]);

        try {
            DB::beginTransaction();

            // Update nama dan email
            $updateData = [
                'name' => $validated['name'],
                'email' => $validated['email']
            ];

            // Jika ada password baru
            if (!empty($validated['new_password'])) {
                // Verifikasi password lama
                if (!Hash::check($validated['current_password'], $user->password)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Password lama tidak sesuai'
                    ], 422);
                }

                $updateData['password'] = Hash::make($validated['new_password']);
            }

            $user->update($updateData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pengaturan pengguna berhasil diperbarui'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui pengaturan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update pengaturan sistem
     */
    public function updateSystemSettings(Request $request)
    {
        $validated = $request->validate([
            'timezone' => 'required|string|max:100',
            'date_format' => 'required|string|max:20',
            'currency' => 'required|string|max:10',
            'language' => 'required|string|max:10'
        ]);

        try {
            foreach ($validated as $key => $value) {
                Setting::set($key, $value, 'string', $this->getSettingDescription($key), 'system');
            }

            // Update config timezone aplikasi
            config(['app.timezone' => $validated['timezone']]);

            return response()->json([
                'success' => true,
                'message' => 'Pengaturan sistem berhasil diperbarui'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui pengaturan sistem: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update pengaturan notifikasi
     */
    public function updateNotificationSettings(Request $request)
    {
        $validated = $request->validate([
            'notification_low_stock' => 'required|boolean',
            'notification_transaction' => 'required|boolean',
            'notification_due_date' => 'required|boolean',
            'notification_email' => 'required|boolean'
        ]);

        try {
            foreach ($validated as $key => $value) {
                Setting::set($key, $value, 'boolean', $this->getSettingDescription($key), 'notification');
            }

            return response()->json([
                'success' => true,
                'message' => 'Pengaturan notifikasi berhasil diperbarui'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui pengaturan notifikasi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update pengaturan keamanan
     */
    public function updateSecuritySettings(Request $request)
    {
        $validated = $request->validate([
            'session_timeout' => 'required|integer|min:5|max:1440',
            'two_factor_auth' => 'required|boolean',
            'login_attempts' => 'required|boolean'
        ]);

        try {
            foreach ($validated as $key => $value) {
                $type = is_bool($value) ? 'boolean' : 'integer';
                Setting::set($key, $value, $type, $this->getSettingDescription($key), 'security');
            }

            // Update session lifetime
            config(['session.lifetime' => $validated['session_timeout']]);

            return response()->json([
                'success' => true,
                'message' => 'Pengaturan keamanan berhasil diperbarui'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui pengaturan keamanan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buat backup database
     */
    public function createBackup(Request $request)
    {
        try {
            $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            
            // Simulasi backup (dalam implementasi real, gunakan mysqldump atau package backup)
            // Untuk development, kita hanya simpan info backup
            
            // Update informasi backup terakhir
            Setting::set('last_backup_date', now(), 'datetime', 'Tanggal backup terakhir', 'backup');
            Setting::set('last_backup_file', $filename, 'string', 'File backup terakhir', 'backup');

            return response()->json([
                'success' => true,
                'message' => 'Backup berhasil dibuat',
                'data' => [
                    'filename' => $filename,
                    'date' => now()->format('d/m/Y H:i:s')
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat backup: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset semua pengaturan ke default
     */
    public function resetSettings(Request $request)
    {
        try {
            DB::beginTransaction();

            // Hapus semua pengaturan custom, kecuali profil toko
            Setting::whereNotIn('key', [
                'store_name', 'store_address', 'store_phone', 'store_email'
            ])->delete();

            // Set pengaturan default
            $this->setDefaultSettings();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pengaturan berhasil direset ke default'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal reset pengaturan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Set pengaturan default
     */
    private function setDefaultSettings()
    {
        $defaults = [
            'timezone' => ['value' => 'Asia/Jakarta', 'type' => 'string', 'group' => 'system'],
            'date_format' => ['value' => 'd/m/Y', 'type' => 'string', 'group' => 'system'],
            'currency' => ['value' => 'IDR', 'type' => 'string', 'group' => 'system'],
            'language' => ['value' => 'id', 'type' => 'string', 'group' => 'system'],
            'notification_low_stock' => ['value' => true, 'type' => 'boolean', 'group' => 'notification'],
            'notification_transaction' => ['value' => true, 'type' => 'boolean', 'group' => 'notification'],
            'notification_due_date' => ['value' => false, 'type' => 'boolean', 'group' => 'notification'],
            'notification_email' => ['value' => true, 'type' => 'boolean', 'group' => 'notification'],
            'session_timeout' => ['value' => 120, 'type' => 'integer', 'group' => 'security'],
            'two_factor_auth' => ['value' => false, 'type' => 'boolean', 'group' => 'security'],
            'login_attempts' => ['value' => true, 'type' => 'boolean', 'group' => 'security']
        ];

        foreach ($defaults as $key => $config) {
            Setting::set(
                $key, 
                $config['value'], 
                $config['type'], 
                $this->getSettingDescription($key),
                $config['group']
            );
        }
    }

    /**
     * Dapatkan deskripsi untuk setiap setting key
     */
    private function getSettingDescription($key)
    {
        $descriptions = [
            'store_name' => 'Nama toko/perusahaan',
            'store_address' => 'Alamat toko/perusahaan',
            'store_phone' => 'Telepon toko/perusahaan',
            'store_email' => 'Email toko/perusahaan',
            'timezone' => 'Zona waktu sistem',
            'date_format' => 'Format tanggal',
            'currency' => 'Mata uang',
            'language' => 'Bahasa sistem',
            'notification_low_stock' => 'Notifikasi stok menipis',
            'notification_transaction' => 'Notifikasi transaksi',
            'notification_due_date' => 'Notifikasi jatuh tempo',
            'notification_email' => 'Notifikasi email',
            'session_timeout' => 'Timeout sesi (menit)',
            'two_factor_auth' => 'Autentikasi dua faktor',
            'login_attempts' => 'Pembatasan percobaan login',
            'last_backup_date' => 'Tanggal backup terakhir',
            'last_backup_file' => 'File backup terakhir'
        ];

        return $descriptions[$key] ?? '';
    }

    /**
     * Dapatkan daftar timezone
     */
    private function getTimezones()
    {
        return [
            'Asia/Jakarta' => 'WIB (UTC+7)',
            'Asia/Makassar' => 'WITA (UTC+8)',
            'Asia/Jayapura' => 'WIT (UTC+9)',
            'UTC' => 'UTC (UTC+0)'
        ];
    }

    /**
     * Dapatkan daftar mata uang
     */
    private function getCurrencies()
    {
        return [
            'IDR' => 'Rupiah (IDR)',
            'USD' => 'US Dollar (USD)',
            'EUR' => 'Euro (EUR)'
        ];
    }

    /**
     * Dapatkan daftar bahasa
     */
    private function getLanguages()
    {
        return [
            'id' => 'Bahasa Indonesia',
            'en' => 'English'
        ];
    }

    /**
     * Dapatkan informasi backup
     */
    private function getBackupInfo()
    {
        $lastBackup = Setting::where('key', 'last_backup_date')->first();
        $lastBackupFile = Setting::where('key', 'last_backup_file')->first();
        
        return [
            'last_backup' => $lastBackup ? $lastBackup->value : null,
            'last_backup_file' => $lastBackupFile ? $lastBackupFile->value : null,
            'database_size' => $this->getDatabaseSize()
        ];
    }

    /**
     * Dapatkan ukuran database
     */
    private function getDatabaseSize()
    {
        try {
            $result = DB::select("
                SELECT 
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS size_mb
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()
            ");

            $size = $result[0]->size_mb ?? 0;
            return $size . ' MB';
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }
}