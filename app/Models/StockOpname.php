<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockOpname extends Model
{
    use HasFactory;

    protected $fillable = [
        'opname_number',
        'product_id',
        'opname_date',
        'system_stock',
        'physical_stock',
        'difference',
        'status',
        'notes',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'opname_date' => 'date',
            'system_stock' => 'integer',
            'physical_stock' => 'integer',
            'difference' => 'integer',
        ];
    }

    /**
     * Relasi dengan Product
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relasi dengan User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi polymorphic dengan StockTransaction
     */
    public function stockTransactions()
    {
        return $this->morphMany(StockTransaction::class, 'reference');
    }

    /**
     * Boot method untuk auto-generate nomor opname dan calculate difference
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate opname number pada saat create
        static::creating(function ($opname) {
            if (empty($opname->opname_number)) {
                $opname->opname_number = static::generateOpnameNumber();
            }
            
            // Calculate difference
            $opname->difference = $opname->system_stock - $opname->physical_stock;
            
            // Set default status jika belum diset
            if (empty($opname->status)) {
                $opname->status = 'pending';
            }
        });

        // Recalculate difference pada saat update
        static::updating(function ($opname) {
            $opname->difference = $opname->system_stock - $opname->physical_stock;
        });
    }

    /**
     * Generate nomor opname otomatis
     */
    public static function generateOpnameNumber()
    {
        $prefix = 'OPN';
        $date = date('Ymd');
        $sequence = static::whereDate('created_at', today())->count() + 1;
        
        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Scope untuk filter by status
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope untuk filter by approved
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope untuk filter by rejected
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope untuk filter by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('opname_date', [$startDate, $endDate]);
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeColorAttribute()
    {
        return match($this->status) {
            'pending' => 'yellow',
            'approved' => 'green',
            default => 'gray'
        };
    }

    /**
     * Get difference status (surplus/minus/match)
     */
    public function getDifferenceStatusAttribute()
    {
        if ($this->difference == 0) {
            return 'match';
        } elseif ($this->difference > 0) {
            return 'minus'; // Fisik kurang dari sistem
        } else {
            return 'surplus'; // Fisik lebih dari sistem
        }
    }
}