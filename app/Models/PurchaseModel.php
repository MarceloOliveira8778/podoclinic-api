<?php

namespace App\Models;

use App\Entities\Purchase;
use CodeIgniter\Model;

class PurchaseModel extends Model
{
    protected $table            = 'purchase';
    protected $primaryKey       = 'id';
    protected $returnType       = Purchase::class;
    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'product_id',
        'financial_category_id',
        'created_by_user_id',
        'quantity',
        'total_amount',
        'purchase_date',
        'notes',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}