<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Product extends Model
{
    use BelongsToTenant;

    /**
     * MASS ASSIGNMENT
     */
   protected $fillable = [

    'tenant_id',
    'category_id',
    'branch_id',
    'name',
    'cost_price',
    'price',
    'stock_quantity',
    'preparation_area',
    'is_unlimited'

];

    /**
     * DEFAULT VALUES
     */
    protected $attributes = [

    'preparation_area' => 'direct',
    'is_unlimited' => false

];
    /**
     * CATEGORY RELATION
     */
    public function category()
    {
        return $this->belongsTo(
            Category::class
        );
    }

    /**
     * SALE ITEMS RELATION
     */
    public function saleItems()
    {
        return $this->hasMany(
            SaleItem::class
        );
    }

    /**
     * RECIPES RELATION
     */
    public function recipes()
    {
        return $this->hasMany(
            Recipe::class
        );
    }

    public function branch()
{
    return $this->belongsTo(
        Branch::class
    );
}
    /**
     * CHECK IF PRODUCT
     * NEEDS KITCHEN
     */
    public function isKitchenItem()
    {
        return
            $this->preparation_area
            === 'kitchen';
    }
}