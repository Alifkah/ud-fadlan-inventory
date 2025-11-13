<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Supplier extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'code',
        'name',
        'company_name',
        'address',
        'phone',
        'email',
        'address',
        'is_active',
        'credit_limit',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'credit_limit' => 'decimal:2',
        ];
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function supplierCredits()
    {
        return $this->hasMany(SupplierCredit::class);
    }

    public function activeSupplierCredits()
    {
        return $this->hasMany(SupplierCredit::class)->where('status', 'active');
    }

    public function creditPurchases()
    {
        return $this->hasMany(Purchase::class)->where('payment_method', 'credit');
    }

    public function getTotalActiveCreditAttribute()
    {
        return $this->activeSupplierCredits()->sum('remaining_amount');
    }

     public function getFullNameAttribute()
    {
        return $this->company_name ? $this->name . ' (' . $this->company_name . ')' : $this->name;
    }

    public function scopeWithActiveCredit($query)
    {
        return $query->whereHas('activeSupplierCredits');
    }

    public function getStatusTextAttribute()
    {
        return $this->is_active ? 'Aktif' : 'Tidak Aktif';
    }

    public function getStatusBadgeAttribute()
    {
        return $this->is_active ? 'success' : 'secondary';
    }

    public function getOverdueCreditCountAttribute()
    {
        return $this->activeSupplierCredits()
                    ->where('due_date', '<', today())
                    ->count();
    }
    
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($supplier) {
            if (empty($supplier->code)) {
                $supplier->code = 'SUP' . str_pad((string) static::count() + 1, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}
