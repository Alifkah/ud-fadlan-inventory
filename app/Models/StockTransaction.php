<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_code',
        'product_id',
        'type',
        'quantity',
        'stock_before',
        'stock_after',
        'price',
        'reference_type',
        'reference_id',
        'notes',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'stock_before' => 'integer',
            'stock_after' => 'integer',
            'price' => 'decimal:2'
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (empty($transaction->transaction_code)) {
                $transaction->transaction_code = static::generateTransactionCode();
            }
        });
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reference()
    {
        return $this->morphTo();
    }

    public function getTypeTextAttribute()
    {
        $types = [
            'in' => 'Masuk',
            'out' => 'Keluar',
            'adjustment' => 'Penyesuaian'
        ];

        return $types[$this->type] ?? 'Unknown';
    }

    public function getTypeBadgeAttribute()
    {
        $badges = [
            'in' => 'success',
            'out' => 'danger',
            'adjustment' => 'warning'
        ];

        return $badges[$this->type] ?? 'secondary';
    }

    public function getFormattedPriceAttribute()
    {
        return $this->price ? 'Rp ' . number_format($this->price, 0, ',', '.') : '-';
    }

    public function getReferenceTextAttribute()
    {
        $references = [
            'purchase' => 'Pembelian',
            'sale' => 'Penjualan',
            'adjustment' => 'Penyesuaian',
            'initial_stock' => 'Stok Awal',
            'manual_adjustment' => 'Penyesuaian Manual'
        ];

        return $references[$this->reference_type] ?? 'Lainnya';
    }

    // Scope untuk transaksi hari ini
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    // Scope untuk transaksi berdasarkan produk
    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    // Scope untuk filter berdasarkan tanggal
    public function scopeByDateRange($query, $dateFrom, $dateTo)
    {
        return $query->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo]);
    }

    // Scope untuk filter berdasarkan product
    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    // Scope untuk filter berdasarkan tipe transaksi
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Scope untuk filter berdasarkan reference type
    public function scopeByReference($query, $referenceType)
    {
        return $query->where('reference_type', $referenceType);
    }

    // Scope untuk filter berdasarkan user
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Method untuk mendapatkan impact (positive/negative)
    public function getImpactAttribute()
    {
        return $this->type === 'in' ? 'positive' : ($this->type === 'out' ? 'negative' : 'neutral');
    }

    // Method untuk mendapatkan quantity dengan sign
    public function getSignedQuantityAttribute()
    {
        $prefix = $this->type === 'in' ? '+' : ($this->type === 'out' ? '-' : '');
        return $prefix . $this->quantity;
    }

    // Method untuk format tanggal
    public function getFormattedDateAttribute()
    {
        return $this->created_at->format('d/m/Y H:i');
    }

    // Method untuk mendapatkan nama user
    public function getUserNameAttribute()
    {
        return $this->user ? $this->user->name : 'System';
    }

    // Method untuk generate kode transaksi unik
    public static function generateTransactionCode()
    {
        $prefix = 'STK';
        $date = date('Ymd');
        $sequence = static::whereDate('created_at', today())->count() + 1;
        
        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}