<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create all permissions
        $permissions = [
            // Laporan
            'laporan.view',
            'laporan.create',
            'laporan.update',
            'laporan.delete',
            'laporan.verify',

            // Penugasan
            'penugasan.view',
            'penugasan.create',
            'penugasan.update',
            'penugasan.delete',

            // Relawan
            'relawan.view',
            'relawan.create',
            'relawan.update',
            'relawan.delete',

            // Faskes
            'faskes.view',
            'faskes.create',
            'faskes.update',
            'faskes.delete',

            // Ambulans
            'ambulans.view',
            'ambulans.create',
            'ambulans.update',
            'ambulans.delete',

            // Zona
            'zona.view',
            'zona.create',
            'zona.update',
            'zona.delete',

            // Pedoman
            'pedoman.view',
            'pedoman.create',
            'pedoman.update',
            'pedoman.delete',

            // User management
            'user.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions

        // Super Admin — gets all permissions
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->syncPermissions(Permission::all());

        // Admin Faskes
        $adminFaskes = Role::firstOrCreate(['name' => 'admin_faskes']);
        $adminFaskes->syncPermissions([
            'faskes.view', 'faskes.create', 'faskes.update', 'faskes.delete',
            'ambulans.view', 'ambulans.create', 'ambulans.update', 'ambulans.delete',
        ]);

        // Koordinator Relawan
        $koordinatorRelawan = Role::firstOrCreate(['name' => 'koordinator_relawan']);
        $koordinatorRelawan->syncPermissions([
            'laporan.view',
            'penugasan.view', 'penugasan.create', 'penugasan.update',
            'relawan.view', 'relawan.create', 'relawan.update', 'relawan.delete',
            'zona.view',
        ]);

        // Petugas Penanganan
        $petugasPenanganan = Role::firstOrCreate(['name' => 'petugas_penanganan']);
        $petugasPenanganan->syncPermissions([
            'laporan.view',
            'penugasan.view', 'penugasan.update',
            'zona.view',
        ]);

        // Admin Konten
        $adminKonten = Role::firstOrCreate(['name' => 'admin_konten']);
        $adminKonten->syncPermissions([
            'pedoman.view', 'pedoman.create', 'pedoman.update', 'pedoman.delete',
        ]);
    }
}
