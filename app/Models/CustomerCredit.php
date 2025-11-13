<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomerCredit extends Model
{
    use HasFactory;

    protected $fillable = [
        'credit_number',
        'customer_id',
        'sale_id',
        'total_credit',
        'paid_amount',
        'remaining_amount',
        'due_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'total_credit' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
            'due_date' => 'date',
        ];
    }

    /**
     * Relasi ke Customer
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relasi ke Sale
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Accessor untuk cek apakah kredit overdue
     */
    public function getOverdueAttribute()
    {
        return $this->due_date < today() && $this->status === 'active';
    }

    /**
     * Accessor untuk format currency total credit
     */
    public function getFormattedTotalCreditAttribute()
    {
        return 'Rp ' . number_format($this->total_credit, 0, ',', '.');
    }

    /**
     * Accessor untuk format currency paid amount
     */
    public function getFormattedPaidAmountAttribute()
    {
        return 'Rp ' . number_format($this->paid_amount, 0, ',', '.');
    }

    /**
     * Accessor untuk format currency remaining amount
     */
    public function getFormattedRemainingAmountAttribute()
    {
        return 'Rp ' . number_format($this->remaining_amount, 0, ',', '.');
    }

    /**
     * Accessor untuk payment progress percentage
     */
    public function getPaymentProgressAttribute()
    {
        if ($this->total_credit <= 0) {
            return 0;
        }
        return round(($this->paid_amount / $this->total_credit) * 100, 2);
    }

    /**
     * Accessor untuk status badge class
     */
    public function getStatusBadgeAttribute()
    {
        $badges = [
            'active' => 'orange',
            'paid' => 'green',
            'overdue' => 'red'
        ];
        return $badges[$this->status] ?? 'gray';
    }

    /**
     * Accessor untuk status text
     */
    public function getStatusTextAttribute()
    {
        $statuses = [
            'active' => 'Belum Lunas',
            'paid' => 'Lunas',
            'overdue' => 'Jatuh Tempo'
        ];
        return $statuses[$this->status] ?? 'Unknown';
    }

    /**
     * Scope untuk kredit aktif
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope untuk kredit yang sudah lunas
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope untuk kredit yang overdue
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue')
                    ->orWhere(function ($q) {
                        $q->where('status', 'active')
                          ->where('due_date', '<', today());
                    });
    }

    /**
     * Scope untuk kredit berdasarkan customer
     */
    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Boot method untuk auto-generate credit number dan update status
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($credit) {
            // Auto-generate credit number
            if (empty($credit->credit_number)) {
                $credit->credit_number = 'CRD-' . date('Ymd') . '-' . str_pad(
                    static::whereDate('created_at', today())->count() + 1,
                    4,
                    '0',
                    STR_PAD_LEFT
                );
            }

            // Calculate remaining amount if not set
            if (!isset($credit->remaining_amount)) {
                $credit->remaining_amount = floatval($credit->total_credit) - floatval($credit->paid_amount ?? 0);
            }

            // Set initial status if not set
            if (!isset($credit->status) || empty($credit->status)) {
                if ($credit->remaining_amount <= 0) {
                    $credit->status = 'paid';
                } elseif ($credit->due_date < today()) {
                    $credit->status = 'overdue';
                } else {
                    $credit->status = 'active';
                }
            }
        });

        static::updating(function ($credit) {
            // ONLY recalculate if total_credit or paid_amount changed
            // AND remaining_amount is NOT dirty (not manually set)
            if (($credit->isDirty('total_credit') || $credit->isDirty('paid_amount')) 
                && !$credit->isDirty('remaining_amount')) {
                $credit->remaining_amount = floatval($credit->total_credit) - floatval($credit->paid_amount ?? 0);
            }

            // ONLY auto-update status if status is NOT already being changed manually
            if (!$credit->isDirty('status')) {
                if ($credit->remaining_amount <= 0) {
                    $credit->status = 'paid';
                } elseif ($credit->due_date < today()) {
                    $credit->status = 'overdue';
                }
            }
        });

        static::saving(function ($credit) {
            // Final validation: Ensure status is valid
            $validStatuses = ['active', 'paid', 'overdue'];
            if (!in_array($credit->status, $validStatuses)) {
                $credit->status = 'active';
            }
            
            // Ensure remaining_amount is not negative
            if ($credit->remaining_amount < 0) {
                $credit->remaining_amount = 0;
            }
        });
    }
}