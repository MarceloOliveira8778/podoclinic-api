<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class AuthSession extends Entity
{
    protected $casts = [
        'id'      => 'integer',
        'user_id' => 'integer',
    ];

    protected $hidden = [
        'token_hash',
    ];
}