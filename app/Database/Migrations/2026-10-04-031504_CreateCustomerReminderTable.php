<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCustomerReminderTable extends Migration
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

            'sale_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
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

            'average_duration_days_snapshot' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],

            'estimated_replacement_date' => [
                'type' => 'DATE',
            ],

            'status' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'PENDING',
                    'CONTACTED',
                    'CANCELLED',
                ],
                'default' => 'PENDING',
            ],

            'contacted_at' => [
                'type' => 'DATETIME',
                'null' => true,
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

        $this->forge->addKey('sale_id');
        $this->forge->addKey('customer_id');
        $this->forge->addKey('product_id');
        $this->forge->addKey('estimated_replacement_date');
        $this->forge->addKey('status');

        $this->forge->addForeignKey(
            'sale_id',
            'sale',
            'id',
            'NO ACTION',
            'NO ACTION',
            'fk_customer_reminder_sale'
        );

        $this->forge->addForeignKey(
            'customer_id',
            'customer',
            'id',
            'NO ACTION',
            'NO ACTION',
            'fk_customer_reminder_customer'
        );

        $this->forge->addForeignKey(
            'product_id',
            'product',
            'id',
            'NO ACTION',
            'NO ACTION',
            'fk_customer_reminder_product'
        );

        $this->forge->createTable('customer_reminder');
    }

    public function down()
    {
        $this->forge->dropTable('customer_reminder');
    }
}