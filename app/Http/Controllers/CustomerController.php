<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\CustomerCredit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class CustomerController extends Controller
{
    /**
     * Tampilkan daftar customer dengan statistik
     */
    public function index(Request $request)
    {
        $query = Customer::withCount([
            'sales',
            'sales as completed_sales_count' => function ($query) {
                $query->where('status', 'completed');
            }
        ])->with(['sales' => function ($query) {
            $query->where('status', 'completed')
                  ->select('customer_id', 'total', 'created_at');
        }]);

        // Filter pencarian
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter status
        if ($request->filled('status')) {
            switch ($request->status) {
                case 'active':
                    $query->where('is_active', true);
                    break;
                case 'inactive':
                    $query->where('is_active', false);
                    break;
            }
        }

        // Sorting
        $sortField = $request->get('sort_field', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $customers = $query->paginate(15);

        // Transform data dengan statistik tambahan
        $customers->getCollection()->transform(function ($customer) {
            $totalPurchases = $customer->sales->sum('total');
            $lastPurchase = $customer->sales->sortByDesc('created_at')->first();
            
            $customer->total_purchases = $totalPurchases;
            $customer->last_purchase_date = $lastPurchase ? $lastPurchase->created_at : null;
            $customer->purchase_frequency = $this->calculatePurchaseFrequency($customer);
            
            return $customer;
        });

        // Statistik keseluruhan
        $stats = [
            'total_customers' => Customer::count(),
            'active_customers' => Customer::where('is_active', true)->count(),
            'inactive_customers' => Customer::where('is_active', false)->count(),
            'customers_with_credit' => CustomerCredit::where('status', 'active')
                                                  ->where('remaining_amount', '>', 0)
                                                  ->distinct('customer_id')
                                                  ->count('customer_id')
        ];

        return view('customers.index', compact('customers', 'stats'));
    }

    /**
     * Tampilkan formulir untuk membuat customer baru
     */
    public function create()
    {
        // Untuk modal-based, tidak perlu view terpisah
        // Bisa return JSON untuk AJAX request
        if (request()->wantsJson()) {
            $customerCode = $this->generateCustomerCode();
            return response()->json([
                'success' => true,
                'data' => [
                    'code' => $customerCode
                ]
            ]);
        }

        // Atau redirect ke index jika diakses langsung
        return redirect()->route('customers.index');
    }

    /**
     * Simpan customer yang baru dibuat
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'nullable|string|max:50|unique:customers,code',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:customers,email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean'
        ]);

        // Auto-generate code jika tidak ada
        if (empty($validated['code'])) {
            $validated['code'] = $this->generateCustomerCode();
        }

        // Set default is_active jika tidak ada
        $validated['is_active'] = $request->input('is_active', 1);

        try {
            $customer = Customer::create($validated);

            // Always return JSON for modal-based system
            return response()->json([
                'success' => true,
                'message' => 'Customer berhasil ditambahkan',
                'data' => $customer
            ]);
                
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan customer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tampilkan detail customer beserta riwayat transaksi
     */
    public function show(Request $request, Customer $customer)
    {
        // Untuk modal-based system, selalu return JSON
        // Load relasi jika diperlukan untuk view detail lengkap
        $customer->load([
            'sales' => function ($query) {
                $query->where('status', 'completed')
                      ->orderBy('created_at', 'desc')
                      ->limit(10);
            },
            'customerCredits' => function ($query) {
                $query->where('status', 'active')
                      ->orderBy('created_at', 'desc');
            }
        ]);

        // Statistik customer
        $customerStats = [
            'total_transactions' => $customer->sales()->where('status', 'completed')->count(),
            'total_purchases' => $customer->sales()->where('status', 'completed')->sum('total'),
            'average_purchase' => $customer->sales()->where('status', 'completed')->avg('total') ?? 0,
            'last_purchase' => $customer->sales()->where('status', 'completed')
                                             ->latest('created_at')
                                             ->first(),
            'total_credit' => $customer->customerCredits()
                                      ->where('status', 'active')
                                      ->sum('remaining_amount'),
            'overdue_credit' => $customer->customerCredits()
                                        ->where('status', 'active')
                                        ->where('due_date', '<', now())
                                        ->sum('remaining_amount')
        ];

        // Always return JSON for modal-based system
        return response()->json([
            'success' => true,
            'data' => [
                'customer' => $customer,
                'stats' => $customerStats
            ]
        ]);
    }

    /**
     * Tampilkan formulir untuk mengedit customer
     */
    public function edit(Customer $customer)
    {
        // Always return JSON for modal-based system
        return response()->json([
            'success' => true,
            'data' => $customer
        ]);
    }

    /**
     * Perbarui data customer
     */
    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('customers')->ignore($customer->id)],
            'name' => 'required|string|max:255',
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('customers')->ignore($customer->id)
            ],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean'
        ]);

        // Set default is_active jika tidak ada
        $validated['is_active'] = $request->input('is_active', 0);

        try {
            $customer->update($validated);

            // Always return JSON for modal-based system
            return response()->json([
                'success' => true,
                'message' => 'Customer berhasil diperbarui',
                'data' => $customer
            ]);
                
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui customer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hapus customer dari database
     */
    public function destroy(Customer $customer)
    {
        try {
            // Periksa apakah customer memiliki transaksi
            if ($customer->sales()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer tidak dapat dihapus karena memiliki riwayat transaksi'
                ], 422);
            }

            // Periksa apakah customer memiliki kredit aktif
            if ($customer->customerCredits()->where('status', 'active')->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer tidak dapat dihapus karena memiliki kredit aktif'
                ], 422);
            }

            $customer->delete();

            // Always return JSON for modal-based system
            return response()->json([
                'success' => true,
                'message' => 'Customer berhasil dihapus'
            ]);
                
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus customer: ' . $e->getMessage()
            ], 500);
        }
    }
    

    /**
     * Aktifkan/nonaktifkan status customer
     */
    public function toggleStatus(Customer $customer)
    {
        try {
            $customer->update(['is_active' => !$customer->is_active]);

            $status = $customer->is_active ? 'diaktifkan' : 'dinonaktifkan';

            return response()->json([
                'success' => true,
                'message' => "Customer berhasil {$status}",
                'data' => $customer
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status customer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk delete customers
     */
    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'customer_ids' => 'required|array',
            'customer_ids.*' => 'exists:customers,id',
        ]);

        try {
            $customers = Customer::whereIn('id', $validated['customer_ids'])->get();
            $deletedCount = 0;

            foreach ($customers as $customer) {
                // Skip jika customer memiliki transaksi atau kredit aktif
                if ($customer->sales()->exists() || 
                    $customer->customerCredits()->where('status', 'active')->exists()) {
                    continue;
                }
                
                $customer->delete();
                $deletedCount++;
            }

            return response()->json([
                'success' => true,
                'message' => "{$deletedCount} customer berhasil dihapus"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus customers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cari customer untuk autocomplete
     */
   public function search(Request $request)
    {
        $search = $request->get('search', '');
        
        $customers = Customer::where('is_active', true)
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->limit(10)
            ->get(['id', 'code', 'name', 'phone', 'email', 'address'])
            ->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'code' => $customer->code,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'email' => $customer->email,
                    'address' => $customer->address
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $customers
        ]);
    }

    /**
     * Dapatkan informasi customer beserta kredit
     */
    public function getCustomerInfo(Customer $customer)
    {
        $customer->load(['customerCredits' => function ($query) {
            $query->where('status', 'active')
                  ->orderBy('due_date');
        }]);

        $totalCredit = $customer->customerCredits->sum('remaining_amount');
        $overdueCredit = $customer->customerCredits
            ->where('due_date', '<', now())
            ->sum('remaining_amount');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $customer->id,
                'code' => $customer->code,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'address' => $customer->address,
                'is_active' => $customer->is_active,
                'total_credit' => $totalCredit,
                'overdue_credit' => $overdueCredit,
                'credit_limit_exceeded' => $totalCredit > 10000000, // Contoh limit 10 juta
                'credits' => $customer->customerCredits
            ]
        ]);
    }

    /**
     * Ekspor data customer
     */
    public function export(Request $request)
    {
        $query = Customer::with(['sales' => function ($salesQuery) {
            $salesQuery->where('status', 'completed');
        }]);

        if ($request->filled('status')) {
            switch ($request->status) {
                case 'active':
                    $query->where('is_active', true);
                    break;
                case 'inactive':
                    $query->where('is_active', false);
                    break;
            }
        }

        $customers = $query->orderBy('name')->get();

        $data = $customers->map(function ($customer) {
            $totalPurchases = $customer->sales->sum('total');
            $totalTransactions = $customer->sales->count();
            $lastPurchase = $customer->sales->sortByDesc('created_at')->first();

            return [
                'Kode Customer' => $customer->code,
                'Nama Customer' => $customer->name,
                'Email' => $customer->email,
                'Telepon' => $customer->phone,
                'Alamat' => $customer->address,
                'Status' => $customer->is_active ? 'Aktif' : 'Tidak Aktif',
                'Total Transaksi' => $totalTransactions,
                'Total Pembelian' => $totalPurchases,
                'Rata-rata Pembelian' => $totalTransactions > 0 ? $totalPurchases / $totalTransactions : 0,
                'Pembelian Terakhir' => $lastPurchase ? $lastPurchase->created_at->format('d/m/Y') : '-',
                'Tanggal Daftar' => $customer->created_at->format('d/m/Y H:i')
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'filename' => 'data_customer_' . date('Y-m-d_H-i-s') . '.xlsx'
        ]);
    }

    /**
     * Generate kode customer otomatis
     */
    private function generateCustomerCode()
    {
        $prefix = 'CST';
        $lastCustomer = Customer::latest('id')->first();
        $nextId = $lastCustomer ? $lastCustomer->id + 1 : 1;
        
        return $prefix . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Hitung frekuensi pembelian customer
     */
    private function calculatePurchaseFrequency($customer)
    {
        if ($customer->sales->isEmpty()) {
            return 'Belum ada transaksi';
        }

        $firstPurchase = $customer->sales->sortBy('created_at')->first();
        $daysSinceFirst = $firstPurchase->created_at->diffInDays(now());
        
        if ($daysSinceFirst == 0) {
            return 'Baru bergabung';
        }

        $frequency = $customer->sales->count() / ($daysSinceFirst / 30); // per bulan
        
        if ($frequency >= 4) {
            return 'Sangat Sering';
        } elseif ($frequency >= 2) {
            return 'Sering';
        } elseif ($frequency >= 1) {
            return 'Normal';
        } else {
            return 'Jarang';
        }
    }

    /**
     * Dapatkan laporan customer terbaik
     */
    public function getTopCustomers(Request $request)
    {
        $period = $request->get('period', '3months'); // 1month, 3months, 6months, 1year
        
        $dateFrom = match($period) {
            '1month' => Carbon::now()->subMonth(),
            '3months' => Carbon::now()->subMonths(3),
            '6months' => Carbon::now()->subMonths(6),
            '1year' => Carbon::now()->subYear(),
            default => Carbon::now()->subMonths(3)
        };

        $topCustomers = Customer::with(['sales' => function ($query) use ($dateFrom) {
                $query->where('status', 'completed')
                      ->where('sale_date', '>=', $dateFrom);
            }])
            ->whereHas('sales', function ($query) use ($dateFrom) {
                $query->where('status', 'completed')
                      ->where('sale_date', '>=', $dateFrom);
            })
            ->get()
            ->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'code' => $customer->code,
                    'total_purchases' => $customer->sales->sum('total'),
                    'total_transactions' => $customer->sales->count(),
                    'average_purchase' => $customer->sales->avg('total')
                ];
            })
            ->sortByDesc('total_purchases')
            ->take(10);

        return response()->json([
            'success' => true,
            'data' => $topCustomers->values()
        ]);
    }
}