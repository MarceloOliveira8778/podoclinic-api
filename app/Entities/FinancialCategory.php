<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class FinancialCategory extends Entity
{
    protected $casts = [
        'id'        => 'integer',
        'is_active' => 'boolean',
    ];
}