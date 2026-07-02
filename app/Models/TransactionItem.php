<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionItem extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'transaction_id',
        'product_id',
        'unit_name_at_transaction',
        'qty_in_selected_unit',
        'conversion_factor_at_transaction',
        'price_per_unit_at_transaction',
        'cost_price_per_base_unit_at_transaction',
    ];

    protected function casts(): array
    {
        return [
            'qty_in_selected_unit' => 'decimal:2',
            'conversion_factor_at_transaction' => 'decimal:4',
            'price_per_unit_at_transaction' => 'decimal:2',
            'cost_price_per_base_unit_at_transaction' => 'decimal:2',
        ];
    }

    /**
     * Subtotal for this line item (in selected unit).
     */
    public function getSubtotalAttribute(): float
    {
        return $this->qty_in_selected_unit * $this->price_per_unit_at_transaction;
    }

    /**
     * Profit (laba kotor) for this line item.
     * = (price_per_unit - cost_per_unit) × qty
     * where cost_per_unit = cost_price_per_base_unit × conversion_factor
     */
    public function getProfitAttribute(): float
    {
        $costPerUnit = $this->cost_price_per_base_unit_at_transaction * $this->conversion_factor_at_transaction;
        return ($this->price_per_unit_at_transaction - $costPerUnit) * $this->qty_in_selected_unit;
    }

    /**
     * Quantity in base unit (for stock calculations).
     */
    public function getQtyInBaseUnitAttribute(): float
    {
        return $this->qty_in_selected_unit * $this->conversion_factor_at_transaction;
    }

    /**
     * Get the parent transaction.
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Get the associated product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
