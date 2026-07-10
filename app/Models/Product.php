<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category_id',
        'base_unit_id',
        'cost_price_per_base_unit',
        'selling_price_per_base_unit',
        'stock_qty_base_unit',
        'location',
        'photo_url',
        'photo_public_id',
        'min_stock_threshold',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'cost_price_per_base_unit' => 'decimal:2',
            'selling_price_per_base_unit' => 'decimal:2',
            'stock_qty_base_unit' => 'decimal:2',
            'min_stock_threshold' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Scope: only active products.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: products with stock below threshold.
     */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('stock_qty_base_unit', '<=', 'min_stock_threshold');
    }

    /**
     * Get the product's category.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the product's base unit.
     */
    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    /**
     * Get the unit jual alternatives.
     */
    public function units(): HasMany
    {
        return $this->hasMany(ProductUnit::class);
    }

    /**
     * Get restock history.
     */
    public function restocks(): HasMany
    {
        return $this->hasMany(Restock::class);
    }

    /**
     * Get transaction items involving this product.
     */
    public function transactionItems(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }
}
