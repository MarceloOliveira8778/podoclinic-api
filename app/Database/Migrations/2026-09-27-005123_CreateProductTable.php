<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProductTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
            ],
            'type' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'CONSUMPTION',
                    'RESALE',
                ],
            ],
            'unit' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
            ],
            'package_quantity' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'current_stock' => [
                'type'     => 'INT',
                'unsigned' => true,
                'default'  => 0,
            ],
            'minimum_stock' => [
                'type'     => 'INT',
                'unsigned' => true,
                'default'  => 0,
            ],
            'average_duration_days' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'is_active' => [
                'type'    => 'BOOLEAN',
                'default' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('name');
        $this->forge->addKey('type');
        $this->forge->addKey('is_active');

        $this->forge->createTable('product');
    }

    public function down()
    {
        $this->forge->dropTable('product');
    }
}