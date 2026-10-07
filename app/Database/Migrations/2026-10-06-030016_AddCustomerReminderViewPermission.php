<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCustomerReminderViewPermission extends Migration
{
    public function up()
    {
        $db = db_connect();

        /*
         * 1. Procura a permissão.
         */
        $permission = $db
            ->table('permission')
            ->where(
                'name',
                'customer_reminder.view'
            )
            ->get()
            ->getRowArray();

        /*
         * 2. Cria somente se ainda não existir.
         */
        if ($permission === null) {
            $db
                ->table('permission')
                ->insert([
                    'name' =>
                        'customer_reminder.view',
                ]);

            $permission = $db
                ->table('permission')
                ->where(
                    'name',
                    'customer_reminder.view'
                )
                ->get()
                ->getRowArray();
        }

        if ($permission === null) {
            return;
        }

        /*
         * 3. Atribui a OWNER e EMPLOYEE.
         */
        $roles = $db
            ->table('role')
            ->whereIn(
                'name',
                [
                    'OWNER',
                    'EMPLOYEE',
                ]
            )
            ->get()
            ->getResultArray();

        foreach ($roles as $role) {
            $rolePermission = $db
                ->table('role_permission')
                ->where(
                    'role_id',
                    $role['id']
                )
                ->where(
                    'permission_id',
                    $permission['id']
                )
                ->get()
                ->getRowArray();

            if ($rolePermission !== null) {
                continue;
            }

            $db
                ->table('role_permission')
                ->insert([
                    'role_id' =>
                        $role['id'],

                    'permission_id' =>
                        $permission['id'],
                ]);
        }
    }

    public function down()
    {
        $db = db_connect();

        $permission = $db
            ->table('permission')
            ->where(
                'name',
                'customer_reminder.view'
            )
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