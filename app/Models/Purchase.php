<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'supplier_id',
        'purchase_date',
        'subtotal',
        'discount',
        'tax',
        'total',
        'payment_method',
        'status',
        'notes',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function stockTransactions()
    {
        return $this->morphMany(StockTransaction::class, 'reference');
    }

    public function supplierCredit()
    {
        return $this->hasOne(SupplierCredit::class);
    }

    public function scopeWithCredit($query)
    {
        return $query->whereHas('supplierCredit');
    }

    public function scopeWithActiveCredit($query)
    {
        return $query->whereHas('supplierCredit', function ($q) {
            $q->where('status', 'active');
        });
    }

    public function getHasCreditAttribute()
    {
        return $this->payment_method === 'credit' && $this->supplierCredit;
    }

    public function getCreditStatusAttribute()
    {
        if (!$this->has_credit) {
            return null;
        }
        
        return $this->supplierCredit->status;
    }

    public function getRemainingCreditAttribute()
    {
        if (!$this->has_credit) {
            return 0;
        }
        
        return $this->supplierCredit->remaining_amount;
    }

    public function getFormattedTotalAttribute()
    {
        return 'Rp ' . number_format($this->total, 0, ',', '.');
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => 'warning',
            'completed' => 'success',
            'canceled' => 'danger'
        ];

        return $badges[$this->status] ?? 'secondary';
    }

    public function getStatusTextAttribute()
    {
        $statuses = [
            'pending' => 'Pending',
            'completed' => 'Selesai',
            'canceled' => 'Dibatalkan'
        ];

        return $statuses[$this->status] ?? 'Unknown';
    }

    public function getPaymentMethodTextAttribute()
    {
        $methods = [
            'cash' => 'Tunai',
            'credit' => 'Kredit',
            'transfer' => 'Transfer'
        ];

        return $methods[$this->payment_method] ?? 'Unknown';
    }

    public function hasCreditPayments()
    {
        return $this->supplierCredit && 
            $this->supplierCredit->payments()->count() > 0;
    }

    public function canBeDeleted()
    {
        // Tidak bisa hapus jika sudah completed atau ada pembayaran kredit
        if ($this->status === 'completed') {
            return false;
        }
        
        if ($this->hasCreditPayments()) {
            return false;
        }
        
        return true;
    }

    public function canBeEdited()
    {
        // Hanya bisa edit jika status pending dan belum ada pembayaran kredit
        if ($this->status !== 'pending') {
            return false;
        }
        
        if ($this->hasCreditPayments()) {
            return false;
        }
        
        return true;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($purchase) {
            if (empty($purchase->invoice_number)) {
                $purchase->invoice_number = 'PUR' . date('Ymd') . str_pad(static::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}
