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
    'name',
    'price',
    'stock_quantity',
    'preparation_area'

];

    /**
     * DEFAULT VALUES
     */
    protected $attributes = [

    'preparation_area' => 'direct'

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