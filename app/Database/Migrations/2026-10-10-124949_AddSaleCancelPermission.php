<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use RuntimeException;

class AddSaleCancelPermission extends Migration
{
    public function up()
    {
        $db = db_connect();

        // Localiza a permissão.
        $permission = $db
            ->table('permission')
            ->where('name', 'sale.cancel')
            ->get()
            ->getRowArray();

        // Cria a permissão caso não exista.
        if ($permission === null) {
            $db->table('permission')->insert([
                'name' => 'sale.cancel',
            ]);

            $permission = $db
                ->table('permission')
                ->where('name', 'sale.cancel')
                ->get()
                ->getRowArray();
        }

        if ($permission === null) {
            throw new RuntimeException(
                'Não foi possível criar a permissão sale.cancel.'
            );
        }

        // Localiza o perfil OWNER.
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

        // Verifica se o vínculo já existe.
        $existing = $db
            ->table('role_permission')
            ->where('role_id', $owner['id'])
            ->where('permission_id', $permission['id'])
            ->get()
            ->getRowArray();

        // Atribui a permissão ao OWNER.
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
            ->where('name', 'sale.cancel')
            ->get()
            ->getRowArray();

        if ($permission === null) {
            return;
        }

        // Remove os vínculos da permissão.
        $db
            ->table('role_permission')
            ->where('permission_id', $permission['id'])
            ->delete();

        // Remove a permissão.
        $db
            ->table('permission')
            ->where('id', $permission['id'])
            ->delete();
    }
}