<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // حذف الكاش الخاص بالصلاحيات
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // إنشاء الأدوار
        $pharmacistRole = Role::firstOrCreate([
            'name' => 'pharmacist',
            'guard_name' => 'web'
        ]);

        $warehouseOwnerRole = Role::firstOrCreate([
            'name' => 'warehouse_owner',
            'guard_name' => 'web'
        ]);

        // إنشاء الصلاحيات
        $permissions = [

            // صلاحيات الصيدلاني
            'register_via_mobile',
            'login_logout',
            'browse_medicines_by_category',

            // صلاحيات صاحب المستودع
            'add_medicines',

            // صلاحيات مشتركة
            'search_medicines',
            'view_medicine_details',

            // إضافية للصيدلاني
            'place_orders',
            'view_order_status',
            'manage_favorites',

            // إضافية لصاحب المستودع
            'manage_orders',
            'update_order_status',
            'update_payment_status',
            'view_reports'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }

        // ربط الصلاحيات بالأدوار
        $pharmacistRole->syncPermissions([
            'register_via_mobile',
            'login_logout',
            'browse_medicines_by_category',
            'search_medicines',
            'view_medicine_details',
            'place_orders',
            'view_order_status',
            'manage_favorites'
        ]);

        $warehouseOwnerRole->syncPermissions([
            'add_medicines',
            'search_medicines',
            'view_medicine_details',
            'manage_orders',
            'update_order_status',
            'update_payment_status',
            'view_reports'
        ]);

        // إنشاء مستخدم الصيدلاني
        $pharmacist = User::firstOrCreate(
            ['email' => 'pharma@gmail.com'],
            [
                'name' => 'صيدلاني تجريبي',
                'password' => bcrypt('password')
            ]
        );

        $pharmacist->assignRole($pharmacistRole);

        // إنشاء مستخدم صاحب المستودع
        $warehouseOwner = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'صاحب مستودع تجريبي',
                'password' => bcrypt('password')
            ]
        );

        $warehouseOwner->assignRole($warehouseOwnerRole);
    }
}
