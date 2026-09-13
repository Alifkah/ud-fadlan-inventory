<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\Customer;
use App\Models\CustomerCredit;
use App\Models\StockTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class SalesController extends Controller
{
    /**
     * Tampilkan halaman utama transaksi penjualan
     */
    public function index(Request $request)
    {
        $query = Sale::with(['customer', 'user', 'saleItems.product', 'customerCredit']);

        // Filter berdasarkan tanggal
        if ($request->filled('date_from')) {
            $query->whereDate('sale_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('sale_date', '<=', $request->date_to);
        }

        // Filter berdasarkan status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter berdasarkan metode pembayaran
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Filter berdasarkan pencarian
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                ->orWhereHas('customer', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            });
        }

        // Sorting
        switch ($request->sort) {
            case 'date_asc':
                $query->orderBy('sale_date', 'asc');
                break;
            case 'date_desc':
                $query->orderBy('sale_date', 'desc');
                break;
            case 'total_asc':
                $query->orderBy('total', 'asc');
                break;
            case 'total_desc':
                $query->orderBy('total', 'desc');
                break;
            default:
                $query->latest('sale_date');
        }

        $sales = $query->paginate(10);
        
        // Calculate statistics
        $todaySales = Sale::whereDate('sale_date', Carbon::today())
                        ->where('status', 'completed')
                        ->count();
        
        $todayAmount = Sale::whereDate('sale_date', Carbon::today())
                        ->where('status', 'completed')
                        ->sum('total');

        $stats = [
            'total_sales' => Sale::where('status', 'completed')->sum('total'),
            'total_transactions' => Sale::where('status', 'completed')->count(),
            'pending_transactions' => Sale::where('status', 'pending')->count(),
            'total_credit_amount' => CustomerCredit::where('status', 'active')->sum('remaining_amount')
        ];

        return view('sales.index', compact('sales', 'todaySales', 'todayAmount', 'stats'));
    }


    /**
     * Show the form for creating a new sale.
     */
    public function create()
    {
        // Get active customers for customer selection
        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        
        // Get active products for product selection
        $products = Product::where('is_active', true)
                          ->with('category')
                          ->orderBy('name')
                          ->get();

        // Generate invoice number
        $invoiceNumber = $this->generateInvoiceNumber();

        return view('sales.create', compact('customers', 'products', 'invoiceNumber'));
    }

    /**
     * Tampilkan daftar semua transaksi penjualan
     */
    public function list(Request $request)
    {
        $query = Sale::with(['customer', 'user', 'saleItems.product', 'customerCredit']);

        // Filter berdasarkan tanggal
        if ($request->filled('date_from')) {
            $query->whereDate('sale_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('sale_date', '<=', $request->date_to);
        }

        // Filter berdasarkan status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter berdasarkan customer
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter berdasarkan metode pembayaran
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Search berdasarkan invoice number
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($customerQuery) use ($search) {
                      $customerQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $sales = $query->orderBy('created_at', 'desc')->paginate(15);
        $customers = Customer::where('is_active', true)->orderBy('name')->get();

        // Statistik
        $stats = [
            'total_sales' => Sale::where('status', 'completed')->sum('total'),
            'total_transactions' => Sale::where('status', 'completed')->count(),
            'pending_transactions' => Sale::where('status', 'pending')->count(),
            'today_sales' => Sale::whereDate('sale_date', today())
                                ->where('status', 'completed')
                                ->sum('total'),
            'credit_sales' => Sale::where('payment_method', 'credit')->count(),
            'total_credit_amount' => CustomerCredit::where('status', 'active')->sum('remaining_amount')
        ];

        return view('sales.list', compact('sales', 'customers', 'stats'));
    }

    /**
     * Simpan transaksi penjualan baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'sale_date' => 'required|date',
            'payment_method' => 'required|in:cash,credit,transfer',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.total_price' => 'required|numeric|min:0',
            'subtotal' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'status' => 'nullable|in:draft,pending,completed',
            'paid_amount' => 'nullable|numeric|min:0',
            'change' => 'nullable|numeric',
            'due_date' => 'required_if:payment_method,credit|nullable|date|after:today',
            'interest_rate' => 'nullable|numeric|min:0|max:100',
            'credit_notes' => 'nullable|string|max:500'
        ]);

        DB::beginTransaction();

        try {
            // Validasi stok untuk setiap item
            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);
                if (!$product) {
                    throw new \Exception("Produk tidak ditemukan");
                }
                if ($item['quantity'] > $product->current_stock) {
                    throw new \Exception("Stok {$product->name} tidak mencukupi. Stok tersedia: {$product->current_stock}");
                }
            }

            // Tentukan status berdasarkan payment method dan input
            $status = $validated['status'] ?? 'completed';
            if ($validated['payment_method'] === 'credit' && !isset($validated['status'])) {
                $status = 'pending';
            }

            // Buat transaksi penjualan
            $sale = Sale::create([
                'invoice_number' => $this->generateInvoiceNumber(),
                'customer_id' => $validated['customer_id'],
                'sale_date' => $validated['sale_date'],
                'subtotal' => floatval($validated['subtotal']),
                'discount' => floatval($validated['discount'] ?? 0),
                'tax' => floatval($validated['tax'] ?? 0),
                'total' => floatval($validated['total']),
                'payment_method' => $validated['payment_method'],
                'status' => $status,
                'notes' => $validated['notes'] ?? null,
                'user_id' => Auth::id(),
                'paid_amount' => $validated['paid_amount'] ? floatval($validated['paid_amount']) : null,
                'change' => $validated['change'] ? floatval($validated['change']) : null
            ]);

            // Buat item penjualan 
            foreach ($validated['items'] as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => intval($item['quantity']),
                    'unit_price' => floatval($item['unit_price']),
                    'total_price' => floatval($item['total_price'])
                ]);
            }

            // Update stok hanya jika status completed
            if ($status === 'completed') {
                $this->updateStockFromSale($sale);
            }

            // Handle kredit jika payment method adalah credit
            if ($validated['payment_method'] === 'credit') {
                $this->createCustomerCredit($sale, $validated);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi penjualan berhasil dibuat',
                'data' => [
                    'sale_id' => $sale->id,
                    'invoice_number' => $sale->invoice_number,
                    'total' => $sale->total,
                    'status' => $sale->status,
                    'payment_method' => $sale->payment_method,
                    'sale_date' => $sale->sale_date->format('Y-m-d'),
                    'has_credit' => $validated['payment_method'] === 'credit'
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating sale: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan transaksi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tampilkan detail transaksi penjualan
     */
    public function show(Sale $sale)
    {
        $sale->load([
            'customer',
            'user',
            'saleItems.product.category',
            'customerCredit'
        ]);

        return view('sales.show', compact('sale'));
    }

    /**
     * Tampilkan formulir edit transaksi
     */
    public function edit(Sale $sale)
    {
        // Hanya bisa edit jika status pending
        if ($sale->status !== 'pending') {
            return back()->with('error', 'Hanya transaksi dengan status pending yang dapat diubah');
        }

        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('is_active', true)->with('category')->orderBy('name')->get();
        $sale->load(['saleItems.product', 'customerCredit']);

        return view('sales.edit', compact('sale', 'customers', 'products'));
    }

    /**
     * Update transaksi penjualan
     */
    public function update(Request $request, Sale $sale)
    {
        if ($sale->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya transaksi dengan status pending yang dapat diubah'
            ], 422);
        }

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'sale_date' => 'required|date',
            'payment_method' => 'required|in:cash,credit,transfer',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.total_price' => 'required|numeric|min:0',
            'subtotal' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'due_date' => 'required_if:payment_method,credit|date|after:today',
            'interest_rate' => 'nullable|numeric|min:0|max:100',
            'credit_notes' => 'nullable|string|max:500'
        ]);

        DB::beginTransaction();

        try {
            $oldPaymentMethod = $sale->payment_method;

            // Kembalikan stok jika sudah di-update sebelumnya
            if ($sale->status === 'completed') {
                $this->restoreStockFromSale($sale);
            }

            // Hapus item lama
            $sale->saleItems()->delete();

            // Update transaksi penjualan
            $sale->update([
                'customer_id' => $validated['customer_id'],
                'sale_date' => $validated['sale_date'],
                'subtotal' => $validated['subtotal'],
                'discount' => $validated['discount'] ?? 0,
                'tax' => $validated['tax'] ?? 0,
                'total' => $validated['total'],
                'payment_method' => $validated['payment_method'],
                'status' => $validated['payment_method'] === 'cash' ? 'completed' : 'pending',
                'notes' => $validated['notes']
            ]);

            // Buat item baru
            foreach ($validated['items'] as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price']
                ]);
            }

            // Handle perubahan payment method
            if ($oldPaymentMethod !== $validated['payment_method']) {
                // Jika sebelumnya kredit dan sekarang bukan kredit, hapus kredit
                if ($oldPaymentMethod === 'credit' && $validated['payment_method'] !== 'credit') {
                    if ($sale->customerCredit) {
                        $sale->customerCredit->delete();
                    }
                }
                // Jika sekarang kredit dan sebelumnya bukan kredit, buat kredit
                elseif ($oldPaymentMethod !== 'credit' && $validated['payment_method'] === 'credit') {
                    $this->createCustomerCredit($sale, $validated);
                }
            }

            // Update stok jika payment method cash
            if ($validated['payment_method'] === 'cash') {
                $this->updateStockFromSale($sale);
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

    /**
     * Hapus transaksi penjualan
     */
    public function destroy(Sale $sale)
    {
        if ($sale->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi yang sudah selesai tidak dapat dihapus'
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Kembalikan stok jika sudah di-update
            if ($sale->status === 'completed') {
                $this->restoreStockFromSale($sale);
            }

            // Hapus kredit customer jika ada
            if ($sale->customerCredit) {
                $sale->customerCredit->delete();
            }

            $sale->delete();

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

    /**
     * Konfirmasi pembayaran untuk transaksi kredit
     */
    public function confirmPayment(Request $request, Sale $sale)
    {
        if ($sale->payment_method !== 'credit' || $sale->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi ini tidak dapat dikonfirmasi'
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Update status transaksi
            $sale->update(['status' => 'completed']);

            // Update stok produk
            $this->updateStockFromSale($sale);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pembayaran berhasil dikonfirmasi',
                'data' => [
                    'sale_id' => $sale->id,
                    'invoice_number' => $sale->invoice_number,
                    'status' => $sale->status
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengkonfirmasi pembayaran: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menampilkan struk penjualan dalam format PDF (untuk preview di browser)
     *
     * @param \App\Models\Sale $sale
     * @return \Illuminate\Http\Response
     */
    public function printReceipt(Sale $sale)
    {
        // Load relasi yang diperlukan
        $sale->load(['customer', 'saleItems.product.category', 'user', 'customerCredit']);
        
        // Ambil data perusahaan dari konfigurasi atau database
        $company = [
            'name' => config('app.name', 'UD Fadlan'),
            'address' => 'MCCS 19DR, Jl. Muara Badak - Samarinda, Gas Alam Badak 5 Kec Muara Badak, Kabupaten Kutai Kartanegara, Kalimantan Timur',
            'phone' => '0541-123456'
        ];
        
        // Render view sebagai HTML
        $html = view('sales.receipt', compact('sale', 'company'))->render();
        
        // Gunakan DomPDF untuk menghasilkan PDF
        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'portrait');
        
        // Nama file PDF
        $filename = "Struk_Penjualan_{$sale->invoice_number}.pdf";
        
        // Tampilkan PDF di browser
        return $pdf->stream($filename);
    }

    /**
     * Download struk penjualan dalam format PDF
     *
     * @param \App\Models\Sale $sale
     * @return \Illuminate\Http\Response
     */
    public function downloadReceipt(Sale $sale)
    {
        // Load relasi yang diperlukan
        $sale->load(['customer', 'saleItems.product.category', 'user', 'customerCredit']);
        
        // Ambil data perusahaan
        $company = [
            'name' => config('app.name', 'UD Fadlan'),
            'address' => 'MCCS 19DR, Jl. Muara Badak - Samarinda, Gas Alam Badak 5 Kec Muara Badak, Kabupaten Kutai Kartanegara, Kalimantan Timur',
            'phone' => '0541-123456'
        ];
        
        // Render view sebagai HTML
        $html = view('sales.receipt', compact('sale', 'company'))->render();
        
        // Gunakan DomPDF untuk menghasilkan PDF
        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'portrait');
        
        // Nama file PDF
        $filename = "Struk_Penjualan_{$sale->invoice_number}.pdf";
        
        // Download PDF
        return $pdf->download($filename);
    }

    /**
     * Search produk untuk AJAX
     */
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
                    'selling_price' => $product->selling_price,
                    'current_stock' => $product->current_stock
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * Dapatkan informasi produk
     */
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
                'minimum_stock' => $product->minimum_stock,
                'status' => $product->current_stock > 0 ? 'available' : 'out_of_stock'
            ]
        ]);
    }

    /**
     * Dapatkan laporan penjualan
     */
    public function getSalesReport(Request $request)
    {
        $validated = $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'group_by' => 'nullable|in:day,week,month',
            'include_credit' => 'nullable|boolean'
        ]);

        $query = Sale::where('status', 'completed')
                    ->whereBetween('sale_date', [$validated['date_from'], $validated['date_to']]);

        if (isset($validated['include_credit']) && $validated['include_credit']) {
            $query->with('customerCredit');
        }

        $groupBy = $validated['group_by'] ?? 'day';

        switch ($groupBy) {
            case 'day':
                $sales = $query->selectRaw('DATE(sale_date) as period, COUNT(*) as total_transactions, SUM(total) as total_sales, payment_method')
                              ->groupBy('period', 'payment_method')
                              ->orderBy('period')
                              ->get();
                break;
            case 'week':
                $sales = $query->selectRaw('YEARWEEK(sale_date) as period, COUNT(*) as total_transactions, SUM(total) as total_sales, payment_method')
                              ->groupBy('period', 'payment_method')
                              ->orderBy('period')
                              ->get();
                break;
            case 'month':
                $sales = $query->selectRaw('DATE_FORMAT(sale_date, "%Y-%m") as period, COUNT(*) as total_transactions, SUM(total) as total_sales, payment_method')
                              ->groupBy('period', 'payment_method')
                              ->orderBy('period')
                              ->get();
                break;
        }

        return response()->json([
            'success' => true,
            'data' => $sales
        ]);
    }

    /**
     * Get transaction details for modal (API endpoint)
     */
    public function getTransactionDetails(Sale $sale)
    {
        try {
            $sale->load(['customer', 'user', 'saleItems.product.category', 'customerCredit']);

            $transactionData = [
                'id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'customer_name' => $sale->customer->name ?? null,
                'customer_id' => $sale->customer_id,
                'sale_date' => $sale->sale_date->format('Y-m-d'),
                'payment_method' => $sale->payment_method,
                'status' => $sale->status,
                'subtotal' => $sale->subtotal,
                'discount' => $sale->discount ?? 0,
                'tax' => $sale->tax ?? 0,
                'total' => $sale->total,
                'notes' => $sale->notes,
                'items' => $sale->saleItems->map(function($item) {
                    return [
                        'id' => $item->id,
                        'product_name' => $item->product->name,
                        'product_code' => $item->product->code,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'total_price' => $item->total_price,
                        'unit' => $item->product->unit
                    ];
                })
            ];

            return response()->json([
                'success' => true,
                'data' => $transactionData
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching transaction details: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat detail transaksi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Dapatkan kredit customer yang akan jatuh tempo
     */
    public function getDueCustomerCredits()
    {
        $dueCredits = CustomerCredit::with(['customer', 'sale'])
                                   ->where('status', 'active')
                                   ->where('due_date', '<=', today()->addDays(7))
                                   ->orderBy('due_date')
                                   ->get()
                                   ->map(function ($credit) {
                                       $daysUntilDue = today()->diffInDays($credit->due_date, false);
                                       return [
                                           'id' => $credit->id,
                                           'credit_number' => $credit->credit_number,
                                           'customer_name' => $credit->customer->name,
                                           'invoice_number' => $credit->sale->invoice_number,
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
     * Dapatkan statistik kredit customer untuk dashboard
     */
    public function getCustomerCreditStats()
    {
        $stats = [
            'total_active_credits' => CustomerCredit::where('status', 'active')->count(),
            'total_credit_amount' => CustomerCredit::where('status', 'active')->sum('remaining_amount'),
            'overdue_credits' => CustomerCredit::where('status', 'active')
                                              ->where('due_date', '<', today())
                                              ->count(),
            'due_this_week' => CustomerCredit::where('status', 'active')
                                            ->whereBetween('due_date', [today(), today()->addDays(7)])
                                            ->count(),
            'total_paid_this_month' => CustomerCredit::whereMonth('created_at', now()->month)
                                                    ->whereYear('created_at', now()->year)
                                                    ->where('status', 'inactive')
                                                    ->sum('paid_amount'),
            'average_credit_amount' => CustomerCredit::where('status', 'active')->avg('total_credit') ?? 0
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Buat kredit customer untuk sale dengan metode kredit
     */
    private function createCustomerCredit($sale, $validated)
    {
        $creditNumber = 'KRC-' . date('Ymd') . '-' . str_pad(CustomerCredit::count() + 1, 3, '0', STR_PAD_LEFT);

        return CustomerCredit::create([
            'credit_number' => $creditNumber,
            'customer_id' => $sale->customer_id,
            'sale_id' => $sale->id,
            'total_credit' => $sale->total,
            'paid_amount' => 0,
            'remaining_amount' => $sale->total,
            'due_date' => $validated['due_date'] ?? Carbon::now()->addDays(30),
            'status' => 'active',
            'notes' => $validated['credit_notes'] ?? null
        ]);
    }

    /**
     * Update stok dari sale
     */
    private function updateStockFromSale($sale)
    {
        foreach ($sale->saleItems as $item) {
            $product = Product::find($item->product_id);
            $oldStock = $product->current_stock;
            $newStock = $oldStock - $item->quantity;
            
            // Update stok produk
            $product->update(['current_stock' => max(0, $newStock)]);

            // Buat transaksi stok
            $this->createStockTransaction([
                'product_id' => $product->id,
                'type' => 'out',
                'quantity' => $item->quantity,
                'stock_before' => $oldStock,
                'stock_after' => $newStock,
                'price' => $item->unit_price,
                'reference_type' => 'sale',
                'reference_id' => $sale->id,
                'notes' => "Penjualan - Invoice: {$sale->invoice_number}"
            ]);
        }
    }

    /**
     * Kembalikan stok dari sale (untuk edit/cancel)
     */
    private function restoreStockFromSale($sale)
    {
        foreach ($sale->saleItems as $item) {
            $product = Product::find($item->product_id);
            $oldStock = $product->current_stock;
            $newStock = $oldStock + $item->quantity;
            
            // Update stok produk
            $product->update(['current_stock' => $newStock]);

            // Hapus transaksi stok terkait
            StockTransaction::where('reference_type', 'sale')
                           ->where('reference_id', $sale->id)
                           ->where('product_id', $product->id)
                           ->delete();
        }
    }

    /**
     * Get sales by customer
     * Untuk dropdown di form create/edit customer credit
     */
    public function byCustomer(Request $request, Customer $customer)
    {
        $query = Sale::where('customer_id', $customer->id);

        // Filter by payment method
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Get sales that don't have customer credit yet
        if ($request->boolean('without_credit')) {
            $query->whereDoesntHave('customerCredit');
        }

        $sales = $query->orderBy('sale_date', 'desc')
                    ->limit(20)
                    ->get(['id', 'invoice_number', 'sale_date', 'total', 'payment_method', 'status']);

        return response()->json([
            'success' => true,
            'data' => $sales
        ]);
    }

    /**
     * Get sale details for credit
     */
    public function getSaleForCredit(Sale $sale)
    {
        // Load necessary relationships
        $sale->load(['customer', 'saleItems.product']);

        // Check if sale already has credit
        if ($sale->customerCredit) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi ini sudah memiliki kredit terkait'
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $sale
        ]);
    }

    /**
     * Generate invoice number
     */
    private function generateInvoiceNumber()
    {
        $prefix = 'PJL';
        $date = date('Ymd');
        $lastSale = Sale::whereDate('created_at', today())->latest('id')->first();
        $sequence = $lastSale ? (intval(substr($lastSale->invoice_number, -3)) + 1) : 1;
        
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

    /**
     * Export sales data
     */
    public function export(Request $request)
    {
        $format = $request->get('format', 'excel');
        
        $query = Sale::with(['customer', 'user', 'saleItems.product']);

        // Apply filters
        if ($request->filled('date_from')) {
            $query->whereDate('sale_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('sale_date', '<=', $request->date_to);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        $sales = $query->orderBy('sale_date', 'desc')->get();

        if ($format === 'excel') {
            return $this->exportToExcel($sales);
        } else {
            return $this->exportToPdf($sales);
        }
    }

    /**
     * Export to Excel
     */
    private function exportToExcel($sales)
    {
        $filename = 'sales_export_' . date('Ymd_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($sales) {
            $file = fopen('php://output', 'w');
            
            // Header
            fputcsv($file, [
                'Invoice Number',
                'Date',
                'Customer',
                'Total Items',
                'Subtotal',
                'Discount',
                'Tax',
                'Total',
                'Payment Method',
                'Status',
                'Cashier'
            ]);

            // Data
            foreach ($sales as $sale) {
                fputcsv($file, [
                    $sale->invoice_number,
                    $sale->sale_date->format('Y-m-d H:i:s'),
                    $sale->customer->name ?? 'Guest',
                    $sale->saleItems->count(),
                    $sale->subtotal,
                    $sale->discount,
                    $sale->tax,
                    $sale->total,
                    $sale->payment_method,
                    $sale->status,
                    $sale->user->name ?? 'Unknown'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to PDF
     */
    private function exportToPdf($sales)
    {
        $data = [
            'sales' => $sales,
            'date_from' => request('date_from'),
            'date_to' => request('date_to'),
            'generated_at' => now()
        ];

        $pdf = Pdf::loadView('sales.export-pdf', $data)->setPaper('a4', 'landscape');
        return $pdf->download('sales_export_' . date('Ymd_His') . '.pdf');
    }
}