<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPurchaseViewPermission extends Migration
{
    public function up()
    {
        $db = db_connect();

        $permission = $db
            ->table('permission')
            ->where('name', 'purchase.view')
            ->get()
            ->getRowArray();

        if ($permission === null) {
            $db->table('permission')->insert([
                'name' => 'purchase.view',
            ]);

            $permission = $db
                ->table('permission')
                ->where('name', 'purchase.view')
                ->get()
                ->getRowArray();
        }

        $owner = $db
            ->table('role')
            ->where('name', 'OWNER')
            ->get()
            ->getRowArray();

        if (
            $permission !== null
            && $owner !== null
        ) {
            $rolePermission = $db
                ->table('role_permission')
                ->where('role_id', $owner['id'])
                ->where(
                    'permission_id',
                    $permission['id']
                )
                ->get()
                ->getRowArray();

            if ($rolePermission === null) {
                $db->table('role_permission')->insert([
                    'role_id' =>
                        $owner['id'],

                    'permission_id' =>
                        $permission['id'],
                ]);
            }
        }
    }

    public function down()
    {
        $db = db_connect();

        $permission = $db
            ->table('permission')
            ->where('name', 'purchase.view')
            ->get()
            ->getRowArray();

        if ($permission === null) {
            return;
        }

        $db
            ->table('role_permission')
            ->where(
                'permission_id',
                $permission['id']
            )
            ->delete();

        $db
            ->table('permission')
            ->where(
                'id',
                $permission['id']
            )
            ->delete();
    }
}