<?php

namespace App\Models;

use App\Entities\FinancialMovement;
use CodeIgniter\Model;

class FinancialMovementModel extends Model
{
    protected $table            = 'financial_movement';
    protected $primaryKey       = 'id';
    protected $returnType       = FinancialMovement::class;
    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'financial_category_id',
        'created_by_user_id',
        'type',
        'description',
        'amount',
        'payment_method',
        'movement_date',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}