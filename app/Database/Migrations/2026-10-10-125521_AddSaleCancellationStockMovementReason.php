<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSaleCancellationStockMovementReason extends Migration
{
    public function up()
    {
        $this->db->query("
            ALTER TABLE stock_movement
            MODIFY COLUMN reason ENUM(
                'PURCHASE',
                'CONSUMPTION',
                'SALE',
                'ADJUSTMENT',
                'SALE_CANCELLATION'
            ) NOT NULL
        ");
    }

    public function down()
    {
        // Impede a remoção de um valor que já esteja em uso.
        $count = $this->db
            ->table('stock_movement')
            ->where('reason', 'SALE_CANCELLATION')
            ->countAllResults();

        if ($count > 0) {
            throw new \RuntimeException(
                'Não é possível reverter a migration: existem movimentações de cancelamento de vendas.'
            );
        }

        $this->db->query("
            ALTER TABLE stock_movement
            MODIFY COLUMN reason ENUM(
                'PURCHASE',
                'CONSUMPTION',
                'SALE',
                'ADJUSTMENT'
            ) NOT NULL
        ");
    }
}