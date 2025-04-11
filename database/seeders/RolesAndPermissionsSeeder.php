<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // إنشاء الأدوار الأساسية حسب المتطلبات
        $pharmacistRole = Role::firstOrCreate(['name' => 'pharmacist', 'guard_name' => 'web']);
        $warehouseOwnerRole = Role::firstOrCreate(['name' => 'warehouse_owner', 'guard_name' => 'web']);

        // إنشاء الصلاحيات حسب المتطلبات الإجبارية والأساسية
        $permissions = [
            // صلاحيات الصيدلاني (ابات الإجبارية)
            'register_via_mobile',
            'login_logout',
            'browse_medicines_by_category',
            // صلاحيات صاحب المستودع (الطلبات الإجبارية)
            'add_medicines',

            // صلاحيات مشتركة (الطلبات الأساسية)
            'search_medicines',
            'view_medicine_details',

            // صلاحيات إضافية للصيدلاني
            'place_orders',
            'view_order_status',
            'manage_favorites',

            // صلاحيات إضافية لصاحب المستودع
            'manage_orders',
            'update_order_status',
            'update_payment_status',
            'view_reports'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // تعيين الصلاحيات للصيدلاني حسب المتطلبات
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
        // تعيين الصلاحيات لصاحب المستودع حسب المتطلبات
        $warehouseOwnerRole->syncPermissions([
            'add_medicines',
            'search_medicines',
            'view_medicine_details',
            'manage_orders',
            'update_order_status',
            'update_payment_status',
            'view_reports'
        ]);

        // إنشاء مستخدم تجريبي للصيدلاني
        $pharmacist = \App\Models\User::firstOrCreate([
            'name' => 'صيدلاني تجريبي',
            'email' => 'pharma@gmail.com',
            'password' => bcrypt('password')
        ]);
        $pharmacist->assignRole('pharmacist');

        // إنشاء مستخدم تجريبي لصاحب المستودع
        $warehouseOwner = \App\Models\User::firstOrCreate([
            'name' => 'صاحب مستودع تجريبي',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('password')
        ]);
        $warehouseOwner->assignRole('warehouse_owner');
    }
}
