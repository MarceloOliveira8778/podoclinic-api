<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Sale extends Entity
{
    protected $casts = [
        'id'                             => 'integer',
        'customer_id'                    => 'integer',
        'product_id'                     => 'integer',
        'financial_category_id'          => 'integer',
        'created_by_user_id'             => 'integer',
        'quantity'                       => 'integer',
        'average_duration_days_snapshot' => 'integer',
    ];
}