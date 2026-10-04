<?php

namespace App\Models;

use App\Entities\Sale;
use CodeIgniter\Model;

class SaleModel extends Model
{
    protected $table            = 'sale';
    protected $primaryKey       = 'id';
    protected $returnType       = Sale::class;
    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'customer_id',
        'product_id',
        'financial_category_id',
        'created_by_user_id',
        'quantity',
        'total_amount',
        'payment_method',
        'sale_date',
        'average_duration_days_snapshot',
        'notes',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}