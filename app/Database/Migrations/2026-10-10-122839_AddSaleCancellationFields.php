<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSaleCancellationFields extends Migration
{
    public function up()
    {
        $this->forge->addColumn('sale', [
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'COMPLETED',
                'null'       => false,
                'after'      => 'notes',
            ],

            'cancellation_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'after'      => 'status',
            ],

            'cancellation_reason' => [
                'type'  => 'TEXT',
                'null'  => true,
                'after' => 'cancellation_type',
            ],

            'cancelled_at' => [
                'type'  => 'DATETIME',
                'null'  => true,
                'after' => 'cancellation_reason',
            ],

            'cancelled_by_user_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'cancelled_at',
            ],
        ]);

        $this->db->query(
            'ALTER TABLE sale
             ADD CONSTRAINT fk_sale_cancelled_by_user
             FOREIGN KEY (cancelled_by_user_id)
             REFERENCES user(id)
             ON DELETE NO ACTION
             ON UPDATE NO ACTION'
        );
    }

    public function down()
    {
        $this->db->query(
            'ALTER TABLE sale
             DROP FOREIGN KEY fk_sale_cancelled_by_user'
        );

        $this->forge->dropColumn('sale', [
            'status',
            'cancellation_type',
            'cancellation_reason',
            'cancelled_at',
            'cancelled_by_user_id',
        ]);
    }
}