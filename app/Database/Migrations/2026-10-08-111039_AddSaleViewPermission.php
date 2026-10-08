<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use RuntimeException;

class AddSaleViewPermission extends Migration
{
    public function up()
    {
        $db = db_connect();

        // Verifica se a permissão já existe.
        $permission = $db
            ->table('permission')
            ->where('name', 'sale.view')
            ->get()
            ->getRowArray();

        if ($permission === null) {
            $db->table('permission')->insert([
                'name' => 'sale.view',
            ]);

            $permission = $db
                ->table('permission')
                ->where('name', 'sale.view')
                ->get()
                ->getRowArray();
        }

        if ($permission === null) {
            throw new RuntimeException(
                'Não foi possível criar a permissão sale.view.'
            );
        }

        // A consulta do histórico será exclusiva do OWNER.
        $owner = $db
            ->table('role')
            ->where('name', 'OWNER')
            ->get()
            ->getRowArray();

        if ($owner === null) {
            throw new RuntimeException(
                'O perfil OWNER não foi encontrado.'
            );
        }

        // Evita duplicidade no relacionamento.
        $existing = $db
            ->table('role_permission')
            ->where('role_id', $owner['id'])
            ->where('permission_id', $permission['id'])
            ->get()
            ->getRowArray();

        if ($existing === null) {
            $db->table('role_permission')->insert([
                'role_id'       => $owner['id'],
                'permission_id' => $permission['id'],
            ]);
        }
    }

    public function down()
    {
        $db = db_connect();

        $permission = $db
            ->table('permission')
            ->where('name', 'sale.view')
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