<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\StockTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['category']);

        // filter pencarian
        if($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('code', 'like', "%$search%");
            });
        }

        // filter kategori
        if($request->has('category') && $request->category != '') {
            $query->where('category_id', $request->category);
        }

        // filter status
        if($request->has('status')){
            switch($request->status){
                case 'active':
                    $query->where('is_active', true);
                    break;
                case 'inactive':
                    $query->where('is_active', false);
                    break;
                case 'low_stock':
                    $query->where('current_stock', '<=', DB::raw('minimum_stock'));
                    break;
            }
        }

        // sorting
        $sortField = $request->get('sort_field', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $products = $query->paginate(15);
        $categories = Category::where('is_active', true)->get();

        // statistik
        $stats = [
            'total_items' => Product::count(),
            'low_stock' => Product::where('current_stock', '<=', DB::raw('minimum_stock'))->count(),
            'out_of_stock' => Product::where('current_stock', 0)->count(),
            'total_value' => Product::sum(DB::raw('current_stock * purchase_price'))
        ];

        return view('products.index', compact('products', 'categories', 'stats'));
    }

    public function search(Request $request)
{
    $search = $request->get('search', '');
    
    $products = Product::where('is_active', true)
        ->where('current_stock', '>', 0)
        ->where(function ($query) use ($search) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
        })
        ->with('category:id,name')
        ->limit(10)
        ->get(['id', 'code', 'name', 'unit', 'selling_price', 'purchase_price', 'current_stock', 'category_id'])
        ->map(function ($product) {
            return [
                'id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
                'unit' => $product->unit,
                'selling_price' => $product->selling_price,
                'purchase_price' => $product->purchase_price,
                'current_stock' => $product->current_stock,
                'category' => $product->category->name ?? '-'
            ];
        });

    return response()->json([
        'success' => true,
        'data' => $products
    ]);
}

    public function create()
    {
        $categories = Category::where('is_active', true)->get();
        $suppliers = Supplier::where('is_active', true)->get();
        
        // generate kode produk otomatis
        $lastProduct = Product::latest('id')->first();
        $nextId = $lastProduct ? $lastProduct->id + 1 : 1;
        $productCode = 'BR' . str_pad($nextId, 3, '0', STR_PAD_LEFT);

        return view('products.create', compact('categories', 'suppliers', 'productCode'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:products,code',
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'unit' => 'required|string|max:50',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'minimum_stock' => 'required|integer|min:0',
            'current_stock' => 'required|integer|min:0',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'supplier_id' => 'nullable|exists:suppliers,id',
        ]);

        DB::beginTransaction();
        try {
            $product = Product::create($validated);

            // jika ada stok awal, buat transaksi stok 
            if($validated['current_stock'] > 0 ){
                $this->createStockTransaction(
                    $product->id,
                    'in',
                    $validated['current_stock'],
                    0,
                    $validated['current_stock'],
                    $validated['purchase_price'],
                    'Initial stock',
                );
            }

            DB::commit();
            return redirect()->route('products.index')
                ->with('success', 'Product berhasil ditambahkan.');
        } catch(\Exception $e){
            DB::rollBack();
            return back()->withInput()
             ->with('error', 'Gagal menambahkan produk: ' . $e->getMessage());
        }
    }

    public function show(Product $product)
    {
        $product->load(['category', 'stockTransactions.user']);

        // riwayat transaksi stok
        $stockHistory = $product->stockTransactions()
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        return view('products.show', compact('product', 'stockHistory'));
    }

    public function edit(Product $product)
    {
        $categories = Category::where('is_active', true)->get();
        $suppliers = Supplier::where('is_active', true)->get();
        return view('products.edit', compact('product', 'categories', 'suppliers'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', Rule::unique('products')->ignore($product->id)],
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'unit' => 'required|string|max:50',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'minimum_stock' => 'required|integer|min:0',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $product->update($validated);

        return redirect()->route('products.index')
            ->with('success', 'Product berhasil diperbarui.');
    }

    public function destroy(Product $product)
    {
        try{
            // cek apakah produk memiliki transaksi
            if($product->saleItem()->exists() || $product->purchaseItems()->exists()){
                return back()->with('error', 'Produk tidak dapat dihapus karena memiliki riwayat transaksi.');
            }

            $product->delete();
            return redirect()->route('products.index')
                ->with('success', 'Product berhasil dihapus.');
        }catch(\Exception $e){
            return back()->with('error', 'Gagal menghapus produk: ' . $e->getMessage());
        }
    }

    public function adjustStock(Request $request, Product $product)
    {
        $validated = $request->validate([
            'adjustment_type' => 'required|in:add, reduce, set',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $oldStock = $product->current_stock;
            $newStock = 0;
            $adjustmentQty = $validated['quantity'];

            switch($validated['adjustment_type']){
                case 'add':
                    $newStock = $oldStock + $adjustmentQty;
                    break;
                case 'reduce':
                    $newStock = max(0, $oldStock - $adjustmentQty);
                    $adjustmentQty = $oldStock - $newStock; // sesuaikan qty
                    break;
                case 'set':
                    $newStock = $adjustmentQty;
                    $adjustmentQty = $newStock - $oldStock; // qty adalah selisih
                    break;
            }

            // update stok produk
            $product->update(['current_stock' => $newStock]);

            // buat transaksi stok
            $transactionType = $adjustmentQty > 0 ? 'in' : 'out';
            $this->createStockTransaction(
                $product->id,
                'adjustment',
                abs($adjustmentQty),
                $oldStock,
                $newStock,
                $product->purchase_price,
                $validated['notes'] ?? 'Stock adjustment'
            );

            DB::commit();
            return back()->with('success', 'Stok produk berhasil disesuaikan.');
        }catch(\Exception $e){
            DB::rollBack();
            return back()->withInput()
             ->with('error', 'Gagal menyesuaikan stok: ' . $e->getMessage());
        }
    }

    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id',
        ]);

        try{
            $products = Product::whereIn('id', $validated['product_ids'])->get();

            foreach($products as $product){
                if($product->saleItems()->exists() || $product->purchaseItems()->exists()){
                    continue; // lewati produk dengan transaksi
                }
                $product->delete();
            }
            return back()->with('success', 'Produk terpilih berhasil dihapus.');
        }catch(\Exception $e){
            return back()->with('error', 'Gagal menghapus produk: ' . $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        // Implementasi ekspor ke Excel atau CSV sesuai kebutuhan
        // Misalnya menggunakan Laravel Excel package

        $query = Product::with('category');
        if($request->has('category') && $request->category != '') {
            $query->where('category_id', $request->category);
        }

        $products = $query->get();

        // Kembalikan file Excel ekspor
        // Kembalikan file Excel::download(new ProductsExport($products), 'products.xlsx');

        return back()->with('info', 'Fitur ekspor belum diimplementasikan.');
    }

    private function createStockTransaction($productId, $type, $quantity, $stockBefore, $stockAfter, $price = null, $notes = null)
    {
        $transactionCode = 'STK-' . date('Ymd') . '-' . str_pad(StockTransaction::count() + 1, 4, '0', STR_PAD_LEFT);
        StockTransaction::create([
            'transaction_code' => $transactionCode,
            'product_id' => $productId,
            'type' => $type,
            'quantity' => $quantity,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'price' => $price,
            'notes' => $notes,
            'user_id' => Auth::id(),
        ]);
    }

    public function getLowStock()
    {
        $lowStockProducts = Product::whereColumn('current_stock', '<=', DB::raw('minimum_stock'))
            ->where('is_active', true)
            ->with('category')
            ->get();

        return response()->json($lowStockProducts);
    }
    public function getProductInfo(Product $product)
    {
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
                'unit' => $product->unit,
                'selling_price' => $product->selling_price,
                'purchase_price' => $product->purchase_price,
                'current_stock' => $product->current_stock,
                'minimum_stock' => $product->minimum_stock,
                'category' => $product->category->name ?? '-',
                'is_available' => $product->current_stock > 0
            ]
        ]);
    }
}
