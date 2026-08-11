<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'dashboard.view.own',
            'dashboard.view.all',
            'dashboard.view.inventory',
            'dashboard.view.sales',
            'customers.view',
            'customers.view.scoped',
            'customers.manage',
            'devices.create',
            'repairs.view.own',
            'repairs.view.status',
            'repairs.manage',
            'repairs.assign',
            'diagnosis.manage',
            'estimation.manage.full',
            'estimation.manage.limited',
            'estimation.view',
            'technicians.view.own',
            'technicians.view.availability',
            'technicians.view.assignments',
            'technicians.manage',
            'jobs.create',
            'jobs.manage.full',
            'invoices.manage',
            'inventory.view',
            'parts.catalog.manage',
            'parts.usage.create',
            'parts.usage.view',
            'suppliers.manage',
            'suppliers.view',
            'warranty.manage',
            'warranty.claim',
            'warranty.view',
            'warranty.view.scoped',
            'notifications.trigger.inventory',
            'notifications.trigger.customer',
            'reports.view.own',
            'reports.view.financial',
            'reports.view.inventory',
            'reports.view.sales',
            'users.manage.full',
            'users.manage.limited',
            'audit.view',
            'backup.manage',
        ];

        foreach ($permissions as $perm) {
            Permission::findOrCreate($perm);
        }

        // admin — Gate::before bypass; no explicit permissions needed
        Role::findOrCreate('admin');

        // shop_manager
        $shopManager = Role::findOrCreate('shop_manager');
        $shopManager->syncPermissions([
            'dashboard.view.all', 'dashboard.view.inventory', 'dashboard.view.sales',
            'customers.view', 'customers.view.scoped', 'customers.manage', 'devices.create',
            'repairs.view.own', 'repairs.view.status', 'repairs.manage', 'repairs.assign',
            'diagnosis.manage',
            'estimation.manage.full', 'estimation.view',
            'technicians.view.own', 'technicians.view.availability', 'technicians.view.assignments', 'technicians.manage',
            'jobs.create', 'jobs.manage.full',
            'invoices.manage',
            'inventory.view', 'parts.catalog.manage', 'parts.usage.create', 'parts.usage.view',
            'suppliers.view',
            'warranty.manage', 'warranty.claim', 'warranty.view', 'warranty.view.scoped',
            'notifications.trigger.inventory', 'notifications.trigger.customer',
            'reports.view.own', 'reports.view.financial', 'reports.view.inventory', 'reports.view.sales',
            'users.manage.limited',
            'audit.view',
        ]);

        // technician
        $technician = Role::findOrCreate('technician');
        $technician->syncPermissions([
            'dashboard.view.own',
            'customers.view.scoped',
            'inventory.view',
            'repairs.view.own', 'repairs.manage',
            'diagnosis.manage',
            'estimation.manage.limited', 'estimation.view',
            'technicians.view.own', 'technicians.view.assignments',
            'parts.usage.create', 'parts.usage.view',
            'warranty.view.scoped',
            'reports.view.own',
        ]);

        // inventory_staff
        $inventoryStaff = Role::findOrCreate('inventory_staff');
        $inventoryStaff->syncPermissions([
            'dashboard.view.inventory',
            'inventory.view', 'parts.catalog.manage', 'parts.usage.view',
            'suppliers.manage', 'suppliers.view',
            'notifications.trigger.inventory',
            'reports.view.inventory',
        ]);

        // cashier — front-desk: creates tickets, assigns technicians, handles billing
        $cashier = Role::findOrCreate('cashier');
        $cashier->syncPermissions([
            'dashboard.view.sales',
            'customers.view', 'customers.view.scoped', 'customers.manage', 'devices.create',
            'repairs.view.status', 'repairs.assign',
            'estimation.view',
            'technicians.view.availability', 'technicians.view.assignments',
            'jobs.create', 'jobs.manage.full',
            'invoices.manage',
            'warranty.view', 'warranty.view.scoped',
            'notifications.trigger.customer',
            'reports.view.sales',
        ]);
    }
}
