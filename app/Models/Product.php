<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Product extends Model
{
    use BelongsToTenant;

    /**
     * MASS ASSIGNMENT
     *
     * FIX: Added cost and is_unlimited.
     * Without these here, Laravel silently drops them
     * even though the controller sends them correctly.
     */
    protected $fillable = [
        'tenant_id',
        'category_id',
        'branch_id',
        'name',
        'price',
        'cost',       // FIX: was missing — inventory valuation
        'stock_quantity',
        'is_unlimited',     // FIX: was missing — tea/coffee/water support
        'preparation_area',
    ];

    /**
     * CAST TYPES
     *
     * Ensures is_unlimited always comes back as a boolean
     * (not "0"/"1" string) when read from the database,
     * so frontend checks like `product.is_unlimited` work correctly.
     */
    protected $casts = [
        'is_unlimited' => 'boolean',
        'price'        => 'float',
        'cost'   => 'float',
        'stock_quantity' => 'integer',
    ];

    /**
     * DEFAULT VALUES
     */
    protected $attributes = [
        'preparation_area' => 'direct',
        'cost'       => 0,
        'is_unlimited'     => false,
    ];

    /**
     * CATEGORY RELATION
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * SALE ITEMS RELATION
     */
    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * RECIPES RELATION
     */
    public function recipes()
    {
        return $this->hasMany(Recipe::class);
    }

    /**
     * BRANCH RELATION
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * CHECK IF PRODUCT NEEDS KITCHEN
     */
    public function isKitchenItem()
    {
        return $this->preparation_area === 'kitchen';
    }

    /**
     * CHECK IF PRODUCT IS UNLIMITED
     * Convenience method usable anywhere in the app:
     *   if ($product->isUnlimited()) { ... }
     */
    public function isUnlimited()
    {
        return (bool) $this->is_unlimited;
    }
}
