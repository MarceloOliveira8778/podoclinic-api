<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class StockMovement extends Entity
{
    protected $casts = [
        'id'                 => 'integer',
        'product_id'         => 'integer',
        'created_by_user_id' => 'integer',
        'quantity'           => 'integer',
    ];
}