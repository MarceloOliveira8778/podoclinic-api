<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            [
                'name'        => 'customer.create',
                'description' => 'Cadastrar clientes.',
            ],
            [
                'name'        => 'customer.view',
                'description' => 'Visualizar clientes.',
            ],
            [
                'name'        => 'customer.update',
                'description' => 'Alterar clientes.',
            ],

            [
                'name'        => 'product.create',
                'description' => 'Cadastrar produtos.',
            ],
            [
                'name'        => 'product.view',
                'description' => 'Visualizar produtos.',
            ],
            [
                'name'        => 'product.update',
                'description' => 'Alterar produtos.',
            ],

            [
                'name'        => 'stock.view',
                'description' => 'Visualizar estoque.',
            ],
            [
                'name'        => 'stock.entry',
                'description' => 'Registrar entrada de estoque.',
            ],
            [
                'name'        => 'stock.exit',
                'description' => 'Registrar saída de estoque.',
            ],

            [
                'name'        => 'purchase.create',
                'description' => 'Registrar compras.',
            ],
            [
                'name'        => 'purchase.view',
                'description' => 'Visualizar compras.',
            ],

            [
                'name'        => 'sale.create',
                'description' => 'Registrar vendas.',
            ],
            [
                'name'        => 'sale.view',
                'description' => 'Visualizar vendas.',
            ],

            [
                'name'        => 'financial_movement.create',
                'description' => 'Cadastrar lançamentos financeiros.',
            ],
            [
                'name'        => 'financial_movement.view',
                'description' => 'Visualizar lançamentos financeiros.',
            ],
            [
                'name'        => 'financial_category.manage',
                'description' => 'Gerenciar categorias financeiras.',
            ],

            [
                'name'        => 'reminder.view',
                'description' => 'Visualizar lembretes de reposição.',
            ],
            [
                'name'        => 'reminder.update',
                'description' => 'Alterar situação de lembretes de reposição.',
            ],

            [
                'name'        => 'financial_dashboard.view',
                'description' => 'Visualizar informações financeiras no dashboard.',
            ],
            [
                'name'        => 'financial_report.view',
                'description' => 'Visualizar relatórios financeiros.',
            ],

            [
                'name'        => 'user.create',
                'description' => 'Cadastrar usuários.',
            ],
            [
                'name'        => 'user.view',
                'description' => 'Visualizar usuários.',
            ],
            [
                'name'        => 'user.update',
                'description' => 'Alterar usuários.',
            ],
            [
                'name'        => 'user.activate',
                'description' => 'Ativar usuários.',
            ],
            [
                'name'        => 'user.deactivate',
                'description' => 'Inativar usuários.',
            ],
            [
                'name'        => 'permission.manage',
                'description' => 'Gerenciar permissões.',
            ],
        ];

        foreach ($permissions as $permission) {
            $existingPermission = $this->db
                ->table('permission')
                ->where('name', $permission['name'])
                ->get()
                ->getRowArray();

            if ($existingPermission === null) {
                $permission['created_at'] = date('Y-m-d H:i:s');
                $permission['updated_at'] = date('Y-m-d H:i:s');

                $this->db
                    ->table('permission')
                    ->insert($permission);
            }
        }
    }
}