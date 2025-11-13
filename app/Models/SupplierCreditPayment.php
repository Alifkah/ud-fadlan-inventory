<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierCreditPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_credit_id',
        'payment_amount',
        'payment_date',
        'payment_method',
        'reference_number',
        'notes',
        'user_id'
    ];

    protected $casts = [
        'payment_date' => 'date',
        'payment_amount' => 'decimal:2'
    ];

    /**
     * Relasi ke SupplierCredit
     */
    public function supplierCredit()
    {
        return $this->belongsTo(SupplierCredit::class);
    }

    /**
     * Relasi ke User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}