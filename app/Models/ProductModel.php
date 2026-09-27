<?php

namespace App\Models;

use App\Entities\Product;
use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $table            = 'product';
    protected $primaryKey       = 'id';
    protected $returnType       = Product::class;
    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'name',
        'type',
        'unit',
        'package_quantity',
        'minimum_stock',
        'average_duration_days',
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
        'type' => [
            'label' => 'Tipo',
            'rules' => 'required|in_list[CONSUMPTION,RESALE]',
        ],
        'unit' => [
            'label' => 'Unidade',
            'rules' => 'required|max_length[30]',
        ],
        'package_quantity' => [
            'label' => 'Quantidade por embalagem',
            'rules' => 'permit_empty|is_natural_no_zero',
        ],
        'minimum_stock' => [
            'label' => 'Estoque mínimo',
            'rules' => 'required|is_natural',
        ],
        'average_duration_days' => [
            'label' => 'Duração média em dias',
            'rules' => 'permit_empty|is_natural_no_zero',
        ],
    ];
}