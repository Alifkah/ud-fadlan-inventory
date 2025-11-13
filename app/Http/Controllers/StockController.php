<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\StockTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class StockController extends Controller
{
    /**
     * Tampilkan daftar produk beserta informasi stoknya
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'stockTransactions' => function ($q) {
            $q->latest()->limit(1);
        }]);

        // Filter berdasarkan kategori
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter berdasarkan status stok
        if ($request->filled('stock_status')) {
            switch ($request->stock_status) {
                case 'habis':
                    $query->where('current_stock', 0);
                    break;
                case 'kritis':
                    $query->whereColumn('current_stock', '<=', 'minimum_stock')
                          ->where('current_stock', '>', 0);
                    break;
                case 'menipis':
                    $query->whereRaw('current_stock <= (minimum_stock * 2)')
                          ->whereColumn('current_stock', '>', 'minimum_stock');
                    break;
                case 'normal':
                    $query->whereRaw('current_stock > (minimum_stock * 2)');
                    break;
            }
        }

        // Cari berdasarkan nama produk atau kode
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Filter hanya produk yang aktif
        $query->where('is_active', true);

        $products = $query->orderBy('name')->paginate(15);

        // Transformasikan produk dengan informasi stok tambahan
        $products->getCollection()->transform(function ($product) {
            $product->stock_status = $this->getStockStatus($product);
            $product->stock_value = $product->current_stock * $product->purchase_price;
            $product->last_transaction = $product->stockTransactions->first();
            return $product;
        });

        $categories = Category::where('is_active', true)->orderBy('name')->get();

        // Statistik stok
        $stockStats = [
            'total_products' => Product::where('is_active', true)->count(),
            'out_of_stock' => Product::where('current_stock', 0)->where('is_active', true)->count(),
            'low_stock' => Product::whereColumn('current_stock', '<=', 'minimum_stock')
                                 ->where('current_stock', '>', 0)
                                 ->where('is_active', true)->count(),
            'total_stock_value' => Product::where('is_active', true)
                ->get()
                ->sum(function ($p) {
                    return $p->current_stock * $p->purchase_price;
                })
        ];

        return view('stock.index', compact('products', 'categories', 'stockStats'));
    }

    /**
     * Simpan produk yang baru dibuat di gudang.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:products,code',
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'unit' => 'required|string|max:20',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'current_stock' => 'required|integer|min:0',
            'minimum_stock' => 'nullable|integer|min:0',
            'location' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:1000',
            'supplier' => 'nullable|string|max:255'
        ]);

        // Set minimum stock default to 10 if not provided
        $validated['minimum_stock'] = $validated['minimum_stock'] ?? 10;

        DB::beginTransaction();
        
        try {
            $product = Product::create($validated);

            // Buat transaksi stok awal jika stok > 0
            if ($validated['current_stock'] > 0) {
                $this->createStockTransaction([
                    'product_id' => $product->id,
                    'type' => 'in',
                    'quantity' => $validated['current_stock'],
                    'stock_before' => 0,
                    'stock_after' => $validated['current_stock'],
                    'price' => $validated['purchase_price'],
                    'reference_type' => 'initial_stock',
                    'notes' => 'Stok awal produk - ' . ($validated['supplier'] ?? 'Manual Entry')
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Produk berhasil ditambahkan',
                'data' => $product->load('category')
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan produk: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tampilkan produk yang ditentukan beserta riwayat persediaan.
     */
    public function show(Product $product)
    {
        $product->load(['category', 'stockTransactions' => function ($query) {
            $query->with('user')->orderBy('created_at', 'desc')->limit(20);
        }]);

        $stockStats = [
            'current_value' => $product->current_stock * $product->purchase_price,
            'selling_value' => $product->current_stock * $product->selling_price,
            'potential_profit' => $product->current_stock * ($product->selling_price - $product->purchase_price),
            'stock_status' => $this->getStockStatus($product)
        ];

        // Pergerakan stok dalam 30 hari terakhir
        $stockMovements = $product->stockTransactions()
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, 
                        SUM(CASE WHEN type = "in" THEN quantity ELSE 0 END) as stock_in, 
                        SUM(CASE WHEN type = "out" THEN quantity ELSE 0 END) as stock_out')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('stock.show', compact('product', 'stockStats', 'stockMovements'));
    }


    /**
     * Tampilkan formulir untuk mengedit produk yang ditentukan
     */
    public function edit(Product $product)
    {
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        
        return response()->json([
            'success' => true,
            'data' => $product->load('category'),
            'categories' => $categories,
            'suppliers' => $suppliers
        ]);
    }

    /**
     * Perbarui produk yang ditentukan di gudang
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:products,code,' . $product->id,
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'unit' => 'required|string|max:20',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'current_stock' => 'required|integer|min:0',
            'minimum_stock' => 'required|integer|min:0',
            'location' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:1000'
        ]);

        DB::beginTransaction();

        try {
            $oldStock = $product->current_stock;
            $newStock = $validated['current_stock'];
            
            // Update product
            $product->update($validated);

            // Jika ada perubahan stok, buat transaksi stok
            if ($oldStock != $newStock) {
                $stockDifference = $newStock - $oldStock;
                
                $this->createStockTransaction([
                    'product_id' => $product->id,
                    'type' => 'adjustment',
                    'quantity' => abs($stockDifference),
                    'stock_before' => $oldStock,
                    'stock_after' => $newStock,
                    'price' => $validated['purchase_price'],
                    'reference_type' => 'manual_adjustment',
                    'notes' => 'Manual stock adjustment via edit form'
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Produk berhasil diperbarui',
                'data' => $product->load('category')
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui produk: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Keluarkan produk yang ditentukan dari penyimpanan.
     */
    public function destroy(Product $product)
    {
        try {
            // Periksa apakah produk memiliki transaksi penjualan atau pembelian
            if ($product->saleItems()->exists() || $product->purchaseItems()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produk tidak dapat dihapus karena memiliki riwayat transaksi'
                ], 422);
            }

            DB::beginTransaction();

            // Hapus semua transaksi stok terkait
            $product->stockTransactions()->delete();
            
            // Hapus produk
            $product->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Produk berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus produk: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sesuaikan stok produk
     */
    public function adjustStock(Request $request, Product $product)
    {
        $validated = $request->validate([
            'adjustment_type' => 'required|in:add,subtract,set',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:500',
            'price' => 'nullable|numeric|min:0'
        ]);

        DB::beginTransaction();

        try {
            $stockBefore = $product->current_stock;
            $quantity = $validated['quantity'];
            
            switch ($validated['adjustment_type']) {
                case 'add':
                    $stockAfter = $stockBefore + $quantity;
                    $transactionType = 'in';
                    $transactionQuantity = $quantity;
                    break;
                case 'subtract':
                    if ($quantity > $stockBefore) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Jumlah pengurangan tidak boleh melebihi stok saat ini'
                        ], 422);
                    }
                    $stockAfter = $stockBefore - $quantity;
                    $transactionType = 'out';
                    $transactionQuantity = $quantity;
                    break;
                case 'set':
                    $stockAfter = $quantity;
                    $transactionType = 'adjustment';
                    $transactionQuantity = $stockAfter - $stockBefore;
                    break;
            }

            // Perbarui stok produk
            $product->update(['current_stock' => $stockAfter]);

            // Buat transaksi stok
            $this->createStockTransaction([
                'product_id' => $product->id,
                'type' => $transactionType,
                'quantity' => abs($transactionQuantity),
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'price' => $validated['price'] ?? $product->purchase_price,
                'reference_type' => 'manual_adjustment',
                'notes' => $validated['notes'] ?? "Penyesuaian stok manual ({$validated['adjustment_type']})"
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stok berhasil disesuaikan',
                'data' => [
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'adjustment' => $transactionQuantity
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyesuaikan stok: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate auto product code
     */
    public function generateProductCode()
    {
        $lastProduct = Product::latest('id')->first();
        $nextId = $lastProduct ? $lastProduct->id + 1 : 1;
        $productCode = 'BR' . str_pad($nextId, 3, '0', STR_PAD_LEFT);

        return response()->json([
            'success' => true,
            'code' => $productCode
        ]);
    }

    /**
     * Ekspor data stok barang
     */
    public function export(Request $request)
    {
        $query = Product::with('category')->where('is_active', true);

        // Terapkan filter yang serupa dengan metode index
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('stock_status')) {
            // Terapkan filter status stok
            switch ($request->stock_status) {
                case 'habis':
                    $query->where('current_stock', 0);
                    break;
                case 'kritis':
                    $query->whereColumn('current_stock', '<=', 'minimum_stock')
                          ->where('current_stock', '>', 0);
                    break;
                case 'menipis':
                    $query->whereRaw('current_stock <= (minimum_stock * 2)')
                          ->whereColumn('current_stock', '>', 'minimum_stock');
                    break;
                case 'normal':
                    $query->whereRaw('current_stock > (minimum_stock * 2)');
                    break;
            }
        }

        $products = $query->orderBy('name')->get();

        $data = $products->map(function ($product) {
            return [
                'Kode Barang' => $product->code,
                'Nama Barang' => $product->name,
                'Kategori' => $product->category->name,
                'Satuan' => $product->unit,
                'Stok Saat Ini' => $product->current_stock,
                'Stok Minimum' => $product->minimum_stock,
                'Harga Beli' => $product->purchase_price,
                'Harga Jual' => $product->selling_price,
                'Nilai Stok' => $product->current_stock * $product->purchase_price,
                'Lokasi' => $product->location,
                'Status Stok' => $this->getStockStatusText($product),
                'Status Aktif' => $product->is_active ? 'Ya' : 'Tidak'
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'filename' => 'stok_barang_' . date('Y-m-d_H-i-s') . '.xlsx'
        ]);
    }

    /**
     * Buat catatan transaksi stok
     */
    private function createStockTransaction($data)
    {
        $data['transaction_code'] = $this->generateTransactionCode();
        $data['user_id'] = Auth::id();
        
        return StockTransaction::create($data);
    }

    /**
     * Generate kode transaksi unik
     */
    private function generateTransactionCode()
    {
        $prefix = 'STK';
        $date = date('Ymd');
        $sequence = StockTransaction::whereDate('created_at', today())->count() + 1;
        
        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    public function getProductData(Product $product)
    {
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        
        return response()->json([
            'success' => true,
            'data' => $product->load('category'),
            'categories' => $categories
        ]);
    }

    /**
     * Periksa status persediaan berdasarkan persediaan saat ini dan persediaan minimum
     */
    private function getStockStatus($product)
    {
        if ($product->current_stock == 0) {
            return 'habis';
        } elseif ($product->current_stock <= $product->minimum_stock) {
            return 'kritis';
        } elseif ($product->current_stock <= ($product->minimum_stock * 2)) {
            return 'menipis';
        } else {
            return 'normal';
        }
    }

    /**
     * Dapatkan teks status stok untuk ekspor
     */
    private function getStockStatusText($product)
    {
        $status = $this->getStockStatus($product);
        
        $statusText = [
            'habis' => 'Habis',
            'kritis' => 'Kritis',
            'menipis' => 'Menipis',
            'normal' => 'Normal'
        ];

        return $statusText[$status] ?? 'Unknown';
    }
}