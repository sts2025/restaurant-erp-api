<?php
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
 
class Setting extends Model
{
    protected $guarded = [];
 
    /**
     * Get a setting value by key.
     * Returns $default if the key doesn't exist.
     *
     * Usage: Setting::get('restaurantName', 'My Business')
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('tenant_id', 1)
            ->where('key', $key)
            ->first();
 
        return $setting ? $setting->value : $default;
    }
 
    /**
     * Set a setting value by key.
     * Creates the record if it doesn't exist, updates if it does.
     *
     * Usage: Setting::set('restaurantName', 'Cafe Kampala')
     */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(
            ['tenant_id' => 1, 'key' => $key],
            ['value'     => $value]
        );
    }
 
    /**
     * Get all settings as a flat key => value array.
     *
     * Usage: Setting::allAsArray()
     * Returns: ['restaurantName' => 'Cafe Kampala', 'currency' => 'UGX', ...]
     */
    public static function allAsArray(): array
    {
        return static::where('tenant_id', 1)
            ->pluck('value', 'key')
            ->toArray();
    }
}