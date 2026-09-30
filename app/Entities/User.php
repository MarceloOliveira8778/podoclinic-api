<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class User extends Entity
{
    protected $casts = [
        'id'        => 'integer',
        'role_id'   => 'integer',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'password_hash',
    ];
}