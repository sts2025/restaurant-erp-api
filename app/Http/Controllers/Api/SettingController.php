<?php
 
namespace App\Http\Controllers\Api;
 
use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
 
class SettingController extends Controller
{
    /**
     * GET ALL SETTINGS
     *
     * Returns business-wide settings only.
     * address and phone are now per-branch (stored on branches table).
     *
     * GET /settings
     */
    public function index()
    {
        $defaults = [
            'restaurantName' => 'My Business',
            'taxRate'        => '18',
            'currency'       => 'UGX',
            'receiptFooter'  => 'Thank you for your business!',
        ];
 
        $saved = Setting::allAsArray();
 
        return response()->json(array_merge($defaults, $saved));
    }
 
    /**
     * SAVE SETTINGS
     *
     * Only saves business-wide settings.
     * address and phone are managed per-branch via PUT /branches/{id}.
     *
     * PUT /settings
     */
    public function update(Request $request)
    {
        $request->validate([
            'restaurantName' => 'sometimes|string|max:255',
            'taxRate'        => 'sometimes|nullable|numeric|min:0|max:100',
            'currency'       => 'sometimes|string|max:10',
            'receiptFooter'  => 'sometimes|nullable|string|max:500',
        ]);
 
        $allowed = [
            'restaurantName',
            'taxRate',
            'currency',
            'receiptFooter',
        ];
 
        foreach ($allowed as $key) {
            if ($request->has($key)) {
                Setting::set($key, $request->input($key));
            }
        }
 
        return response()->json([
            'status'   => 'success',
            'message'  => 'Settings saved successfully',
            'settings' => Setting::allAsArray(),
        ]);
    }
}