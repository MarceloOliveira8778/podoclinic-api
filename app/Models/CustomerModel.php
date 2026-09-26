<?php

namespace App\Models;

use App\Entities\Customer;
use CodeIgniter\Model;

class CustomerModel extends Model
{
    protected $table            = 'customer';
    protected $primaryKey       = 'id';
    protected $returnType       = Customer::class;
    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'name',
        'phone',
        'is_active',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'name' => [
            'label' => 'Nome',
            'rules' => 'required|max_length[120]',
        ],
        'phone' => [
            'label' => 'Celular',
            'rules' => 'required|max_length[20]',
        ],
    ];
}