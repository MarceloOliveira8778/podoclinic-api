<?php

namespace App\Models;

use App\Entities\User;
use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'user';
    protected $primaryKey       = 'id';
    protected $returnType       = User::class;
    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'role_id',
        'name',
        'email',
        'password_hash',
        'is_active',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'role_id' => [
            'label' => 'Perfil',
            'rules' => 'required|is_natural_no_zero',
        ],
        'name' => [
            'label' => 'Nome',
            'rules' => 'required|max_length[120]',
        ],
        'email' => [
            'label' => 'E-mail',
            'rules' => 'required|valid_email|max_length[190]|is_unique[user.email,id,{id}]',
        ],
        'password_hash' => [
            'label' => 'Senha',
            'rules' => 'permit_empty|max_length[255]',
        ],
    ];
}