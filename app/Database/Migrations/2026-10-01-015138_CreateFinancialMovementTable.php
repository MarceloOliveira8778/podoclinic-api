<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFinancialMovementTable extends Migration
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

            'financial_category_id' => [
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
                    'INCOME',
                    'EXPENSE',
                ],
            ],

            'description' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],

            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
            ],

            'payment_method' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'CASH',
                    'PIX',
                ],
                'null' => true,
            ],

            'movement_date' => [
                'type' => 'DATE',
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

        $this->forge->addKey(
            'financial_category_id'
        );

        $this->forge->addKey(
            'created_by_user_id'
        );

        $this->forge->addKey(
            'movement_date'
        );

        $this->forge->addKey(
            'type'
        );

        $this->forge->addForeignKey(
            'financial_category_id',
            'financial_category',
            'id',
            'NO ACTION',
            'NO ACTION',
            'fk_financial_movement_category'
        );

        $this->forge->addForeignKey(
            'created_by_user_id',
            'user',
            'id',
            'NO ACTION',
            'NO ACTION',
            'fk_financial_movement_user'
        );

        $this->forge->createTable(
            'financial_movement'
        );
    }

    public function down()
    {
        $this->forge->dropTable(
            'financial_movement'
        );
    }
}