<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFinancialCategoryTable extends Migration
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

            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],

            'type' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'INCOME',
                    'EXPENSE',
                ],
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

        $this->forge->createTable(
            'financial_category'
        );
    }

    public function down()
    {
        $this->forge->dropTable(
            'financial_category'
        );
    }
}
