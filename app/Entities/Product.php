<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Product extends Entity
{
    protected $casts = [
        'id'                    => 'integer',
        'package_quantity'      => '?integer',
        'current_stock'         => 'integer',
        'minimum_stock'         => 'integer',
        'average_duration_days' => '?integer',
        'is_active'             => 'boolean',
    ];
}