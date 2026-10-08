<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use RuntimeException;

class AddCustomerLookupPermission extends Migration
{
    public function up()
    {
        $db = db_connect();

        $permission = $db
            ->table('permission')
            ->where('name', 'customer.lookup')
            ->get()
            ->getRowArray();

        if ($permission === null) {
            $db->table('permission')->insert([
                'name' => 'customer.lookup',
            ]);

            $permission = $db
                ->table('permission')
                ->where('name', 'customer.lookup')
                ->get()
                ->getRowArray();
        }

        if ($permission === null) {
            throw new RuntimeException(
                'Não foi possível criar a permissão customer.lookup.'
            );
        }

        $roles = $db
            ->table('role')
            ->whereIn('name', ['OWNER', 'EMPLOYEE'])
            ->get()
            ->getResultArray();

        if (count($roles) !== 2) {
            throw new RuntimeException(
                'Os perfis OWNER e EMPLOYEE devem existir.'
            );
        }

        foreach ($roles as $role) {
            $existing = $db
                ->table('role_permission')
                ->where('role_id', $role['id'])
                ->where('permission_id', $permission['id'])
                ->get()
                ->getRowArray();

            if ($existing !== null) {
                continue;
            }

            $db->table('role_permission')->insert([
                'role_id'       => $role['id'],
                'permission_id' => $permission['id'],
            ]);
        }
    }

    public function down()
    {
        $db = db_connect();

        $permission = $db
            ->table('permission')
            ->where('name', 'customer.lookup')
            ->get()
            ->getRowArray();

        if ($permission === null) {
            return;
        }

        $db
            ->table('role_permission')
            ->where('permission_id', $permission['id'])
            ->delete();

        $db
            ->table('permission')
            ->where('id', $permission['id'])
            ->delete();
    }
}