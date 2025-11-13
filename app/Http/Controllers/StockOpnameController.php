<?php

namespace App\Http\Controllers;

use App\Models\StockOpname;
use App\Models\Product;
use App\Models\Category;
use App\Models\StockTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class StockOpnameController extends Controller
{
    /**
     * Display a listing of stock opnames
     */
    public function index(Request $request)
    {
        $query = StockOpname::with(['product.category', 'user']);

        // Filter berdasarkan tanggal
        if ($request->filled('start_date')) {
            $query->whereDate('opname_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('opname_date', '<=', $request->end_date);
        }

        // Filter berdasarkan kategori
        if ($request->filled('category_id')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        // Filter berdasarkan status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter berdasarkan produk
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Cari berdasarkan nomor opname atau nama produk
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('opname_number', 'like', "%{$search}%")
                  ->orWhereHas('product', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%")
                         ->orWhere('code', 'like', "%{$search}%");
                  });
            });
        }

        $opnames = $query->orderBy('opname_date', 'desc')
                        ->orderBy('created_at', 'desc')
                        ->paginate(15);

        // Statistik
        $stats = [
            'total_opnames' => StockOpname::count(),
            'pending' => StockOpname::where('status', 'pending')->count(),
            'approved' => StockOpname::where('status', 'approved')->count(),
            'total_difference' => StockOpname::where('status', 'approved')
                                            ->sum(DB::raw('ABS(difference)')),
        ];

        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();

        return view('stock-opnames.index', compact('opnames', 'stats', 'categories', 'products'));
    }

    /**
     * Show the form for creating a new stock opname
     */
    public function create()
    {
        $products = Product::with('category')
                          ->where('is_active', true)
                          ->orderBy('name')
                          ->get();
        
        $categories = Category::where('is_active', true)->orderBy('name')->get();

        return view('stock-opnames.create', compact('products', 'categories'));
    }

    /**
     * Store a newly created stock opname
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'opname_date' => 'required|date',
            'physical_stock' => 'required|integer|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();

        try {
            $product = Product::findOrFail($validated['product_id']);

            $opname = StockOpname::create([
                'product_id' => $validated['product_id'],
                'opname_date' => $validated['opname_date'],
                'system_stock' => $product->current_stock,
                'physical_stock' => $validated['physical_stock'],
                'status' => 'pending',
                'notes' => $validated['notes'],
                'user_id' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock opname berhasil ditambahkan',
                'data' => $opname->load('product', 'user')
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan stock opname: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified stock opname
     */
    public function show(StockOpname $stockOpname)
    {
        // Load relasi yang diperlukan
        $stockOpname->load(['product.category', 'user']);

        // Jika request adalah AJAX atau meminta JSON, return JSON
        if (request()->wantsJson() || request()->ajax() || request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $stockOpname->id,
                    'opname_number' => $stockOpname->opname_number,
                    'opname_date' => $stockOpname->opname_date->format('Y-m-d'),
                    'system_stock' => $stockOpname->system_stock,
                    'physical_stock' => $stockOpname->physical_stock,
                    'difference' => $stockOpname->difference,
                    'status' => $stockOpname->status,
                    'notes' => $stockOpname->notes,
                    'created_at' => $stockOpname->created_at->toISOString(),
                    'updated_at' => $stockOpname->updated_at->toISOString(),
                    'product' => [
                        'id' => $stockOpname->product->id,
                        'code' => $stockOpname->product->code,
                        'name' => $stockOpname->product->name,
                        'unit' => $stockOpname->product->unit,
                        'category' => [
                            'id' => $stockOpname->product->category->id,
                            'name' => $stockOpname->product->category->name,
                        ]
                    ],
                    'user' => [
                        'id' => $stockOpname->user->id,
                        'name' => $stockOpname->user->name,
                    ]
                ]
            ]);
        }

        // Jika bukan AJAX, return view (untuk halaman detail tersendiri jika diperlukan)
        return view('stock-opnames.show', compact('stockOpname'));
    }

    /**
     * Show the form for editing the specified stock opname
     */
    public function edit(StockOpname $stockOpname)
    {
        if ($stockOpname->status === 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Stock opname yang sudah disetujui tidak dapat diedit'
            ], 422);
        }

        $products = Product::where('is_active', true)->orderBy('name')->get();
        
        return response()->json([
            'success' => true,
            'data' => $stockOpname->load('product'),
            'products' => $products
        ]);
    }

    /**
     * Update the specified stock opname
     */
    public function update(Request $request, StockOpname $stockOpname)
    {
        if ($stockOpname->status === 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Stock opname yang sudah disetujui tidak dapat diedit'
            ], 422);
        }

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'opname_date' => 'required|date',
            'physical_stock' => 'required|integer|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();

        try {
            $product = Product::findOrFail($validated['product_id']);

            $stockOpname->update([
                'product_id' => $validated['product_id'],
                'opname_date' => $validated['opname_date'],
                'system_stock' => $product->current_stock,
                'physical_stock' => $validated['physical_stock'],
                'notes' => $validated['notes'],
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock opname berhasil diperbarui',
                'data' => $stockOpname->load('product', 'user')
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui stock opname: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified stock opname
     */
    public function destroy(StockOpname $stockOpname)
    {
        if ($stockOpname->status === 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Stock opname yang sudah disetujui tidak dapat dihapus'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $stockOpname->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock opname berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus stock opname: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve stock opname and adjust stock
     */
    public function approve(StockOpname $stockOpname)
    {
        if ($stockOpname->status === 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Stock opname sudah disetujui sebelumnya'
            ], 422);
        }

        DB::beginTransaction();

        try {
            $product = $stockOpname->product;
            $difference = $stockOpname->difference;

            // Update product stock
            $product->update([
                'current_stock' => $stockOpname->physical_stock
            ]);

            // Create stock transaction
            if ($difference != 0) {
                StockTransaction::create([
                    'transaction_code' => $this->generateTransactionCode(),
                    'product_id' => $product->id,
                    'type' => 'adjustment',
                    'quantity' => abs($difference),
                    'stock_before' => $stockOpname->system_stock,
                    'stock_after' => $stockOpname->physical_stock,
                    'price' => $product->purchase_price,
                    'reference_type' => StockOpname::class,
                    'reference_id' => $stockOpname->id,
                    'notes' => "Stock opname adjustment - {$stockOpname->opname_number}",
                    'user_id' => Auth::id(),
                ]);
            }

            // Update opname status
            $stockOpname->update([
                'status' => 'approved'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock opname berhasil disetujui dan stok telah disesuaikan',
                'data' => $stockOpname->load('product', 'user')
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyetujui stock opname: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate transaction code
     */
    private function generateTransactionCode()
    {
        $prefix = 'STK';
        $date = date('Ymd');
        $sequence = StockTransaction::whereDate('created_at', today())->count() + 1;
        
        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get product current stock
     */
    public function getProductStock(Product $product)
    {
        return response()->json([
            'success' => true,
            'data' => [
                'product' => $product->load('category'),
                'current_stock' => $product->current_stock,
                'unit' => $product->unit
            ]
        ]);
    }
}