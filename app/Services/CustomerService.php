<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\CustomerModel;

class CustomerService{

    public function lookup(string $search): array
    {
        $search = trim($search);

        // Validação do termo de pesquisa.
        if (mb_strlen($search, 'UTF-8') < 2) {
            throw new BusinessException(
                'Informe pelo menos 2 caracteres para pesquisar clientes.'
            );
        }

        // Evita pesquisas excessivamente longas.
        if (mb_strlen($search, 'UTF-8') > 120) {
            throw new BusinessException(
                'O termo de pesquisa deve ter no máximo 120 caracteres.'
            );
        }

        $customerModel = new CustomerModel();

        $builder = $customerModel->builder();

        $builder->select([
            'id',
            'name',
            'phone',
        ]);

        $builder->where('is_active', 1);

        $builder->groupStart()
            ->like('name', $search)
            ->orLike('phone', $search)
            ->groupEnd();

        $builder->orderBy('name', 'ASC');
        $builder->orderBy('id', 'ASC');

        $builder->limit(20);

        $customers = $builder->get()->getResultArray();

        return array_map(
            static function (array $customer): array {
                return [
                    'id' => (int) $customer['id'],
                    'name' => $customer['name'],
                    'phone' => $customer['phone'],
                ];
            },
            $customers
        );
    }


}