<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAuthSessionTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'token_hash' => [
                'type'       => 'CHAR',
                'constraint' => 64,
            ],
            'last_activity_at' => [
                'type' => 'DATETIME',
            ],
            'expires_at' => [
                'type' => 'DATETIME',
            ],
            'revoked_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);

        $this->forge->addUniqueKey(
            'token_hash',
            'uk_auth_session_token_hash'
        );

        $this->forge->addKey('user_id');
        $this->forge->addKey('last_activity_at');
        $this->forge->addKey('expires_at');

        $this->forge->addForeignKey(
            'user_id',
            'user',
            'id',
            'RESTRICT',
            'RESTRICT',
            'fk_auth_session_user'
        );

        $this->forge->createTable('auth_session');
    }

    public function down()
    {
        $this->forge->dropTable('auth_session');
    }
}