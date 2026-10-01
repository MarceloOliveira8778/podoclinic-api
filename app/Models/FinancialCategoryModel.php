<?php

namespace App\Models;

use App\Entities\FinancialCategory;
use CodeIgniter\Model;

class FinancialCategoryModel extends Model
{
    protected $table            = 'financial_category';
    protected $primaryKey       = 'id';
    protected $returnType       = FinancialCategory::class;
    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'name',
        'type',
        'is_active',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'name' => [
            'label' => 'Nome',
            'rules' => 'required|max_length[100]',
        ],

        'type' => [
            'label' => 'Tipo',
            'rules' => 'required|in_list[INCOME,EXPENSE]',
        ],
    ];
}