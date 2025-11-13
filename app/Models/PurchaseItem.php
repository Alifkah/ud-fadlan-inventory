<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
        ];
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getFormattedUnitPriceAttribute()
    {
        return 'Rp ' . number_format($this->unit_price, 0, ',', '.');
    }

    public function getFormattedTotalPriceAttribute()
    {
        return 'Rp ' . number_format($this->total_price, 0, ',', '.');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($purchaseItem) {
            if (empty($purchaseItem->total_price)) {
                $purchaseItem->total_price = $purchaseItem->quantity * $purchaseItem->unit_price;
            }
        });

        static::updating(function ($purchaseItem) {
            $purchaseItem->total_price = $purchaseItem->quantity * $purchaseItem->unit_price;
        });
    }
}
