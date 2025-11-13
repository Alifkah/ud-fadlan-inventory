<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class SupplierController extends Controller
{
    /**
     * Tampilkan daftar supplier
     */
    public function index(Request $request)
    {
        $query = Supplier::query();

        // Filter pencarian
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Sorting
        $sortField = $request->get('sort_field', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $suppliers = $query->paginate(15);

        // Statistik
        $stats = [
            'total_suppliers' => Supplier::count(),
            'active_suppliers' => Supplier::where('is_active', true)->count(),
            'inactive_suppliers' => Supplier::where('is_active', false)->count(),
            'recent_suppliers' => Supplier::where('created_at', '>=', Carbon::now()->subDays(30))->count()
        ];

        return view('suppliers.index', compact('suppliers', 'stats'));
    }

    /**
     * Simpan supplier baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:suppliers,code',
            'company_name' => 'nullable|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255|unique:suppliers,email',
            'address' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean'
        ]);

        try {
            $supplier = Supplier::create([
                'code' => $validated['code'] ?? null,
                'name' => $validated['name'],
                'company_name' => $validated['company_name'] ?? null,
                'phone' => $validated['phone'],
                'email' => $validated['email'] ?? null,
                'address' => $validated['address'] ?? null,
                'is_active' => $validated['is_active'] ?? true
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Supplier berhasil ditambahkan',
                    'data' => $supplier
                ]);
            }

            return redirect()->route('suppliers.index')
                           ->with('success', 'Supplier berhasil ditambahkan');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menambahkan supplier: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                           ->withInput()
                           ->with('error', 'Gagal menambahkan supplier: ' . $e->getMessage());
        }
    }

    /**
     * Tampilkan detail supplier
     */
    public function show(Request $request, Supplier $supplier)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $supplier
            ]);
        }

        // Load relasi dengan pembelian
        $supplier->load(['purchases' => function ($query) {
            $query->with('user')->orderBy('created_at', 'desc')->limit(10);
        }]);

        // Statistik supplier
        $stats = [
            'total_purchases' => $supplier->purchases()->where('status', 'completed')->count(),
            'total_purchase_value' => $supplier->purchases()->where('status', 'completed')->sum('total'),
            'pending_purchases' => $supplier->purchases()->where('status', 'pending')->count(),
            'last_purchase_date' => $supplier->purchases()->latest('purchase_date')->value('purchase_date'),
            'average_purchase_value' => $supplier->purchases()->where('status', 'completed')->avg('total') ?? 0
        ];

        // Riwayat pembelian lengkap dengan pagination
        $purchases = $supplier->purchases()
            ->with(['user', 'purchaseItems.product'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('suppliers.show', compact('supplier', 'stats', 'purchases'));
    }

    /**
     * Tampilkan formulir edit supplier
     */
    public function edit(Supplier $supplier)
    {
        return response()->json([
            'success' => true,
            'data' => $supplier
        ]);
    }

    /**
     * Update supplier
     */
    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:50', Rule::unique('suppliers')->ignore($supplier->id)],
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'email' => ['nullable', 'email', 'max:255', Rule::unique('suppliers')->ignore($supplier->id)],
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean'
        ]);

        // Handle checkbox for is_active
        $validated['is_active'] = $request->has('is_active') ? ($validated['is_active'] ?? true) : false;

        try {
            $supplier->update($validated);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Supplier berhasil diperbarui',
                    'data' => $supplier
                ]);
            }

            return redirect()->route('suppliers.index')
                           ->with('success', 'Supplier berhasil diperbarui');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal memperbarui supplier: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                           ->withInput()
                           ->with('error', 'Gagal memperbarui supplier: ' . $e->getMessage());
        }
    }

    /**
     * Hapus supplier
     */
    public function destroy(Request $request, Supplier $supplier)
    {
        try {
            // Cek apakah supplier memiliki transaksi pembelian
            if ($supplier->purchases()->exists()) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Supplier tidak dapat dihapus karena memiliki riwayat transaksi pembelian'
                    ], 422);
                }

                return redirect()->route('suppliers.index')
                               ->with('error', 'Supplier tidak dapat dihapus karena memiliki riwayat transaksi pembelian');
            }

            $supplier->delete();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Supplier berhasil dihapus'
                ]);
            }

            return redirect()->route('suppliers.index')
                           ->with('success', 'Supplier berhasil dihapus');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menghapus supplier: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->route('suppliers.index')
                           ->with('error', 'Gagal menghapus supplier: ' . $e->getMessage());
        }
    }

    /**
     * Aktifkan/nonaktifkan supplier
     */
    public function toggleStatus(Supplier $supplier)
    {
        try {
            $supplier->update(['is_active' => !$supplier->is_active]);

            $status = $supplier->is_active ? 'diaktifkan' : 'dinonaktifkan';

            return response()->json([
                'success' => true,
                'message' => "Supplier berhasil {$status}",
                'data' => [
                    'id' => $supplier->id,
                    'is_active' => $supplier->is_active
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status supplier: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk delete suppliers
     */
    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'supplier_ids' => 'required|array',
            'supplier_ids.*' => 'exists:suppliers,id',
        ]);

        try {
            $suppliers = Supplier::whereIn('id', $validated['supplier_ids'])->get();
            $deletedCount = 0;
            $skippedCount = 0;

            foreach ($suppliers as $supplier) {
                // Cek apakah supplier memiliki transaksi
                if ($supplier->purchases()->exists()) {
                    $skippedCount++;
                    continue;
                }
                
                $supplier->delete();
                $deletedCount++;
            }

            $message = "Berhasil menghapus {$deletedCount} supplier";
            if ($skippedCount > 0) {
                $message .= ", {$skippedCount} supplier dilewati karena memiliki riwayat transaksi";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'deleted_count' => $deletedCount,
                    'skipped_count' => $skippedCount
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus supplier: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search suppliers untuk AJAX
     */
    public function search(Request $request)
    {
        $search = $request->get('search', '');
        
        $suppliers = Supplier::where('is_active', true)
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%")
                      ->orWhere('company_name', 'like', "%{$search}%");
            })
            ->limit(10)
            ->get()
            ->map(function ($supplier) {
                return [
                    'id' => $supplier->id,
                    'code' => $supplier->code,
                    'name' => $supplier->name,
                    'company_name' => $supplier->company_name,
                    'email' => $supplier->email,
                    'phone' => $supplier->phone,
                    'address' => $supplier->address
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $suppliers
        ]);
    }

    /**
     * Get supplier info untuk dropdown/select
     */
    public function getSupplierInfo(Supplier $supplier)
    {
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $supplier->id,
                'code' => $supplier->code,
                'name' => $supplier->name,
                'company_name' => $supplier->company_name,
                'email' => $supplier->email,
                'phone' => $supplier->phone,
                'address' => $supplier->address,
                'is_active' => $supplier->is_active
            ]
        ]);
    }

    /**
     * Get suppliers untuk dropdown
     */
    public function getActiveSuppliers()
    {
        $suppliers = Supplier::where('is_active', true)
            ->select('id', 'code', 'name', 'company_name')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $suppliers
        ]);
    }

    /**
     * Generate laporan supplier
     */
    public function getSupplierReport(Request $request)
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'supplier_id' => 'nullable|exists:suppliers,id'
        ]);

        $query = Supplier::with(['purchases' => function ($purchaseQuery) use ($validated) {
            $purchaseQuery->where('status', 'completed');
            
            if (isset($validated['date_from'])) {
                $purchaseQuery->whereDate('purchase_date', '>=', $validated['date_from']);
            }
            
            if (isset($validated['date_to'])) {
                $purchaseQuery->whereDate('purchase_date', '<=', $validated['date_to']);
            }
        }]);

        if (isset($validated['supplier_id'])) {
            $query->where('id', $validated['supplier_id']);
        }

        $suppliers = $query->get()->map(function ($supplier) {
            return [
                'id' => $supplier->id,
                'code' => $supplier->code,
                'name' => $supplier->name,
                'company_name' => $supplier->company_name,
                'total_purchases' => $supplier->purchases->count(),
                'total_purchase_value' => $supplier->purchases->sum('total'),
                'average_purchase_value' => $supplier->purchases->avg('total') ?? 0,
                'last_purchase_date' => $supplier->purchases->max('purchase_date')
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $suppliers
        ]);
    }

    /**
     * Export data supplier
     */
    public function export(Request $request)
    {
        $query = Supplier::query();

        // Apply filters similar to index method
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%");
            });
        }

        $suppliers = $query->orderBy('name')->get();

        $data = $suppliers->map(function ($supplier) {
            // Hitung statistik untuk setiap supplier
            $totalPurchases = $supplier->purchases()->where('status', 'completed')->count();
            $totalValue = $supplier->purchases()->where('status', 'completed')->sum('total');
            $lastPurchase = $supplier->purchases()->latest('purchase_date')->value('purchase_date');

            return [
                'Kode Supplier' => $supplier->code,
                'Nama Supplier' => $supplier->name,
                'Nama Perusahaan' => $supplier->company_name,
                'Email' => $supplier->email,
                'Telepon' => $supplier->phone,
                'Alamat' => $supplier->address,
                'Total Pembelian' => $totalPurchases,
                'Nilai Total Pembelian' => 'Rp ' . number_format($totalValue, 0, ',', '.'),
                'Pembelian Terakhir' => $lastPurchase ? Carbon::parse($lastPurchase)->format('d/m/Y') : '-',
                'Status' => $supplier->is_active ? 'Aktif' : 'Tidak Aktif',
                'Tanggal Kerja Sama' => $supplier->created_at->format('d/m/Y'),
                'Terakhir Diperbarui' => $supplier->updated_at->format('d/m/Y H:i')
            ];
        });

        // Return data untuk diproses oleh library export (seperti Maatwebsite/Excel)
        return response()->json([
            'success' => true,
            'data' => $data,
            'filename' => 'data_supplier_' . date('Y-m-d_H-i-s') . '.xlsx'
        ]);
    }
}