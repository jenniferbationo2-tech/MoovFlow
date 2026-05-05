<?php

namespace Database\Seeders;

use App\Support\AdminPermissionCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Vider tous les caches
        DB::table('cache')->truncate();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        echo "🧹 Cache vidé\n";

        // 2. Créer les permissions (déjà là, mais firstOrCreate au cas où)
        $permissions = AdminPermissionCatalog::all();

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name'       => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        echo "✅ " . count($permissions) . " permissions vérifiées\n";

        // 3. Vider le cache APRES création
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 4. Attribuer aux rôles via SQL direct (bypass du cache Spatie)
        echo "🎭 Attribution aux rôles...\n";

        foreach (AdminPermissionCatalog::roleAssignments() as $roleName => $permissionNames) {

            // Trouve ou crée le rôle
            $role = Role::firstOrCreate([
                'name'       => $roleName,
                'guard_name' => 'web',
            ]);

            // Récupère les IDs des permissions par requête directe
            $permissionIds = DB::table('permissions')
                ->whereIn('name', $permissionNames)
                ->where('guard_name', 'web')
                ->pluck('id');

            // Supprime les anciennes attributions de ce rôle
            DB::table('role_has_permissions')
                ->where('role_id', $role->id)
                ->delete();

            // Insère directement dans la table pivot
            $rows = $permissionIds->map(fn ($id) => [
                'permission_id' => $id,
                'role_id'       => $role->id,
            ])->toArray();

            if (!empty($rows)) {
                DB::table('role_has_permissions')->insert($rows);
            }

            echo "  ✅ {$roleName} → " . count($rows) . " permissions\n";
        }

        // 5. Vider le cache final
        DB::table('cache')->truncate();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        echo "\n✨ Terminé !\n";
    }
}