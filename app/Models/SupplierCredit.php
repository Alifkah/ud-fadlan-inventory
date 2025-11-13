<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierCredit extends Model
{
    use HasFactory;

    protected $fillable = [
        'credit_number',
        'supplier_id',
        'purchase_id',
        'total_credit',
        'paid_amount',
        'remaining_amount',
        'due_date',
        'status',
        'notes',
        'last_reminder_sent'
    ];

    protected $casts = [
        'due_date' => 'date',
        'last_reminder_sent' => 'datetime',
        'total_credit' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2'
    ];

    /**
     * Relasi ke Supplier
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Relasi ke Purchase
     */
    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * Relasi ke SupplierCreditPayment
     */
    public function payments()
    {
        return $this->hasMany(SupplierCreditPayment::class);
    }

    /**
     * Scope untuk kredit aktif
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope untuk kredit yang jatuh tempo
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', today())->where('status', 'active');
    }

    /**
     * Scope untuk kredit yang akan jatuh tempo dalam X hari
     */
    public function scopeDueSoon($query, $days = 7)
    {
        return $query->where('status', 'active')
                    ->whereBetween('due_date', [today(), today()->addDays($days)]);
    }

    /**
     * Accessor untuk progress pembayaran dalam persen
     */
    public function getPaymentProgressAttribute()
    {
        if ($this->total_credit <= 0) {
            return 0;
        }
        
        return round(($this->paid_amount / $this->total_credit) * 100, 2);
    }

    /**
     * Accessor untuk menentukan apakah kredit overdue
     */
    public function getIsOverdueAttribute()
    {
        return $this->due_date < today() && $this->status === 'active';
    }

    /**
     * Accessor untuk menghitung hari keterlambatan
     */
    public function getDaysOverdueAttribute()
    {
        if (!$this->is_overdue) {
            return 0;
        }
        
        return today()->diffInDays($this->due_date);
    }

    public function getFormattedTotalCreditAttribute()
    {
        return 'Rp ' . number_format($this->total_credit, 0, ',', '.');
    }

    public function getFormattedRemainingAmountAttribute()
    {
        return 'Rp ' . number_format($this->remaining_amount, 0, ',', '.');
    }

    public function getFormattedPaidAmountAttribute()
    {
        return 'Rp ' . number_format($this->paid_amount, 0, ',', '.');
    }
}