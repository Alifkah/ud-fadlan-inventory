<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'email',
        'phone',
        'address',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    // Boot method untuk auto-generate code
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
            if (empty($customer->code)) {
                $lastCustomer = static::latest('id')->first();
                $nextId = $lastCustomer ? $lastCustomer->id + 1 : 1;
                $customer->code = 'CST' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    // Relasi ke Sales
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    // Relasi ke CustomerCredit
    public function customerCredits(): HasMany
    {
        return $this->hasMany(CustomerCredit::class);
    }

    // Accessor untuk total pembelian
    public function getTotalPurchasesAttribute(): float
    {
        return $this->sales()->where('status', 'completed')->sum('total');
    }

    // Accessor untuk total kredit aktif
    public function getActiveCreditAttribute(): float
    {
        return $this->customerCredits()->where('status', 'active')->sum('remaining_amount');
    }

    // Method untuk cek apakah customer memiliki kredit aktif
    public function hasActiveCredit(): bool
    {
        return $this->customerCredits()->where('status', 'active')->where('remaining_amount', '>', 0)->exists();
    }

    // Scope untuk customer aktif
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Accessor untuk status text
    public function getStatusTextAttribute(): string
    {
        return $this->is_active ? 'Aktif' : 'Tidak Aktif';
    }

    // Accessor untuk status badge
    public function getStatusBadgeAttribute(): string
    {
        return $this->is_active ? 'success' : 'secondary';
    }
}