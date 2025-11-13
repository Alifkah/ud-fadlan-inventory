<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2'
    ];

    // Relasi ke Sale
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    // Relasi ke Product
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Boot method untuk menghitung total_price otomatis saat membuat atau mengupdate
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($saleItem) {
            $saleItem->total_price = $saleItem->quantity * $saleItem->unit_price;
        });

        static::updating(function ($saleItem) {
            $saleItem->total_price = $saleItem->quantity * $saleItem->unit_price;
        });
    }

    // Mutator untuk menghitung total_price otomatis
    public function setQuantityAttribute($value)
    {
        $this->attributes['quantity'] = $value;
        $this->calculateTotalPrice();
    }

    public function setUnitPriceAttribute($value)
    {
        $this->attributes['unit_price'] = is_string($value) ? 
            str_replace(['Rp', '.', ' '], '', $value) : $value;
        $this->calculateTotalPrice();
    }

    // Method untuk menghitung total price
    private function calculateTotalPrice()
    {
        if (isset($this->attributes['quantity']) && isset($this->attributes['unit_price'])) {
            $this->attributes['total_price'] = $this->attributes['quantity'] * $this->attributes['unit_price'];
        }
    }

    // Accessor untuk format currency display
    public function getFormattedUnitPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->unit_price, 0, ',', '.');
    }

    public function getFormattedTotalPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->total_price, 0, ',', '.');
    }

    // Method untuk mendapatkan profit per item
    public function getProfitAttribute(): float
    {
        return ($this->unit_price - $this->product->purchase_price) * $this->quantity;
    }

    // Scope untuk filter berdasarkan product_id
    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }
}