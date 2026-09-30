<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use RuntimeException;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        $ownerRole = $this->findRoleByName('OWNER');
        $employeeRole = $this->findRoleByName('EMPLOYEE');

        $permissions = $this->db
            ->table('permission')
            ->get()
            ->getResultArray();

        /*
         * OWNER recebe explicitamente todas as permissões
         * existentes no momento da execução do Seeder.
         */
        foreach ($permissions as $permission) {
            $this->assignPermission(
                (int) $ownerRole['id'],
                (int) $permission['id']
            );
        }

        $employeePermissionNames = [
            'customer.create',

            'product.create',
            'product.view',
            'product.update',

            'stock.view',
            'stock.entry',
            'stock.exit',

            'purchase.create',

            'sale.create',

            'financial_movement.create',
        ];

        foreach ($employeePermissionNames as $permissionName) {
            $permission = $this->findPermissionByName(
                $permissionName
            );

            $this->assignPermission(
                (int) $employeeRole['id'],
                (int) $permission['id']
            );
        }
    }

    private function findRoleByName(string $roleName): array
    {
        $role = $this->db
            ->table('role')
            ->where('name', $roleName)
            ->get()
            ->getRowArray();

        if ($role === null) {
            throw new RuntimeException(
                "Perfil {$roleName} não encontrado."
            );
        }

        return $role;
    }

    private function findPermissionByName(
        string $permissionName
    ): array {
        $permission = $this->db
            ->table('permission')
            ->where('name', $permissionName)
            ->get()
            ->getRowArray();

        if ($permission === null) {
            throw new RuntimeException(
                "Permissão {$permissionName} não encontrada."
            );
        }

        return $permission;
    }

    private function assignPermission(
        int $roleId,
        int $permissionId
    ): void {
        $existingRolePermission = $this->db
            ->table('role_permission')
            ->where('role_id', $roleId)
            ->where('permission_id', $permissionId)
            ->get()
            ->getRowArray();

        if ($existingRolePermission !== null) {
            return;
        }

        $this->db
            ->table('role_permission')
            ->insert([
                'role_id'       => $roleId,
                'permission_id' => $permissionId,
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
    }
}