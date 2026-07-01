<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'cashier_user_id',
        'transaction_datetime',
        'payment_method',
        'subtotal_before_discount',
        'discount_amount',
        'total_amount',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'transaction_datetime' => 'datetime',
            'subtotal_before_discount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Get the cashier who processed this transaction.
     */
    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_user_id');
    }

    /**
     * Get the line items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }
}
