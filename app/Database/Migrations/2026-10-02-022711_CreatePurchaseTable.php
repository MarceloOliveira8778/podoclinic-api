<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePurchaseTable extends Migration
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

            'quantity' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],

            'total_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
            ],

            'purchase_date' => [
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

        $this->forge->addKey(
            'id',
            true
        );

        $this->forge->addKey(
            'product_id'
        );

        $this->forge->addKey(
            'financial_category_id'
        );

        $this->forge->addKey(
            'created_by_user_id'
        );

        $this->forge->addKey(
            'purchase_date'
        );

        $this->forge->addForeignKey(
            'product_id',
            'product',
            'id',
            'NO ACTION',
            'NO ACTION',
            'fk_purchase_product'
        );

        $this->forge->addForeignKey(
            'financial_category_id',
            'financial_category',
            'id',
            'NO ACTION',
            'NO ACTION',
            'fk_purchase_financial_category'
        );

        $this->forge->addForeignKey(
            'created_by_user_id',
            'user',
            'id',
            'NO ACTION',
            'NO ACTION',
            'fk_purchase_user'
        );

        $this->forge->createTable(
            'purchase'
        );
    }

    public function down()
    {
        $this->forge->dropTable(
            'purchase'
        );
    }
}