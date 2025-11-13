<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'category_id',
        'unit',
        'purchase_price',
        'selling_price',
        'current_stock',
        'minimum_stock',
        'location',
        'description',
        'is_active',
        'supplier' // Tambahan untuk menyimpan nama supplier
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'current_stock' => 'integer',
        'minimum_stock' => 'integer',
        'is_active' => 'boolean'
    ];

    protected static function boot()
    {
        parent::boot();

        // Auto generate product code if not provided
        static::creating(function ($product) {
            if (empty($product->code)) {
                $lastProduct = static::latest('id')->first();
                $nextId = $lastProduct ? $lastProduct->id + 1 : 1;
                $product->code = 'BR' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
            }

            // Set default minimum stock
            if (is_null($product->minimum_stock)) {
                $product->minimum_stock = 10;
            }
        });
    }
    

    // Relasi ke Category
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // Relasi ke StockTransaction
    public function stockTransactions(): HasMany
    {
        return $this->hasMany(StockTransaction::class);
    }

    // Relasi ke SaleItem
    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    // Relasi ke PurchaseItem
    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function creditPurchaseItems()
    {
        return $this->hasMany(PurchaseItem::class)
                    ->whereHas('purchase.supplierCredit', function ($q) {
                        $q->where('status', 'active');
                    });
    }

    public function getTotalCreditQuantityAttribute()
    {
        return $this->creditPurchaseItems()->sum('quantity');
    }

    // Accessor untuk format currency
    public function getFormattedPurchasePriceAttribute(): string
    {
        return 'Rp ' . number_format($this->purchase_price, 0, ',', '.');
    }

    public function getFormattedSellingPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->selling_price, 0, ',', '.');
    }

    // Accessor untuk status stok
    public function getStockStatusAttribute()
    {
        if ($this->current_stock == 0) {
            return 'habis';
        } elseif ($this->current_stock <= $this->minimum_stock) {
            return 'kritis';
        } elseif ($this->current_stock <= ($this->minimum_stock * 2)) {
            return 'menipis';
        } else {
            return 'normal';
        }
    }

    public function getStockStatusBadgeAttribute()
    {
        $badges = [
            'habis' => 'danger',
            'kritis' => 'warning',
            'menipis' => 'info',
            'normal' => 'success'
        ];

        return $badges[$this->stock_status] ?? 'secondary';
    }

    public function getStockStatusTextAttribute()
    {
        $statusText = [
            'habis' => 'Habis',
            'kritis' => 'Kritis',
            'menipis' => 'Menipis',
            'normal' => 'Normal'
        ];

        return $statusText[$this->stock_status] ?? 'Unknown';
    }

    // Accessor untuk profit margin
    public function getProfitMarginAttribute(): float
    {
        if ($this->purchase_price > 0) {
            return (($this->selling_price - $this->purchase_price) / $this->purchase_price) * 100;
        }
        return 0;
    }

    // Accessor untuk nilai stok
    public function getStockValueAttribute(): float
    {
        return $this->current_stock * $this->purchase_price;
    }

    // Accessor untuk potential profit
    public function getPotentialProfitAttribute(): float
    {
        return $this->current_stock * ($this->selling_price - $this->purchase_price);
    }

    // Method untuk cek ketersediaan stok
    public function hasStock($quantity = 1): bool
    {
        return $this->current_stock >= $quantity;
    }

    // Method untuk cek stok rendah
    public function isLowStock(): bool
    {
        return $this->current_stock <= $this->minimum_stock;
    }

    public function getLatestPurchasePriceAttribute()
    {
        $latestPurchaseItem = $this->purchaseItems()
            ->whereHas('purchase', function($q) {
                $q->where('status', 'completed');
            })
            ->latest()
            ->first();
            
        return $latestPurchaseItem ? $latestPurchaseItem->unit_price : $this->purchase_price;
    }

    // Method untuk cek stok habis
    public function isOutOfStock(): bool
    {
        return $this->current_stock <= 0;
    }

    // Method untuk cek stok kritis (kurang dari minimum)
    public function isCriticalStock(): bool
    {
        return $this->current_stock > 0 && $this->current_stock <= $this->minimum_stock;
    }

    // Method untuk cek stok menipis (2x minimum stock)
    public function isRunningLowStock(): bool
    {
        return $this->current_stock > $this->minimum_stock && $this->current_stock <= ($this->minimum_stock * 2);
    }

    // Scope untuk produk dengan stok tersedia
    public function scopeAvailable($query)
    {
        return $query->where('current_stock', '>', 0);
    }

    // Scope untuk pencarian produk berdasarkan nama atau kode
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
            ->orWhere('code', 'like', "%{$search}%");
        });
    }
    
    // Scope untuk produk dengan stok rendah
    public function scopeLowStock($query)
    {
        return $query->whereColumn('current_stock', '<=', 'minimum_stock');
    }

    // Scope untuk produk habis
    public function scopeOutOfStock($query)
    {
        return $query->where('current_stock', '<=', 0);
    }

    // Scope untuk produk aktif
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope untuk produk berdasarkan kategori
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    // Scope untuk filter berdasarkan status stok
    public function scopeByStockStatus($query, $status)
    {
        switch ($status) {
            case 'habis':
                return $query->where('current_stock', 0);
            case 'kritis':
                return $query->whereColumn('current_stock', '<=', 'minimum_stock')
                            ->where('current_stock', '>', 0);
            case 'menipis':
                return $query->whereRaw('current_stock <= (minimum_stock * 2)')
                            ->whereColumn('current_stock', '>', 'minimum_stock');
            case 'normal':
                return $query->whereRaw('current_stock > (minimum_stock * 2)');
            default:
                return $query;
        }
    }

    // Method untuk validasi stok sebelum transaksi
    public function validateStock($quantity): bool
    {
        return $this->current_stock >= $quantity && $this->is_active;
    }

    // Method untuk mengurangi stok
    public function reduceStock($quantity): bool
    {
        if (!$this->validateStock($quantity)) {
            return false;
        }

        $this->decrement('current_stock', $quantity);
        return true;
    }

    // Method untuk mengembalikan stok
    public function restoreStock($quantity): void
    {
        $this->increment('current_stock', $quantity);
    }

    // Method untuk mendapatkan transaksi stok terakhir
    public function getLastStockTransaction()
    {
        return $this->stockTransactions()->latest()->first();
    }

    // Method untuk mendapatkan riwayat stok dalam periode tertentu
    public function getStockHistory($days = 30)
    {
        return $this->stockTransactions()
                    ->where('created_at', '>=', now()->subDays($days))
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    // Method untuk update stok
    public function updateStock($quantity, $type = 'set', $notes = null, $userId = null)
    {
        $oldStock = $this->current_stock;
        
        switch ($type) {
            case 'add':
                $newStock = $oldStock + $quantity;
                break;
            case 'subtract':
                $newStock = max(0, $oldStock - $quantity);
                $quantity = $oldStock - $newStock; // Adjust quantity
                break;
            case 'set':
            default:
                $newStock = $quantity;
                $quantity = $newStock - $oldStock; // Difference
                break;
        }

        // Update stock
        $this->update(['current_stock' => $newStock]);

        // Create stock transaction
        $transactionType = $quantity > 0 ? 'in' : ($quantity < 0 ? 'out' : 'adjustment');
        
        $this->stockTransactions()->create([
            'transaction_code' => 'STK' . date('Ymd') . str_pad(StockTransaction::count() + 1, 4, '0', STR_PAD_LEFT),
            'type' => $transactionType,
            'quantity' => abs($quantity),
            'stock_before' => $oldStock,
            'stock_after' => $newStock,
            'price' => $this->purchase_price,
            'reference_type' => 'manual_adjustment',
            'notes' => $notes ?? "Stock {$type} via system",
            'user_id' => $userId ?? auth()->id()
        ]);

        return $this;
    }
}