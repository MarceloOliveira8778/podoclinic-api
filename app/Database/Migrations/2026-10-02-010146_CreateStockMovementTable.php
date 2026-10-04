<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStockMovementTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],

            'product_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],

            'created_by_user_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],

            'type' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'IN',
                    'OUT',
                ],
            ],

            'reason' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'PURCHASE',
                    'CONSUMPTION',
                    'SALE',
                    'ADJUSTMENT',
                ],
            ],

            'quantity' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],

            'movement_date' => [
                'type' => 'DATE',
            ],

            'notes' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
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

        $this->forge->addKey('product_id');
        $this->forge->addKey('created_by_user_id');
        $this->forge->addKey('movement_date');
        $this->forge->addKey('type');
        $this->forge->addKey('reason');

        $this->forge->addForeignKey(
            'product_id',
            'product',
            'id',
            'NO ACTION',
            'NO ACTION',
            'fk_stock_movement_product'
        );

        $this->forge->addForeignKey(
            'created_by_user_id',
            'user',
            'id',
            'NO ACTION',
            'NO ACTION',
            'fk_stock_movement_user'
        );

        $this->forge->createTable(
            'stock_movement'
        );
    }

    public function down()
    {
        $this->forge->dropTable(
            'stock_movement'
        );
    }
}