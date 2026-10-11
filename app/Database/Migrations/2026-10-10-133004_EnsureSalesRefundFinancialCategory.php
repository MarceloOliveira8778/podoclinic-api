<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class EnsureSalesRefundFinancialCategory extends Migration
{
    public function up()
    {
        $category = $this->db
            ->table('financial_category')
            ->where('name', 'Devolução de vendas')
            ->where('type', 'EXPENSE')
            ->get()
            ->getRowArray();

        if ($category !== null) {
            if ((int) $category['is_active'] !== 1) {
                $this->db
                    ->table('financial_category')
                    ->where('id', $category['id'])
                    ->update([
                        'is_active'  => 1,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
            }

            return;
        }

        $now = date('Y-m-d H:i:s');

        $this->db
            ->table('financial_category')
            ->insert([
                'name'       => 'Devolução de vendas',
                'type'       => 'EXPENSE',
                'is_active'  => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
    }

    public function down()
    {
        // A categoria é preservada no rollback para evitar
        // exclusão de registros utilizados por movimentações financeiras.
    }
}