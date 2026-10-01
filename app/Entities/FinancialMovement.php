<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class FinancialMovement extends Entity
{
    protected $casts = [
        'id'                    => 'integer',
        'financial_category_id' => 'integer',
        'created_by_user_id'    => 'integer',
    ];
}