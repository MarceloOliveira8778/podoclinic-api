<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Purchase extends Entity
{
    protected $casts = [
        'id'                    => 'integer',
        'product_id'            => 'integer',
        'financial_category_id' => 'integer',
        'created_by_user_id'    => 'integer',
        'quantity'              => 'integer',
    ];
}