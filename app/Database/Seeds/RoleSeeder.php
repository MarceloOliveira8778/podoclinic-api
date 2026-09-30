<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            [
                'name'        => 'OWNER',
                'description' => 'Proprietário com acesso completo ao sistema.',
                'is_active'   => true,
            ],
            [
                'name'        => 'EMPLOYEE',
                'description' => 'Funcionário com acesso operacional restrito.',
                'is_active'   => true,
            ],
        ];

        foreach ($roles as $role) {
            $existingRole = $this->db
                ->table('role')
                ->where('name', $role['name'])
                ->get()
                ->getRowArray();

            if ($existingRole === null) {
                $role['created_at'] = date('Y-m-d H:i:s');
                $role['updated_at'] = date('Y-m-d H:i:s');

                $this->db
                    ->table('role')
                    ->insert($role);
            }
        }
    }
}