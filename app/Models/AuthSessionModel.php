<?php

namespace App\Models;

use App\Entities\AuthSession;
use CodeIgniter\Model;

class AuthSessionModel extends Model
{
    protected $table            = 'auth_session';
    protected $primaryKey       = 'id';
    protected $returnType       = AuthSession::class;
    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'user_id',
        'token_hash',
        'last_activity_at',
        'expires_at',
        'revoked_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    protected $validationRules = [
        'user_id' => [
            'label' => 'Usuário',
            'rules' => 'required|is_natural_no_zero',
        ],
        'token_hash' => [
            'label' => 'Token',
            'rules' => 'required|exact_length[64]',
        ],
        'last_activity_at' => [
            'label' => 'Última atividade',
            'rules' => 'required',
        ],
        'expires_at' => [
            'label' => 'Expiração',
            'rules' => 'required',
        ],
    ];
}