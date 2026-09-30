<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRolePermissionTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'role_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'permission_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);

        $this->forge->addKey('role_id');
        $this->forge->addKey('permission_id');

        $this->forge->addUniqueKey(
            [
                'role_id',
                'permission_id',
            ],
            'uk_role_permission'
        );

        $this->forge->addForeignKey(
            'role_id',
            'role',
            'id',
            'RESTRICT',
            'RESTRICT',
            'fk_role_permission_role'
        );

        $this->forge->addForeignKey(
            'permission_id',
            'permission',
            'id',
            'RESTRICT',
            'RESTRICT',
            'fk_role_permission_permission'
        );

        $this->forge->createTable('role_permission');
    }

    public function down()
    {
        $this->forge->dropTable('role_permission');
    }
}