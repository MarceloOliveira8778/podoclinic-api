<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSaleTable extends Migration
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

            'customer_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
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

            'payment_method' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'CASH',
                    'PIX',
                ],
            ],

            'sale_date' => [
                'type' => 'DATE',
            ],

            'average_duration_days_snapshot' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
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

        $this->forge->addKey('customer_id');
        $this->forge->addKey('product_id');
        $this->forge->addKey('financial_category_id');
        $this->forge->addKey('created_by_user_id');
        $this->forge->addKey('sale_date');

        $this->forge->addForeignKey(
            'customer_id',
            'customer',
            'id',
            'NO ACTION',
            'NO ACTION',
            'fk_sale_customer'
        );

        $this->forge->addForeignKey(
            'product_id',
            'product',
            'id',
            'NO ACTION',
            'NO ACTION',
            'fk_sale_product'
        );

        $this->forge->addForeignKey(
            'financial_category_id',
            'financial_category',
            'id',
            'NO ACTION',
            'NO ACTION',
            'fk_sale_financial_category'
        );

        $this->forge->addForeignKey(
            'created_by_user_id',
            'user',
            'id',
            'NO ACTION',
            'NO ACTION',
            'fk_sale_user'
        );

        $this->forge->createTable('sale');
    }

    public function down()
    {
        $this->forge->dropTable('sale');
    }
}