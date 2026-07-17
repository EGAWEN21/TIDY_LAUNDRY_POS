<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Permission;
use App\Models\UserRole;

$permissions = [
    ['name' => 'order_price_override', 'display_name' => 'Edit Service/Addon Prices', 'category' => 'Order'],
    ['name' => 'order_discount_apply', 'display_name' => 'Apply Custom Discounts', 'category' => 'Order'],
];

foreach ($permissions as $permission) {
    $perm = Permission::updateOrCreate(['name' => $permission['name']], $permission);
    $role = UserRole::find(1);
    if ($role) {
        // the relationship is likely defined as role->permissions() ? Let's just create a raw insert with the proper data if there is no sync. Wait! UserRole is defined, we can insert directly with the role.
        \Illuminate\Support\Facades\DB::table('user_role_permissions')->insertOrIgnore([
            'role_id' => 1,
            'permission_id' => $perm->id,
            'name' => $perm->display_name,
            'permission_name' => $perm->name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
echo "Done\n";
