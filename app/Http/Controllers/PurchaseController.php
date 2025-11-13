<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\StockTransaction;
use App\Models\SupplierCredit;
use App\Models\SupplierCreditPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $query = Purchase::with(['supplier', 'user', 'purchaseItems.product', 'supplierCredit']);

        // Filter berdasarkan tanggal
        if ($request->filled('date_from')) {
            $query->whereDate('purchase_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('purchase_date', '<=', $request->date_to);
        }

        // Filter berdasarkan status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter berdasarkan supplier
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // Filter berdasarkan metode pembayaran
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Filter hanya kredit dengan status belum lunas
        if ($request->filled('credit_status') && $request->credit_status === 'active') {
            $query->whereHas('supplierCredit', function ($q) {
                $q->where('status', 'active');
            });
        }

        // Search berdasarkan invoice number
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('supplier', function ($supplierQuery) use ($search) {
                      $supplierQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $purchases = $query->orderBy('created_at', 'desc')->paginate(15);

        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();

        // Statistik
        $stats = [
            'total_purchases' => Purchase::where('status', 'completed')->sum('total'),
            'total_transactions' => Purchase::where('status', 'completed')->count(),
            'pending_transactions' => Purchase::where('status', 'pending')->count(),
            'today_purchases' => Purchase::whereDate('purchase_date', today())
                                      ->where('status', 'completed')
                                      ->sum('total'),
            'credit_purchases' => Purchase::where('payment_method', 'credit')->count(),
            'total_credit_amount' => SupplierCredit::where('status', 'active')->sum('remaining_amount')
        ];

        return view('purchases.index', compact('purchases', 'suppliers', 'stats'));
    }

    public function create()
    {
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('is_active', true)
                          ->with('category')
                          ->orderBy('name')
                          ->get();

        // Generate invoice number
        $invoiceNumber = $this->generateInvoiceNumber();

        return view('purchases.create', compact('suppliers', 'products', 'invoiceNumber'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'payment_method' => 'required|in:cash,credit,transfer',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'subtotal' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            // Fields untuk transfer
            'bank_name' => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:50',
            // Fields untuk kredit
            'due_date' => 'required_if:payment_method,credit|date|after:today',
            'interest_rate' => 'nullable|numeric|min:0|max:100',
            'credit_notes' => 'nullable|string|max:500'
        ]);

        DB::beginTransaction();

        try {
            // Buat transaksi pembelian dengan status pending dulu
            $purchase = Purchase::create([
                'invoice_number' => $this->generateInvoiceNumber(),
                'supplier_id' => $validated['supplier_id'],
                'purchase_date' => $validated['purchase_date'],
                'subtotal' => $validated['subtotal'],
                'discount' => $validated['discount'] ?? 0,
                'tax' => $validated['tax'] ?? 0,
                'total' => $validated['total'],
                'payment_method' => $validated['payment_method'],
                'status' => 'pending', // Semua dimulai sebagai pending
                'notes' => $validated['notes'],
                'user_id' => Auth::id()
            ]);

            // Buat item pembelian (belum update stok)
            foreach ($validated['items'] as $item) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['quantity'] * $item['unit_price']
                ]);
            }

            // Validasi khusus untuk payment method credit
            if ($validated['payment_method'] === 'credit') {
                // Pastikan supplier memiliki limit kredit yang memadai
                $supplier = Supplier::find($validated['supplier_id']);
                $currentCredit = $supplier->total_active_credit ?? 0;
                $creditLimit = $supplier->credit_limit ?? 0; // Tambahkan field ini jika diperlukan
                
                if ($creditLimit > 0 && ($currentCredit + $validated['total']) > $creditLimit) {
                    throw new \Exception('Kredit supplier melebihi batas limit yang ditetapkan');
                }
                
                // Pastada due_date tidak terlalu jauh
                $dueDate = Carbon::parse($validated['due_date']);
                $maxCreditDays = 365; // Maksimal 1 tahun
                
                if ($dueDate->diffInDays(today()) > $maxCreditDays) {
                    throw new \Exception('Tanggal jatuh tempo terlalu jauh (maksimal 1 tahun)');
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi pembelian berhasil dibuat',
                'data' => [
                    'purchase_id' => $purchase->id,
                    'invoice_number' => $purchase->invoice_number,
                    'total' => $purchase->total,
                    'status' => $purchase->status,
                    'payment_method' => $purchase->payment_method,
                    'has_credit' => $validated['payment_method'] === 'credit'
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan transaksi: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Purchase $purchase)
    {
        $purchase->load([
            'supplier', 
            'user', 
            'purchaseItems.product.category',
            'supplierCredit.payments' => function ($query) {
                $query->with('user')->orderBy('payment_date', 'desc');
            }
        ]);

        return view('purchases.show', compact('purchase'));
    }

    public function edit(Purchase $purchase)
    {
        // Hanya bisa edit jika status pending
        if ($purchase->status !== 'pending') {
            return back()->with('error', 'Hanya transaksi dengan status pending yang dapat diubah');
        }

        // Jika ada kredit dan sudah ada pembayaran, tidak bisa edit
        if ($purchase->supplierCredit && $purchase->supplierCredit->paid_amount > 0) {
            return back()->with('error', 'Transaksi kredit yang sudah ada pembayaran tidak dapat diubah');
        }

        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('is_active', true)->with('category')->orderBy('name')->get();
        $purchase->load(['purchaseItems.product', 'supplierCredit']);

        return view('purchases.edit', compact('purchase', 'suppliers', 'products'));
    }

    public function update(Request $request, Purchase $purchase)
    {
        if ($purchase->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya transaksi dengan status pending yang dapat diubah'
            ], 422);
        }

        // Cek apakah ada kredit dengan pembayaran
        if ($purchase->supplierCredit && $purchase->supplierCredit->paid_amount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi kredit yang sudah ada pembayaran tidak dapat diubah'
            ], 422);
        }

        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'payment_method' => 'required|in:cash,credit,transfer',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'subtotal' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            // Fields untuk kredit
            'due_date' => 'required_if:payment_method,credit|date|after:today',
            'interest_rate' => 'nullable|numeric|min:0|max:100',
            'credit_notes' => 'nullable|string|max:500'
        ]);

        DB::beginTransaction();

        try {
            $oldPaymentMethod = $purchase->payment_method;

            // Hapus item lama
            $purchase->purchaseItems()->delete();

            // Update transaksi pembelian
            $purchase->update([
                'supplier_id' => $validated['supplier_id'],
                'purchase_date' => $validated['purchase_date'],
                'subtotal' => $validated['subtotal'],
                'discount' => $validated['discount'] ?? 0,
                'tax' => $validated['tax'] ?? 0,
                'total' => $validated['total'],
                'payment_method' => $validated['payment_method'],
                'notes' => $validated['notes']
            ]);

            // Buat item baru
            foreach ($validated['items'] as $item) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['quantity'] * $item['unit_price']
                ]);
            }

            // Handle perubahan payment method
            if ($oldPaymentMethod !== $validated['payment_method']) {
                // Jika sebelumnya kredit dan sekarang bukan kredit, hapus kredit
                if ($oldPaymentMethod === 'credit' && $validated['payment_method'] !== 'credit') {
                    if ($purchase->supplierCredit) {
                        $purchase->supplierCredit->delete();
                    }
                }
                // Jika sekarang kredit dan sebelumnya bukan kredit, buat kredit
                elseif ($oldPaymentMethod !== 'credit' && $validated['payment_method'] === 'credit') {
                    $this->createSupplierCredit($purchase, $validated);
                }
            }

            // Validasi khusus untuk payment method credit
            if ($validated['payment_method'] === 'credit') {
                // Pastikan supplier memiliki limit kredit yang memadai
                $supplier = Supplier::find($validated['supplier_id']);
                $currentCredit = $supplier->total_active_credit ?? 0;
                $creditLimit = $supplier->credit_limit ?? 0; // Tambahkan field ini jika diperlukan
                
                if ($creditLimit > 0 && ($currentCredit + $validated['total']) > $creditLimit) {
                    throw new \Exception('Kredit supplier melebihi batas limit yang ditetapkan');
                }
                
                // Pastada due_date tidak terlalu jauh
                $dueDate = Carbon::parse($validated['due_date']);
                $maxCreditDays = 365; // Maksimal 1 tahun
                
                if ($dueDate->diffInDays(today()) > $maxCreditDays) {
                    throw new \Exception('Tanggal jatuh tempo terlalu jauh (maksimal 1 tahun)');
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil diperbarui'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui transaksi: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Purchase $purchase)
    {
        if ($purchase->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi yang sudah selesai tidak dapat dihapus'
            ], 422);
        }

        // Cek apakah ada kredit dengan pembayaran
        if ($purchase->supplierCredit && $purchase->supplierCredit->paid_amount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi kredit yang sudah ada pembayaran tidak dapat dihapus'
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Jika sudah ada perubahan stok, kembalikan stok
            if ($purchase->status === 'completed') {
                foreach ($purchase->purchaseItems as $item) {
                    $product = Product::find($item->product_id);
                    $product->decrement('current_stock', $item->quantity);
                }

                // Hapus transaksi stok terkait
                StockTransaction::where('reference_type', 'purchase')
                               ->where('reference_id', $purchase->id)
                               ->delete();
            }

            // Hapus kredit supplier jika ada
            if ($purchase->supplierCredit) {
                $purchase->supplierCredit->payments()->delete(); // Hapus pembayaran
                $purchase->supplierCredit->delete(); // Hapus kredit
            }

            $purchase->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus transaksi: ' . $e->getMessage()
            ], 500);
        }
    }

    public function processPayment(Request $request, Purchase $purchase)
    {
        if ($purchase->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi ini sudah diproses sebelumnya'
            ], 422);
        }

        $validated = $request->validate([
            'payment_method' => 'required|in:cash,transfer,credit',
            'bank_name' => 'required_if:payment_method,transfer|string|max:100',
            'account_number' => 'required_if:payment_method,transfer|string|max:50',
            'due_date' => 'required_if:payment_method,credit|date|after:today',
            'interest_rate' => 'nullable|numeric|min:0|max:100',
            'credit_notes' => 'nullable|string|max:500'
        ]);

        DB::beginTransaction();

        try {
            $oldPaymentMethod = $purchase->payment_method;

            // Update payment method dan status berdasarkan metode pembayaran
            $newStatus = ($validated['payment_method'] === 'credit') ? 'pending' : 'completed';
            
            $purchase->update([
                'payment_method' => $validated['payment_method'],
                'status' => $newStatus
            ]);

            // Handle perubahan dari kredit ke non-kredit
            if ($oldPaymentMethod === 'credit' && $validated['payment_method'] !== 'credit') {
                if ($purchase->supplierCredit && $purchase->supplierCredit->paid_amount == 0) {
                    // Hapus kredit jika belum ada pembayaran
                    $purchase->supplierCredit->delete();
                } elseif ($purchase->supplierCredit) {
                    // Tandai sebagai lunas jika sudah ada pembayaran
                    $this->markCreditAsPaid($purchase->supplierCredit, $validated['payment_method']);
                }
            }

            // Handle perubahan dari non-kredit ke kredit
            if ($oldPaymentMethod !== 'credit' && $validated['payment_method'] === 'credit') {
                $this->createSupplierCredit($purchase, $validated);
            }

            // Jika pembayaran cash atau transfer, langsung update stok
            if (in_array($validated['payment_method'], ['cash', 'transfer'])) {
                $this->updateStockFromPurchase($purchase);
            }

            // Simpan informasi tambahan untuk transfer
            if ($validated['payment_method'] === 'transfer') {
                $purchase->update([
                    'notes' => $purchase->notes . "\nBank: " . $validated['bank_name'] . 
                              "\nNo. Rekening: " . $validated['account_number']
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pembayaran berhasil diproses',
                'data' => [
                    'purchase_id' => $purchase->id,
                    'invoice_number' => $purchase->invoice_number,
                    'status' => $purchase->status,
                    'payment_method' => $purchase->payment_method,
                    'stock_updated' => in_array($validated['payment_method'], ['cash', 'transfer']),
                    'has_credit' => $validated['payment_method'] === 'credit'
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses pembayaran: ' . $e->getMessage()
            ], 500);
        }
    }

    public function confirmReceipt(Purchase $purchase)
    {
        try {
            // Validasi status
            if ($purchase->status === 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi ini sudah dikonfirmasi sebelumnya.'
                ], 400);
            }

            if ($purchase->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya transaksi dengan status pending yang dapat dikonfirmasi.'
                ], 400);
            }

            DB::beginTransaction();

            // Update stok untuk semua item
            foreach ($purchase->purchaseItems as $item) {
                $product = Product::find($item->product_id);
                
                if (!$product) {
                    throw new \Exception("Produk dengan ID {$item->product_id} tidak ditemukan");
                }

                $oldStock = $product->current_stock;
                $newStock = $oldStock + $item->quantity;
                
                // Update stok produk
                $product->update(['current_stock' => $newStock]);

                // Buat transaksi stok
                $this->createStockTransaction([
                    'product_id' => $product->id,
                    'type' => 'in',
                    'quantity' => $item->quantity,
                    'stock_before' => $oldStock,
                    'stock_after' => $newStock,
                    'price' => $item->unit_price,
                    'reference_type' => 'purchase',
                    'reference_id' => $purchase->id,
                    'notes' => "Penerimaan Pembelian - Invoice: {$purchase->invoice_number}"
                ]);
            }

            // Update status purchase menjadi completed
            $purchase->update([
                'status' => 'completed',
                'received_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Penerimaan barang berhasil dikonfirmasi dan stok telah diperbarui!',
                'data' => [
                    'purchase_id' => $purchase->id,
                    'invoice_number' => $purchase->invoice_number,
                    'status' => 'completed',
                    'items_updated' => $purchase->purchaseItems->count()
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get kredit supplier yang akan jatuh tempo
     */
    public function getDueSupplierCredits()
    {
        $dueCredits = SupplierCredit::with(['supplier', 'purchase'])
                                   ->where('status', 'active')
                                   ->where('due_date', '<=', today()->addDays(7))
                                   ->orderBy('due_date')
                                   ->get()
                                   ->map(function ($credit) {
                                       $daysUntilDue = today()->diffInDays($credit->due_date, false);
                                       return [
                                           'id' => $credit->id,
                                           'credit_number' => $credit->credit_number,
                                           'supplier_name' => $credit->supplier->name,
                                           'invoice_number' => $credit->purchase->invoice_number,
                                           'remaining_amount' => $credit->remaining_amount,
                                           'due_date' => $credit->due_date,
                                           'days_until_due' => $daysUntilDue,
                                           'is_overdue' => $daysUntilDue < 0
                                       ];
                                   });

        return response()->json([
            'success' => true,
            'data' => $dueCredits
        ]);
    }

    /**
     * Get statistik kredit supplier untuk dashboard
     */
    public function getSupplierCreditStats()
    {
        $stats = [
            'total_active_credits' => SupplierCredit::where('status', 'active')->count(),
            'total_credit_amount' => SupplierCredit::where('status', 'active')->sum('remaining_amount'),
            'overdue_credits' => SupplierCredit::where('status', 'active')
                                              ->where('due_date', '<', today())
                                              ->count(),
            'due_this_week' => SupplierCredit::where('status', 'active')
                                            ->whereBetween('due_date', [today(), today()->addDays(7)])
                                            ->count(),
            'total_paid_this_month' => SupplierCreditPayment::whereMonth('payment_date', now()->month)
                                                           ->whereYear('payment_date', now()->year)
                                                           ->sum('payment_amount'),
            'average_credit_amount' => SupplierCredit::where('status', 'active')->avg('total_credit') ?? 0
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get purchases dengan kredit aktif
     */
    public function getCreditPurchases(Request $request)
    {
        $query = Purchase::with(['supplier', 'supplierCredit'])
                        ->whereHas('supplierCredit', function ($q) {
                            $q->where('status', 'active');
                        });

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        $purchases = $query->orderBy('created_at', 'desc')
                          ->get()
                          ->map(function ($purchase) {
                              $credit = $purchase->supplierCredit;
                              return [
                                  'id' => $purchase->id,
                                  'invoice_number' => $purchase->invoice_number,
                                  'supplier_name' => $purchase->supplier->name,
                                  'total' => $purchase->total,
                                  'purchase_date' => $purchase->purchase_date,
                                  'credit_number' => $credit->credit_number,
                                  'remaining_amount' => $credit->remaining_amount,
                                  'due_date' => $credit->due_date,
                                  'payment_progress' => $credit->payment_progress
                              ];
                          });

        return response()->json([
            'success' => true,
            'data' => $purchases
        ]);
    }

    public function printPurchaseOrder(Purchase $purchase)
    {
        $purchase->load(['supplier', 'purchaseItems.product', 'user', 'supplierCredit']);
        
        $data = [
            'purchase' => $purchase,
            'company' => [
                'name' => config('app.name', 'UD Fadlan'),
                'address' => 'MCCS 19DR, Jl. Muara Badak - Samarinda, Gas Alam Badak 5 Kec Muara Badak, Kabupaten Kutai Kartanegara, Kalimantan Timur',
                'phone' => '0541-123456'
            ]
        ];

        $pdf = Pdf::loadView('purchases.purchase_order', $data)->setPaper('a4');
        return $pdf->stream("purchase-order-{$purchase->invoice_number}.pdf");
    }

    public function printReceipt(Purchase $purchase)
    {
        $purchase->load(['supplier', 'purchaseItems.product', 'user', 'supplierCredit']);
        
        $data = [
            'purchase' => $purchase,
            'company' => [
                'name' => 'UD Fadlan',
                'address' => 'MCCS 19DR, Jl. Muara Badak - Samarinda, Gas Alam Badak 5 Kec Muara Badak, Kabupaten Kutai Kartanegara, Kalimantan Timur',
                'phone' => '0541-123456'
            ]
        ];

        $pdf = Pdf::loadView('purchases.receipt', $data)->setPaper('a4');

        return $pdf->stream("receipt-{$purchase->invoice_number}.pdf");
    }

    public function searchProducts(Request $request)
    {
        $search = $request->get('search', '');
        
        $products = Product::where('is_active', true)
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
            })
            ->with('category')
            ->limit(10)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'code' => $product->code,
                    'name' => $product->name,
                    'category' => $product->category->name,
                    'unit' => $product->unit,
                    'purchase_price' => $product->purchase_price,
                    'current_stock' => $product->current_stock
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
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
                'purchase_price' => $product->purchase_price,
                'selling_price' => $product->selling_price,
                'current_stock' => $product->current_stock,
                'minimum_stock' => $product->minimum_stock
            ]
        ]);
    }

    public function getPurchaseReport(Request $request)
    {
        $validated = $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'group_by' => 'nullable|in:day,week,month',
            'include_credit' => 'nullable|boolean'
        ]);

        $query = Purchase::where('status', 'completed')
                        ->whereBetween('purchase_date', [$validated['date_from'], $validated['date_to']]);

        if (isset($validated['include_credit']) && $validated['include_credit']) {
            $query->with('supplierCredit');
        }

        $groupBy = $validated['group_by'] ?? 'day';

        switch ($groupBy) {
            case 'day':
                $purchases = $query->selectRaw('DATE(purchase_date) as period, COUNT(*) as total_transactions, SUM(total) as total_purchases, payment_method')
                                  ->groupBy('period', 'payment_method')
                                  ->orderBy('period')
                                  ->get();
                break;
            case 'week':
                $purchases = $query->selectRaw('YEARWEEK(purchase_date) as period, COUNT(*) as total_transactions, SUM(total) as total_purchases, payment_method')
                                  ->groupBy('period', 'payment_method')
                                  ->orderBy('period')
                                  ->get();
                break;
            case 'month':
                $purchases = $query->selectRaw('DATE_FORMAT(purchase_date, "%Y-%m") as period, COUNT(*) as total_transactions, SUM(total) as total_purchases, payment_method')
                                  ->groupBy('period', 'payment_method')
                                  ->orderBy('period')
                                  ->get();
                break;
        }

        return response()->json([
            'success' => true,
            'data' => $purchases
        ]);
    }

    /**
     * Buat kredit supplier untuk purchase dengan metode kredit
     */
    private function createSupplierCredit($purchase, $validated)
    {
        if (!$purchase->supplier_id) {
            throw new \Exception('Supplier harus dipilih untuk pembayaran kredit');
        }

        $creditNumber = 'KRS-' . date('Ymd') . '-' . str_pad(SupplierCredit::count() + 1, 3, '0', STR_PAD_LEFT);

        return SupplierCredit::create([
            'credit_number' => $creditNumber,
            'supplier_id' => $purchase->supplier_id,
            'purchase_id' => $purchase->id,
            'total_credit' => $purchase->total,
            'paid_amount' => 0,
            'remaining_amount' => $purchase->total,
            'due_date' => $validated['due_date'] ?? Carbon::now()->addDays(30),
            'status' => 'active',
            'notes' => $validated['credit_notes'] ?? null
        ]);
    }

    /**
     * Tandai kredit sebagai lunas ketika payment method berubah
     */
    private function markCreditAsPaid($supplierCredit, $paymentMethod)
    {
        $remainingAmount = $supplierCredit->remaining_amount;
        
        // Update kredit menjadi lunas
        $supplierCredit->update([
            'paid_amount' => $supplierCredit->total_credit,
            'remaining_amount' => 0,
            'status' => 'inactive'
        ]);

        // Buat record pembayaran
        SupplierCreditPayment::create([
            'supplier_credit_id' => $supplierCredit->id,
            'payment_amount' => $remainingAmount,
            'payment_date' => today(),
            'payment_method' => $paymentMethod,
            'reference_number' => 'AUTO-' . time(),
            'notes' => 'Pembayaran lunas otomatis - perubahan metode pembayaran',
            'user_id' => Auth::id()
        ]);
    }

    /**
     * Update stok dari purchase
     */
    private function updateStockFromPurchase($purchase)
    {
        foreach ($purchase->purchaseItems as $item) {
            $product = Product::find($item->product_id);
            $oldStock = $product->current_stock;
            $newStock = $oldStock + $item->quantity;
            
            // Update stok produk
            $product->update(['current_stock' => $newStock]);

            // Buat transaksi stok
            $this->createStockTransaction([
                'product_id' => $product->id,
                'type' => 'in',
                'quantity' => $item->quantity,
                'stock_before' => $oldStock,
                'stock_after' => $newStock,
                'price' => $item->unit_price,
                'reference_type' => 'purchase',
                'reference_id' => $purchase->id,
                'notes' => "Pembelian - Invoice: {$purchase->invoice_number}"
            ]);
        }
    }

    /**
     * Generate invoice number
     */
    private function generateInvoiceNumber()
    {
        $prefix = 'PBL';
        $date = date('Ymd');
        $lastPurchase = Purchase::whereDate('created_at', today())->latest('id')->first();
        $sequence = $lastPurchase ? (intval(substr($lastPurchase->invoice_number, -3)) + 1) : 1;
        
        return $prefix . '-' . $date . '-' . str_pad($sequence, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Buat transaksi stok
     */
    private function createStockTransaction($data)
    {
        $data['transaction_code'] = $this->generateStockTransactionCode();
        $data['user_id'] = Auth::id();
        
        return StockTransaction::create($data);
    }

    /**
     * Generate kode transaksi stok
     */
    private function generateStockTransactionCode()
    {
        $prefix = 'STK';
        $date = date('Ymd');
        $sequence = StockTransaction::whereDate('created_at', today())->count() + 1;
        
        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    public function export(Request $request)
    {
        $format = $request->get('format', 'excel');
        
        $query = Purchase::with(['supplier', 'user']);

        // Apply same filters as index
        if ($request->filled('date_from')) {
            $query->whereDate('purchase_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('purchase_date', '<=', $request->date_to);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                ->orWhereHas('supplier', function ($supplierQuery) use ($search) {
                    $supplierQuery->where('name', 'like', "%{$search}%");
                });
            });
        }

        $purchases = $query->orderBy('created_at', 'desc')->get();

        if ($format === 'pdf') {
            $data = [
                'purchases' => $purchases,
                'filters' => $request->all()
            ];
            
            $pdf = Pdf::loadView('purchases.export_pdf', $data)->setPaper('a4', 'landscape');
            return $pdf->download('laporan-pembelian-' . date('Y-m-d') . '.pdf');
        }

        // Excel export would use Laravel Excel package
        // For now, return CSV
        return $this->exportToCSV($purchases);
    }

    /**
     * Export to CSV
     */
    private function exportToCSV($purchases)
    {
        $filename = 'laporan-pembelian-' . date('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($purchases) {
            $file = fopen('php://output', 'w');
            
            // Header
            fputcsv($file, [
                'Invoice',
                'Tanggal',
                'Supplier',
                'Total',
                'Metode Pembayaran',
                'Status',
                'Staff'
            ]);

            // Data
            foreach ($purchases as $purchase) {
                fputcsv($file, [
                    $purchase->invoice_number,
                    $purchase->purchase_date->format('d/m/Y'),
                    $purchase->supplier->name ?? '-',
                    $purchase->total,
                    $purchase->payment_method_text,
                    $purchase->status_text,
                    $purchase->user->name ?? '-'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}