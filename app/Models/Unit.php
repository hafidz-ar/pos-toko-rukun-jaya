<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'symbol'];

    /**
     * Get products using this unit as their base unit.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'base_unit_id');
    }

    /**
     * Get alternative product units using this unit.
     */
    public function productUnits(): HasMany
    {
        return $this->hasMany(ProductUnit::class, 'unit_id');
    }
}
