<?php

namespace App\Http\Controllers;

use App\Models\SupplierCredit;
use App\Models\Supplier;
use App\Models\Purchase;
use App\Models\SupplierCreditPayment;
use App\Models\StockTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SupplierCreditController extends Controller
{
    /**
     * Tampilkan daftar kredit supplier
     */
    public function index(Request $request)
    {
        $query = SupplierCredit::with(['supplier', 'purchase.user']);

        // Filter berdasarkan status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter berdasarkan supplier
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // Filter berdasarkan tanggal jatuh tempo
        if ($request->filled('due_date_from')) {
            $query->whereDate('due_date', '>=', $request->due_date_from);
        }

        if ($request->filled('due_date_to')) {
            $query->whereDate('due_date', '<=', $request->due_date_to);
        }

        // Search berdasarkan kode kredit atau nama supplier
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('credit_number', 'like', "%{$search}%")
                  ->orWhereHas('supplier', function ($supplierQuery) use ($search) {
                      $supplierQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $credits = $query->orderBy('created_at', 'desc')->paginate(15);

        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();

        // Statistik
        $stats = [
            'total_active_credit' => SupplierCredit::where('status', 'active')->sum('remaining_amount'),
            'total_credits' => SupplierCredit::where('status', 'active')->count(),
            'overdue_credits' => SupplierCredit::where('status', 'active')
                                              ->where('due_date', '<', today())
                                              ->count(),
            'due_this_week' => SupplierCredit::where('status', 'active')
                                            ->whereBetween('due_date', [today(), today()->addDays(7)])
                                            ->count()
        ];

        return view('supplier-credits.index', compact('credits', 'suppliers', 'stats'));
    }

    /**
     * Tampilkan detail kredit supplier
     */
    public function show(SupplierCredit $supplierCredit)
    {
        $supplierCredit->load([
            'supplier', 
            'purchase.user', 
            'purchase.purchaseItems.product',
            'payments' => function ($query) {
                $query->orderBy('payment_date', 'desc');
            }
        ]);

        // Hitung progress pembayaran
        $paymentProgress = ($supplierCredit->paid_amount / $supplierCredit->total_credit) * 100;

        // Status berdasarkan tanggal jatuh tempo
        $daysUntilDue = Carbon::parse($supplierCredit->due_date)->diffInDays(today(), false);
        $paymentStatus = $this->getPaymentStatus($supplierCredit, $daysUntilDue);

        return view('supplier-credits.show', compact('supplierCredit', 'paymentProgress', 'paymentStatus'));
    }

    /**
     * Tampilkan formulir edit kredit supplier
     */
    public function edit(SupplierCredit $supplierCredit)
    {
        if ($supplierCredit->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya kredit dengan status aktif yang dapat diubah'
            ], 422);
        }

        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $supplierCredit->load(['supplier', 'purchase.purchaseItems.product']);

        return response()->json([
            'success' => true,
            'data' => $supplierCredit,
            'suppliers' => $suppliers
        ]);
    }

    /**
     * Update kredit supplier
     */
    public function update(Request $request, SupplierCredit $supplierCredit)
    {
        if ($supplierCredit->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya kredit dengan status aktif yang dapat diubah'
            ], 422);
        }

        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'due_date' => 'required|date|after:today',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'subtotal' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'total' => 'required|numeric|min:0'
        ]);

        DB::beginTransaction();

        try {
            // Update purchase yang terkait
            $purchase = $supplierCredit->purchase;
            $purchase->update([
                'supplier_id' => $validated['supplier_id'],
                'subtotal' => $validated['subtotal'],
                'discount' => $validated['discount'] ?? 0,
                'tax' => $validated['tax'] ?? 0,
                'total' => $validated['total'],
                'notes' => $validated['notes']
            ]);

            // Update items (hapus yang lama, buat yang baru)
            $purchase->purchaseItems()->delete();
            foreach ($validated['items'] as $item) {
                $purchase->purchaseItems()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['quantity'] * $item['unit_price']
                ]);
            }

            // Update kredit supplier
            $newRemainingAmount = $validated['total'] - $supplierCredit->paid_amount;
            $supplierCredit->update([
                'supplier_id' => $validated['supplier_id'],
                'total_credit' => $validated['total'],
                'remaining_amount' => max(0, $newRemainingAmount),
                'due_date' => $validated['due_date'],
                'notes' => $validated['notes'],
                'status' => $newRemainingAmount <= 0 ? 'inactive' : 'active'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Kredit supplier berhasil diperbarui'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui kredit: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hapus kredit supplier
     */
    public function destroy(SupplierCredit $supplierCredit)
    {
        if ($supplierCredit->paid_amount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Kredit yang sudah ada pembayaran tidak dapat dihapus'
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Hapus pembayaran yang terkait
            $supplierCredit->payments()->delete();
            
            // Update status purchase menjadi canceled
            if ($supplierCredit->purchase) {
                $supplierCredit->purchase->update(['status' => 'canceled']);
            }

            $supplierCredit->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Kredit supplier berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus kredit: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Proses pembayaran kredit
     */
    public function processPayment(Request $request, SupplierCredit $supplierCredit)
    {
        $validated = $request->validate([
            'payment_amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,transfer,check',
            'notes' => 'nullable|string|max:500',
            'reference_number' => 'nullable|string|max:100'
        ]);

        if ($supplierCredit->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Kredit ini sudah tidak aktif'
            ], 422);
        }

        if ($validated['payment_amount'] > $supplierCredit->remaining_amount) {
            return response()->json([
                'success' => false,
                'message' => 'Jumlah pembayaran melebihi sisa tagihan'
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Buat record pembayaran
            $payment = SupplierCreditPayment::create([
                'supplier_credit_id' => $supplierCredit->id,
                'payment_amount' => $validated['payment_amount'],
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'],
                'reference_number' => $validated['reference_number'],
                'notes' => $validated['notes'],
                'user_id' => Auth::id()
            ]);

            // Update kredit supplier
            $newPaidAmount = $supplierCredit->paid_amount + $validated['payment_amount'];
            $newRemainingAmount = $supplierCredit->total_credit - $newPaidAmount;
            
            $supplierCredit->update([
                'paid_amount' => $newPaidAmount,
                'remaining_amount' => $newRemainingAmount,
                'status' => $newRemainingAmount <= 0 ? 'inactive' : 'active'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pembayaran berhasil diproses',
                'data' => [
                    'payment_id' => $payment->id,
                    'remaining_amount' => $newRemainingAmount,
                    'status' => $supplierCredit->status
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

    /**
     * Kirim reminder pembayaran
     */
    public function sendReminder(SupplierCredit $supplierCredit)
    {
        if ($supplierCredit->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Kredit ini sudah tidak aktif'
            ], 422);
        }

        try {
            // Implementasi pengiriman reminder (email, SMS, WhatsApp, dll)
            // Untuk sementara hanya log atau update last_reminder_sent
            
            $supplierCredit->update([
                'last_reminder_sent' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reminder berhasil dikirim ke supplier'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim reminder: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get laporan kredit supplier
     */
    public function getReport(Request $request)
    {
        $validated = $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'status' => 'nullable|in:active,inactive'
        ]);

        $query = SupplierCredit::with('supplier')
                              ->whereBetween('created_at', [$validated['date_from'], $validated['date_to']]);

        if ($validated['supplier_id']) {
            $query->where('supplier_id', $validated['supplier_id']);
        }

        if ($validated['status']) {
            $query->where('status', $validated['status']);
        }

        $credits = $query->get()->map(function ($credit) {
            $daysOverdue = $credit->due_date < today() ? today()->diffInDays($credit->due_date) : 0;
            
            return [
                'credit_number' => $credit->credit_number,
                'supplier_name' => $credit->supplier->name,
                'total_credit' => $credit->total_credit,
                'paid_amount' => $credit->paid_amount,
                'remaining_amount' => $credit->remaining_amount,
                'due_date' => $credit->due_date,
                'days_overdue' => $daysOverdue,
                'status' => $credit->status,
                'created_at' => $credit->created_at
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $credits
        ]);
    }

    /**
     * Get kredit yang jatuh tempo
     */
    public function getDueCredits()
    {
        $dueCredits = SupplierCredit::with('supplier')
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
     * Konfirmasi penerimaan barang (untuk kredit)
     */
    public function confirmReceipt(SupplierCredit $supplierCredit)
    {
        if ($supplierCredit->purchase->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Pembelian sudah dikonfirmasi sebelumnya'
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Update status purchase
            $supplierCredit->purchase->update(['status' => 'completed']);

            // Update stok produk jika belum diupdate
            foreach ($supplierCredit->purchase->purchaseItems as $item) {
                $product = $item->product;
                $oldStock = $product->current_stock;
                $newStock = $oldStock + $item->quantity;
                
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
                    'reference_id' => $supplierCredit->purchase->id,
                    'notes' => "Pembelian Kredit - Invoice: {$supplierCredit->purchase->invoice_number}"
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Penerimaan barang berhasil dikonfirmasi'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengkonfirmasi penerimaan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get status pembayaran berdasarkan tanggal jatuh tempo
     */
    private function getPaymentStatus($credit, $daysUntilDue)
    {
        if ($credit->remaining_amount <= 0) {
            return 'Lunas';
        } elseif ($daysUntilDue < 0) {
            return 'Jatuh Tempo';
        } elseif ($daysUntilDue <= 7) {
            return 'Belum Lunas';
        } else {
            return 'Belum Lunas';
        }
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
}