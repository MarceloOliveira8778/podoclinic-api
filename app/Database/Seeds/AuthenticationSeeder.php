<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AuthenticationSeeder extends Seeder
{
    public function run()
    {
        $this->call('RoleSeeder');
        $this->call('PermissionSeeder');
        $this->call('RolePermissionSeeder');
    }
}