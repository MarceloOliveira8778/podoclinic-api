<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class CustomerReminder extends Entity
{
    protected $casts = [
        'id'                             => 'integer',
        'sale_id'                        => 'integer',
        'customer_id'                    => 'integer',
        'product_id'                     => 'integer',
        'average_duration_days_snapshot' => 'integer',
    ];
}