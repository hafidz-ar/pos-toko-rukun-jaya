<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Restock extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'qty_base_unit',
        'unit_name_at_restock',
        'cost_price_per_base_unit_at_restock',
        'location',
        'restocked_by_user_id',
        'restocked_at',
    ];

    protected function casts(): array
    {
        return [
            'qty_base_unit' => 'decimal:2',
            'cost_price_per_base_unit_at_restock' => 'decimal:2',
            'restocked_at' => 'datetime',
        ];
    }

    /**
     * Get the product being restocked.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the user who performed the restock.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'restocked_by_user_id');
    }

    /**
     * Get the notification associated with this restock.
     */
    public function notification(): HasOne
    {
        return $this->hasOne(Notification::class, 'related_restock_id');
    }
}
