<?php

namespace App\Http\Controllers;

use App\Models\CustomerCredit;
use App\Models\Customer;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class CustomerCreditController extends Controller
{
    /**
     * Display a listing of customer credits
     */
    public function index(Request $request)
    {
        $query = CustomerCredit::with(['customer', 'sale'])
            ->orderBy('created_at', 'desc');

        // Filter by search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('credit_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($customerQuery) use ($search) {
                      $customerQuery->where('name', 'like', "%{$search}%")
                                  ->orWhere('code', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('created_at', [
                $request->date_from,
                $request->date_to
            ]);
        }

        // Sorting
        $sortField = $request->get('sort_field', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $credits = $query->paginate(15);

        // Statistics
        $stats = [
            'total_credits' => CustomerCredit::count(),
            'active_credits' => CustomerCredit::where('status', 'active')->count(),
            'paid_credits' => CustomerCredit::where('status', 'paid')->count(),
            'overdue_credits' => CustomerCredit::where('status', 'overdue')->count(),
            'total_credit_amount' => CustomerCredit::where('status', 'active')->sum('remaining_amount'),
            'overdue_amount' => CustomerCredit::where('status', 'overdue')->sum('remaining_amount')
        ];

        return view('customer-credits.index', compact('credits', 'stats'));
    }

    /**
     * Show the form for creating a new customer credit
     */
    public function create()
    {
        $customers = Customer::where('is_active', true)->get();
        $sales = Sale::where('payment_method', 'credit')
                    ->where('status', 'completed')
                    ->whereDoesntHave('customerCredit')
                    ->get();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'customers' => $customers,
                    'sales' => $sales
                ]
            ]);
        }

        return view('customer-credits.create', compact('customers', 'sales'));
    }

    /**
     * Store a newly created customer credit
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'sale_id' => 'nullable|exists:sales,id',
            'total_credit' => 'required|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'due_date' => 'required|date|after:today',
            'notes' => 'nullable|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            $validated['paid_amount'] = $validated['paid_amount'] ?? 0;
            $validated['remaining_amount'] = $validated['total_credit'] - $validated['paid_amount'];
            $validated['status'] = 'active';

            $credit = CustomerCredit::create($validated);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Kredit customer berhasil ditambahkan',
                'data' => $credit->load(['customer', 'sale'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan kredit customer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified customer credit
     */
    public function show(CustomerCredit $customerCredit)
    {
        $customerCredit->load(['customer', 'sale.saleItems.product']);

        // Get payment history (simulasi - nanti bisa dibuat tabel terpisah)
        $paymentHistory = [
            [
                'date' => $customerCredit->created_at->format('d/m/Y'),
                'amount' => $customerCredit->paid_amount,
                'remaining' => $customerCredit->remaining_amount,
                'method' => 'Transfer Bank',
                'notes' => 'Pembayaran pertama'
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'credit' => $customerCredit,
                'payment_history' => $paymentHistory
            ]
        ]);
    }

    /**
     * Show the form for editing customer credit
     */
    public function edit(CustomerCredit $customerCredit)
    {
        $customers = Customer::where('is_active', true)->get();
        $sales = Sale::where('payment_method', 'credit')
                    ->where('status', 'completed')
                    ->where(function ($query) use ($customerCredit) {
                        $query->whereDoesntHave('customerCredit')
                              ->orWhere('id', $customerCredit->sale_id);
                    })
                    ->get();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'credit' => $customerCredit,
                    'customers' => $customers,
                    'sales' => $sales
                ]
            ]);
        }

        return view('customer-credits.edit', compact('customerCredit', 'customers', 'sales'));
    }

    /**
     * Update the specified customer credit
     */
    public function update(Request $request, CustomerCredit $customerCredit)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'sale_id' => 'nullable|exists:sales,id',
            'total_credit' => 'required|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'due_date' => 'required|date',
            'status' => ['required', Rule::in(['active', 'paid', 'overdue'])],
            'notes' => 'nullable|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            $validated['paid_amount'] = $validated['paid_amount'] ?? 0;
            $validated['remaining_amount'] = $validated['total_credit'] - $validated['paid_amount'];

            $customerCredit->update($validated);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Kredit customer berhasil diperbarui',
                'data' => $customerCredit->load(['customer', 'sale'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui kredit customer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified customer credit
     */
    public function destroy(CustomerCredit $customerCredit)
    {
        try {
            // Check if credit has been paid partially
            if ($customerCredit->paid_amount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kredit yang sudah ada pembayaran tidak dapat dihapus'
                ], 422);
            }

            $customerCredit->delete();

            return response()->json([
                'success' => true,
                'message' => 'Kredit customer berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus kredit customer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Accept payment for customer credit
     */
    public function acceptPayment(Request $request, CustomerCredit $customerCredit)
    {
        $validated = $request->validate([
            'payment_amount' => 'required|numeric|min:0|max:' . $customerCredit->remaining_amount,
            'payment_method' => 'required|in:cash,transfer,debit,credit',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            // Calculate new amounts
            $newPaidAmount = floatval($customerCredit->paid_amount) + floatval($validated['payment_amount']);
            $newRemainingAmount = floatval($customerCredit->total_credit) - $newPaidAmount;
            
            // Determine new status
            $newStatus = $newRemainingAmount <= 0 ? 'paid' : 'active';

            // USE RAW QUERY TO BYPASS MODEL EVENTS
            DB::table('customer_credits')
                ->where('id', $customerCredit->id)
                ->update([
                    'paid_amount' => $newPaidAmount,
                    'remaining_amount' => max(0, $newRemainingAmount),
                    'status' => $newStatus,
                    'updated_at' => now()
                ]);

            // Log payment for history
            \Log::info('Customer Credit Payment', [
                'credit_id' => $customerCredit->id,
                'customer' => $customerCredit->customer->name,
                'payment_amount' => $validated['payment_amount'],
                'payment_method' => $validated['payment_method'],
                'payment_date' => $validated['payment_date'],
                'new_paid_amount' => $newPaidAmount,
                'new_remaining' => $newRemainingAmount,
                'status' => $newStatus
            ]);

            DB::commit();

            // Refresh model to get updated data
            $customerCredit = $customerCredit->fresh(['customer']);

            return response()->json([
                'success' => true,
                'message' => 'Pembayaran berhasil diterima',
                'data' => $customerCredit
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Accept Payment Error: ' . $e->getMessage());
            \Log::error('Stack Trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal menerima pembayaran: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send payment reminder
     */
    public function sendReminder(CustomerCredit $customerCredit)
    {
        try {
            // TODO: Implement SMS/WhatsApp/Email sending logic
            
            // For now, just return success
            return response()->json([
                'success' => true,
                'message' => 'Pengingat pembayaran berhasil dikirim ke ' . $customerCredit->customer->phone
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim pengingat: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customer credit info
     */
    public function getCustomerCreditInfo(Customer $customer)
    {
        $credits = $customer->customerCredits()
                          ->where('status', 'active')
                          ->with('sale')
                          ->get();

        $totalCredit = $credits->sum('remaining_amount');
        $overdueCredit = $credits->where('due_date', '<', now())->sum('remaining_amount');

        return response()->json([
            'success' => true,
            'data' => [
                'credits' => $credits,
                'total_credit' => $totalCredit,
                'overdue_credit' => $overdueCredit
            ]
        ]);
    }

    /**
     * Export customer credits data
     */
    public function export(Request $request)
    {
        $query = CustomerCredit::with(['customer', 'sale']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $credits = $query->orderBy('created_at', 'desc')->get();

        $data = $credits->map(function ($credit) {
            return [
                'Nomor Kredit' => $credit->credit_number,
                'Tanggal' => $credit->created_at->format('d/m/Y'),
                'Customer' => $credit->customer->name,
                'Kode Customer' => $credit->customer->code,
                'Total Kredit' => $credit->total_credit,
                'Dibayar' => $credit->paid_amount,
                'Sisa' => $credit->remaining_amount,
                'Jatuh Tempo' => $credit->due_date->format('d/m/Y'),
                'Status' => ucfirst($credit->status),
                'Catatan' => $credit->notes ?? '-'
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'filename' => 'kredit_customer_' . date('Y-m-d_H-i-s') . '.xlsx'
        ]);
    }
}