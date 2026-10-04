<?php

namespace App\Models;

use App\Entities\StockMovement;
use CodeIgniter\Model;

class StockMovementModel extends Model
{
    protected $table            = 'stock_movement';
    protected $primaryKey       = 'id';
    protected $returnType       = StockMovement::class;
    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'product_id',
        'created_by_user_id',
        'type',
        'reason',
        'quantity',
        'movement_date',
        'notes',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}