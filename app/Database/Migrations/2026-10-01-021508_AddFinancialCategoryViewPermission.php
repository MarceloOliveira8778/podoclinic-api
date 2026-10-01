<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use RuntimeException;

class AddFinancialCategoryViewPermission extends Migration
{
    private const PERMISSION_NAME = 'financial_category.view';

    public function up()
    {
        $permissionTable = $this->db->table('permission');

        $permission = $permissionTable
            ->where(
                'name',
                self::PERMISSION_NAME
            )
            ->get()
            ->getRowArray();

        if ($permission === null) {
            $inserted = $permissionTable->insert([
                'name' => self::PERMISSION_NAME,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            if (!$inserted) {
                throw new RuntimeException(
                    'Não foi possível criar a permissão financial_category.view.'
                );
            }

            $permission = $this->db
                ->table('permission')
                ->where(
                    'name',
                    self::PERMISSION_NAME
                )
                ->get()
                ->getRowArray();

            if ($permission === null) {
                throw new RuntimeException(
                    'Não foi possível localizar a permissão financial_category.view após a criação.'
                );
            }
        }

        $permissionId = (int) $permission['id'];

        $this->assignPermissionToRole(
            'OWNER',
            $permissionId
        );

        $this->assignPermissionToRole(
            'EMPLOYEE',
            $permissionId
        );
    }

    public function down()
    {
        $permission = $this->db
            ->table('permission')
            ->where(
                'name',
                self::PERMISSION_NAME
            )
            ->get()
            ->getRowArray();

        if ($permission === null) {
            return;
        }

        $permissionId = (int) $permission['id'];

        $this->db
            ->table('role_permission')
            ->where(
                'permission_id',
                $permissionId
            )
            ->delete();

        $this->db
            ->table('permission')
            ->where(
                'id',
                $permissionId
            )
            ->delete();
    }

    private function assignPermissionToRole(
        string $roleName,
        int $permissionId
    ): void {
        $role = $this->db
            ->table('role')
            ->where(
                'name',
                $roleName
            )
            ->get()
            ->getRowArray();

        if ($role === null) {
            throw new RuntimeException(
                "Perfil {$roleName} não encontrado."
            );
        }

        $roleId = (int) $role['id'];

        $relationship = $this->db
            ->table('role_permission')
            ->where(
                'role_id',
                $roleId
            )
            ->where(
                'permission_id',
                $permissionId
            )
            ->get()
            ->getRowArray();

        if ($relationship !== null) {
            return;
        }

        $inserted = $this->db
            ->table('role_permission')
            ->insert([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
            ]);

        if (!$inserted) {
            throw new RuntimeException(
                "Não foi possível associar a permissão ao perfil {$roleName}."
            );
        }
    }
}