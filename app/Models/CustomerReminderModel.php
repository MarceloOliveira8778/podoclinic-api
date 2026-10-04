<?php

namespace App\Models;

use App\Entities\CustomerReminder;
use CodeIgniter\Model;

class CustomerReminderModel extends Model
{
    protected $table            = 'customer_reminder';
    protected $primaryKey       = 'id';
    protected $returnType       = CustomerReminder::class;
    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'sale_id',
        'customer_id',
        'product_id',
        'average_duration_days_snapshot',
        'estimated_replacement_date',
        'status',
        'contacted_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}