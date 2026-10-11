<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFinancialMovementReversalFields extends Migration
{
    public function up()
    {
        $this->forge->addColumn('financial_movement', [
            'sale_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
            ],

            'reversed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],

            'reversed_by_user_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
            ],
        ]);

        $this->db->query(
            'ALTER TABLE financial_movement
             ADD CONSTRAINT fk_financial_movement_sale
             FOREIGN KEY (sale_id)
             REFERENCES sale(id)
             ON DELETE NO ACTION
             ON UPDATE NO ACTION'
        );

        $this->db->query(
            'ALTER TABLE financial_movement
             ADD CONSTRAINT fk_financial_movement_reversed_by
             FOREIGN KEY (reversed_by_user_id)
             REFERENCES user(id)
             ON DELETE NO ACTION
             ON UPDATE NO ACTION'
        );
    }

    public function down()
    {
        $this->db->query(
            'ALTER TABLE financial_movement
             DROP FOREIGN KEY fk_financial_movement_sale'
        );

        $this->db->query(
            'ALTER TABLE financial_movement
             DROP FOREIGN KEY fk_financial_movement_reversed_by'
        );

        $this->forge->dropColumn('financial_movement', [
            'sale_id',
            'reversed_at',
            'reversed_by_user_id',
        ]);
    }
}