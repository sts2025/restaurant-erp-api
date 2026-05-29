<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Branch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create System Roles
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);
        $cashierRole = Role::firstOrCreate(['name' => 'Cashier']);
        $managerRole = Role::firstOrCreate(['name' => 'Manager']);

        // 2. Create a Test Tenant (Restaurant/Bakery)
        $tenant = Tenant::create([
            'name' => 'Streamline Bakery & Cafe',
            'business_type' => 'bakery',
            'currency' => 'UGX',
            'timezone' => 'Africa/Kampala',
            'status' => true,
        ]);

        // 3. Create a Main Branch for the Tenant
        $branch = Branch::create([
            'tenant_id' => $tenant->id,
            'name' => 'Kampala Main Branch',
            'location' => 'Downtown',
            'is_main' => true,
        ]);

        // 4. Create an Admin Test User
        $user = User::create([
            'name' => 'System Admin',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'status' => true,
        ]);

        // 5. Assign the Super Admin role to the user
        $user->assignRole($superAdminRole);
        // 6. Create Menu Categories
        $pastries = \App\Models\Category::create([
            'tenant_id' => $tenant->id,
            'name' => 'Pastries',
        ]);

        $beverages = \App\Models\Category::create([
            'tenant_id' => $tenant->id,
            'name' => 'Hot Beverages',
        ]);

        // 7. Create Products
        \App\Models\Product::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'category_id' => $pastries->id,
            'name' => 'Butter Croissant',
            'price' => 5500,
            'stock_quantity' => 50,
        ]);

        \App\Models\Product::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'category_id' => $beverages->id,
            'name' => 'Cafe Latte',
            'price' => 8000,
            'stock_quantity' => 100,
        ]);
    }
}
