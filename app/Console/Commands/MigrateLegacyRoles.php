<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use App\Models\User;

class MigrateLegacyRoles extends Command
{
    protected $signature = 'roles:migrate-legacy';
    protected $description = 'One-time migration: rename legacy role names to new slugs (idempotent, safe to re-run).';

    /** Maps legacy role name => new slug */
    protected array $roleMap = [
        'Administrator' => 'admin',
        'Shop Manager'  => 'shop_manager',
        'Technician'    => 'technician',
        'Inventory Staff' => 'inventory_staff',
        'Cashier'       => 'cashier',
    ];

    public function handle(): int
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $summary = [];

        foreach ($this->roleMap as $legacyName => $newSlug) {
            $legacyRole = Role::where('name', $legacyName)->first();

            if (! $legacyRole) {
                $this->line("  [skip] No legacy role found: '{$legacyName}'");
                continue;
            }

            // Ensure new slug role exists
            $newRole = Role::findOrCreate($newSlug);

            // Re-assign all users from legacy role to new slug role
            $users = User::role($legacyRole)->get();
            $count = 0;
            foreach ($users as $user) {
                $user->removeRole($legacyRole);
                if (! $user->hasRole($newRole)) {
                    $user->assignRole($newRole);
                    $count++;
                }
            }

            // Remove the old legacy role (only if it's now empty)
            if ($legacyRole->name !== $newSlug) {
                $legacyRole->delete();
            }

            $summary[$newSlug] = $count;
            $this->line("  [done] '{$legacyName}' -> '{$newSlug}': {$count} user(s) reassigned.");
        }

        $this->newLine();
        $this->info('Legacy role migration complete.');
        $this->table(['New Role', 'Users Reassigned'], collect($summary)->map(fn ($v, $k) => [$k, $v])->values()->toArray());

        return self::SUCCESS;
    }
}
