<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;

class PermissionService
{
    private BaseConnection $database;

    public function __construct()
    {
        $this->database = db_connect();
    }

    public function userHasPermission(
        int $userId,
        string $permissionName
    ): bool {
        $permission = $this->database
            ->table('user')
            ->select('permission.id')
            ->join(
                'role',
                'role.id = user.role_id'
            )
            ->join(
                'role_permission',
                'role_permission.role_id = role.id'
            )
            ->join(
                'permission',
                'permission.id = role_permission.permission_id'
            )
            ->where('user.id', $userId)
            ->where('user.is_active', true)
            ->where('role.is_active', true)
            ->where('permission.name', $permissionName)
            ->get()
            ->getRowArray();

        return $permission !== null;
    }
}