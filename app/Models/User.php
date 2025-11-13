<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }
    public function stockTransactions()
    {
        return $this->hasMany(StockTransaction::class);
    }
    public function stockOpnames()
    {
        return $this->hasMany(StockOpname::class);
    }
    public function supplierCreditPayments()
    {
        return $this->hasMany(SupplierCreditPayment::class);
    }
    public function supplierCredits()
    {
        return $this->hasManyThrough(
            SupplierCredit::class,
            Purchase::class,
            'user_id',
            'purchase_id',
            'id',
            'id'
        );
    }
    public function getSalesTodayAttribute()
    {
        return $this->sales()->whereDate('sale_date', today())->count();
    }
    public function getSalesThisMonthAttribute()
    {
        return $this->sales()->thisMonth()->count();
    }
    public function getTotalSalesAmountAttribute()
    {
        return $this->sales()->completed()->sum('total');
    }
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

