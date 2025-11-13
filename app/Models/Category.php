<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'icon',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    // Accessor untuk mendapatkan jumlah produk aktif
    public function getActiveProductsCountAttribute()
    {
        return $this->products()->where('is_active', true)->count();
    }

    // Accessor untuk mendapatkan total stok
    public function getTotalStockAttribute()
    {
        return $this->products()->where('is_active', true)->sum('current_stock');
    }

    // Accessor untuk mendapatkan produk dengan stok rendah
    public function getLowStockProductsCountAttribute()
    {
        return $this->products()
            ->where('is_active', true)
            ->whereColumn('current_stock', '<=', 'minimum_stock')
            ->count();
    }

    // Scope untuk kategori aktif
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Method untuk mendapatkan nilai total stok
    public function getTotalStockValue()
    {
        return $this->products()
            ->where('is_active', true)
            ->get()
            ->sum(function ($product) {
                return $product->current_stock * $product->purchase_price;
            });
    }
}