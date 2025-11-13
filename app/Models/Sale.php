<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Carbon\Carbon;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'sale_date',
        'subtotal',
        'discount',
        'tax',
        'total',
        'payment_method',
        'status',
        'notes',
        'user_id',
        'paid_amount',
        'change',
    ];

    protected $casts = [
        'sale_date' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',    
        'change' => 'decimal:2',         
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relasi ke Customer
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // Relasi ke User (kasir/staff yang melakukan transaksi)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke SaleItem
    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    // Relasi ke CustomerCredit (untuk pembayaran kredit)
    public function customerCredit(): HasOne
    {
        return $this->hasOne(CustomerCredit::class);
    }

    // Relasi ke StockTransaction
    public function stockTransactions(): HasMany
    {
        return $this->hasMany(StockTransaction::class, 'reference_id')
                    ->where('reference_type', 'sale');
    }

    // Accessor untuk mendapatkan status text
    public function getStatusTextAttribute(): string
    {
        $statusText = [
            'pending' => 'Pending',
            'completed' => 'Selesai',
            'canceled' => 'Dibatalkan'
        ];

        return $statusText[$this->status] ?? 'Unknown';
    }

    /**
     * Accessor untuk mendapatkan payment_progress jika ada kredit
     */
    public function getPaymentProgressAttribute(): float
    {
        if (!$this->customerCredit) {
            return 0;
        }
        
        if ($this->customerCredit->total_credit <= 0) {
            return 100;
        }
        
        return ($this->customerCredit->paid_amount / $this->customerCredit->total_credit) * 100;
    }

    /**
     * Method untuk mendapatkan detail pembayaran
     */
    public function getPaymentDetailsAttribute(): array
    {
        $details = [
            'method' => $this->payment_method,
            'method_text' => $this->payment_method_text,
            'status' => $this->status,
            'status_text' => $this->status_text
        ];

        if ($this->payment_method === 'credit' && $this->customerCredit) {
            $details['credit'] = [
                'credit_number' => $this->customerCredit->credit_number,
                'total_credit' => $this->customerCredit->total_credit,
                'paid_amount' => $this->customerCredit->paid_amount,
                'remaining_amount' => $this->customerCredit->remaining_amount,
                'due_date' => $this->customerCredit->due_date,
                'payment_progress' => $this->payment_progress
            ];
        }

        return $details;
    }

    // Accessor untuk mendapatkan payment method text
    public function getPaymentMethodTextAttribute(): string
    {
        $methodText = [
            'cash' => 'Tunai',
            'credit' => 'Kredit',
            'transfer' => 'Transfer'
        ];

        return $methodText[$this->payment_method] ?? 'Unknown';
    }

    // Accessor untuk mendapatkan badge status
    public function getStatusBadgeAttribute(): string
    {
        $badges = [
            'pending' => 'warning',
            'completed' => 'success',
            'canceled' => 'danger'
        ];

        return $badges[$this->status] ?? 'secondary';
    }

    // Accessor untuk format currency
    public function getFormattedSubtotalAttribute(): string
    {
        return 'Rp ' . number_format($this->subtotal, 0, ',', '.');
    }

    public function getFormattedDiscountAttribute(): string
    {
        return 'Rp ' . number_format($this->discount, 0, ',', '.');
    }

    public function getFormattedTaxAttribute(): string
    {
        return 'Rp ' . number_format($this->tax, 0, ',', '.');
    }

    public function getFormattedTotalAttribute(): string
    {
        return 'Rp ' . number_format($this->total, 0, ',', '.');
    }

    // Method untuk mendapatkan total item quantity
    public function getTotalQuantityAttribute(): int
    {
        return $this->saleItems->sum('quantity');
    }

    /**
     * Method untuk mendapatkan total profit dari transaksi
     */
    public function getTotalProfitAttribute(): float
    {
        $totalProfit = 0;
        
        foreach ($this->saleItems as $item) {
            if ($item->product) {
                $profit = ($item->unit_price - $item->product->purchase_price) * $item->quantity;
                $totalProfit += $profit;
            }
        }
        
        return $totalProfit;
    }

    // Method untuk cek apakah bisa di-edit
    public function canBeEdited(): bool
    {
        return $this->status === 'pending';
    }

    // Method untuk cek apakah bisa di-cancel
    public function canBeCanceled(): bool
    {
        return in_array($this->status, ['pending', 'completed']) && 
               $this->created_at->diffInHours(now()) <= 24;
    }

    /**
     * Method untuk cek apakah transaksi bisa dicetak
     */
    public function canBePrintedAttribute(): bool
    {
        return in_array($this->status, ['completed', 'pending']);
    }

    // Method untuk cek apakah memiliki kredit
    public function hasCreditAttribute(): bool
    {
        return $this->payment_method === 'credit' && $this->customerCredit;
    }

    // Method untuk mendapatkan sisa kredit
    public function getRemainingCreditAttribute(): float
    {
        if (!$this->has_credit) {
            return 0;
        }
        
        return $this->customerCredit->remaining_amount ?? 0;
    }

    // Method untuk mendapatkan status kredit
    public function getCreditStatusAttribute(): ?string
    {
        if (!$this->has_credit) {
            return null;
        }
        
        return $this->customerCredit->status ?? null;
    }

    // Method untuk mendapatkan profit margin
    public function getProfitAttribute(): float
    {
        $totalProfit = 0;
        
        foreach ($this->saleItems as $item) {
            $profit = ($item->unit_price - $item->product->purchase_price) * $item->quantity;
            $totalProfit += $profit;
        }
        
        return $totalProfit;
    }

    // Method untuk mendapatkan profit margin percentage
    public function getProfitMarginAttribute(): float
    {
        if ($this->total > 0) {
            return ($this->profit / $this->total) * 100;
        }
        
        return 0;
    }

    /**
     * Method untuk mendapatkan profit margin percentage
     */
    public function getProfitMarginPercentageAttribute(): float
    {
        if ($this->total > 0) {
            return ($this->total_profit / $this->total) * 100;
        }
        
        return 0;
    }

    // Scope untuk filter berdasarkan tanggal
    public function scopeByDateRange($query, $dateFrom, $dateTo)
    {
        return $query->whereBetween('sale_date', [$dateFrom, $dateTo]);
    }

    // Scope untuk filter berdasarkan status
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // Scope untuk filter berdasarkan customer
    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    // Scope untuk filter berdasarkan payment method
    public function scopeByPaymentMethod($query, $paymentMethod)
    {
        return $query->where('payment_method', $paymentMethod);
    }

    // Scope untuk transaksi hari ini
    public function scopeToday($query)
    {
        return $query->whereDate('sale_date', today());
    }

    // Scope untuk transaksi bulan ini
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('sale_date', now()->month)
                    ->whereYear('sale_date', now()->year);
    }

    // Scope untuk transaksi dengan kredit
    public function scopeWithCredit($query)
    {
        return $query->whereHas('customerCredit');
    }

    /**
     * Scope untuk transaksi dengan nilai tertentu
     */
    public function scopeByAmountRange($query, $minAmount = null, $maxAmount = null)
    {
        if ($minAmount !== null) {
            $query->where('total', '>=', $minAmount);
        }
        
        if ($maxAmount !== null) {
            $query->where('total', '<=', $maxAmount);
        }
        
        return $query;
    }

    /**
     * Scope untuk transaksi yang memiliki diskon
     */
    public function scopeWithDiscount($query)
    {
        return $query->where('discount', '>', 0);
    }

    // Scope untuk transaksi dengan kredit aktif
    public function scopeWithActiveCredit($query)
    {
        return $query->whereHas('customerCredit', function ($q) {
            $q->where('status', 'active');
        });
    }

    // Scope untuk transaksi completed
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // Boot method untuk auto-generate invoice number
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($sale) {
            if (empty($sale->invoice_number)) {
                $sale->invoice_number = static::generateInvoiceNumber();
            }
        });
    }

    // Method untuk generate invoice number
    public static function generateInvoiceNumber(): string
    {
        $prefix = 'PJL';
        $date = date('Ymd');
        $lastSale = static::whereDate('created_at', today())->latest('id')->first();
        $sequence = $lastSale ? (intval(substr($lastSale->invoice_number, -3)) + 1) : 1;
        
        return $prefix . '-' . $date . '-' . str_pad($sequence, 3, '0', STR_PAD_LEFT);
    }

    // Method untuk mendapatkan total sales per periode
    public static function getTotalSalesByPeriod($dateFrom, $dateTo, $status = 'completed')
    {
        return static::whereBetween('sale_date', [$dateFrom, $dateTo])
                    ->where('status', $status)
                    ->sum('total');
    }

    // Method untuk mendapatkan jumlah transaksi per periode
    public static function getTransactionCountByPeriod($dateFrom, $dateTo, $status = 'completed')
    {
        return static::whereBetween('sale_date', [$dateFrom, $dateTo])
                    ->where('status', $status)
                    ->count();
    }

    // Method untuk mendapatkan rata-rata penjualan per periode
    public static function getAverageSalesByPeriod($dateFrom, $dateTo, $status = 'completed')
    {
        return static::whereBetween('sale_date', [$dateFrom, $dateTo])
                    ->where('status', $status)
                    ->avg('total') ?? 0;
    }

    // Method untuk mendapatkan top customers
    public static function getTopCustomers($limit = 10, $dateFrom = null, $dateTo = null)
    {
        $query = static::with('customer')
                      ->where('status', 'completed')
                      ->selectRaw('customer_id, SUM(total) as total_sales, COUNT(*) as transaction_count')
                      ->groupBy('customer_id')
                      ->orderByDesc('total_sales');

        if ($dateFrom && $dateTo) {
            $query->whereBetween('sale_date', [$dateFrom, $dateTo]);
        }

        return $query->limit($limit)->get();
    }

    // Method untuk mendapatkan sales trend
    public static function getSalesTrend($period = 'month', $limit = 6)
    {
        $query = static::where('status', 'completed');

        switch ($period) {
            case 'day':
                return $query->selectRaw('DATE(sale_date) as period, SUM(total) as total_sales, COUNT(*) as transaction_count')
                           ->groupBy('period')
                           ->orderBy('period', 'desc')
                           ->limit($limit)
                           ->get();
            case 'week':
                return $query->selectRaw('YEARWEEK(sale_date) as period, SUM(total) as total_sales, COUNT(*) as transaction_count')
                           ->groupBy('period')
                           ->orderBy('period', 'desc')
                           ->limit($limit)
                           ->get();
            case 'month':
            default:
                return $query->selectRaw('DATE_FORMAT(sale_date, "%Y-%m") as period, SUM(total) as total_sales, COUNT(*) as transaction_count')
                           ->groupBy('period')
                           ->orderBy('period', 'desc')
                           ->limit($limit)
                           ->get();
        }
    }

    /**
     * Method untuk mendapatkan ringkasan transaksi
     */
    public function getSummaryAttribute(): array
    {
        return [
            'invoice_number' => $this->invoice_number,
            'date' => $this->sale_date->format('d M Y'),
            'customer' => $this->customer->name ?? 'Guest',
            'items_count' => $this->saleItems->count(),
            'total_quantity' => $this->total_quantity,
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'tax' => $this->tax,
            'total' => $this->total,
            'payment_method' => $this->payment_method_text,
            'status' => $this->status_text,
            'profit' => $this->total_profit
        ];
    }

    /**
     * Scope untuk transaksi berdasarkan kasir
     */
    public function scopeByCashier($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope untuk transaksi hari ini berdasarkan kasir
     */
    public function scopeTodayByCashier($query, $userId)
    {
        return $query->whereDate('sale_date', today())
                    ->where('user_id', $userId);
    }

    /**
     * Method untuk mendapatkan top selling products dari transaksi
     */
    public static function getTopSellingProducts($limit = 10, $dateFrom = null, $dateTo = null)
    {
        $query = SaleItem::with('product')
                        ->whereHas('sale', function ($q) use ($dateFrom, $dateTo) {
                            $q->where('status', 'completed');
                            
                            if ($dateFrom && $dateTo) {
                                $q->whereBetween('sale_date', [$dateFrom, $dateTo]);
                            }
                        })
                        ->selectRaw('product_id, SUM(quantity) as total_quantity, SUM(total_price) as total_sales, COUNT(DISTINCT sale_id) as transaction_count')
                        ->groupBy('product_id')
                        ->orderByDesc('total_quantity');

        return $query->limit($limit)->get();
    }

    /**
     * Method untuk mendapatkan sales comparison
     */
    public static function getSalesComparison($currentPeriodStart, $currentPeriodEnd, $previousPeriodStart, $previousPeriodEnd)
    {
        $currentSales = static::whereBetween('sale_date', [$currentPeriodStart, $currentPeriodEnd])
                            ->where('status', 'completed')
                            ->sum('total');
        
        $previousSales = static::whereBetween('sale_date', [$previousPeriodStart, $previousPeriodEnd])
                            ->where('status', 'completed')
                            ->sum('total');
        
        $difference = $currentSales - $previousSales;
        $percentageChange = $previousSales > 0 ? ($difference / $previousSales) * 100 : 0;
        
        return [
            'current_sales' => $currentSales,
            'previous_sales' => $previousSales,
            'difference' => $difference,
            'percentage_change' => $percentageChange,
            'trend' => $difference >= 0 ? 'up' : 'down'
        ];
    }

    /**
     * Method untuk mendapatkan hourly sales trend
     */
    public static function getHourlySalesTrend($date = null)
    {
        $date = $date ?? today();
        
        return static::whereDate('sale_date', $date)
                    ->where('status', 'completed')
                    ->selectRaw('HOUR(sale_date) as hour, COUNT(*) as transaction_count, SUM(total) as total_sales')
                    ->groupBy('hour')
                    ->orderBy('hour')
                    ->get();
    }

    /**
     * Method untuk mendapatkan payment method distribution
     */
    public static function getPaymentMethodDistribution($dateFrom = null, $dateTo = null)
    {
        $query = static::where('status', 'completed');
        
        if ($dateFrom && $dateTo) {
            $query->whereBetween('sale_date', [$dateFrom, $dateTo]);
        }
        
        return $query->selectRaw('payment_method, COUNT(*) as transaction_count, SUM(total) as total_amount')
                    ->groupBy('payment_method')
                    ->get();
    }
}