<?php

namespace App\Services;

use App\Entities\FinancialCategory;
use App\Exceptions\BusinessException;
use App\Exceptions\ResourceNotFoundException;
use App\Models\FinancialCategoryModel;

class FinancialCategoryService
{
    private FinancialCategoryModel $model;

    public function __construct()
    {
        $this->model =
            new FinancialCategoryModel();
    }

    public function create(array $data): FinancialCategory
    {
        $name = trim(
            (string) ($data['name'] ?? '')
        );

        $type = strtoupper(
            trim((string) ($data['type'] ?? ''))
        );

        $this->validateName($name);
        $this->validateType($type);

        $this->ensureUnique(
            $name,
            $type
        );

        $id = $this->model->insert([
            'name'      => $name,
            'type'      => $type,
            'is_active' => true,
        ], true);

        if ($id === false) {
            throw new BusinessException(
                'Não foi possível cadastrar a categoria financeira.'
            );
        }

        return $this->model->find((int) $id);
    }

    public function find(int $id): FinancialCategory
    {
        $category = $this->model->find($id);

        if ($category === null) {
            throw new ResourceNotFoundException(
                'Categoria financeira não encontrada.'
            );
        }

        return $category;
    }

    public function update(
        int $id,
        array $data
    ): FinancialCategory {
        $category = $this->find($id);

        $name = array_key_exists('name', $data)
            ? trim((string) $data['name'])
            : $category->name;

        $type = array_key_exists('type', $data)
            ? strtoupper(trim((string) $data['type']))
            : $category->type;

        $this->validateName($name);
        $this->validateType($type);

        $this->ensureUnique(
            $name,
            $type,
            $category->id
        );

        $updated = $this->model->update(
            $category->id,
            [
                'name' => $name,
                'type' => $type,
            ]
        );

        if (!$updated) {
            throw new BusinessException(
                'Não foi possível alterar a categoria financeira.'
            );
        }

        return $this->find($category->id);
    }

    public function activate(
        int $id
    ): FinancialCategory {
        $category = $this->find($id);

        if (!$category->is_active) {
            if (!$this->model->update(
                $category->id,
                ['is_active' => true]
            )) {
                throw new BusinessException(
                    'Não foi possível ativar a categoria financeira.'
                );
            }
        }

        return $this->find($category->id);
    }

    public function deactivate(
        int $id
    ): FinancialCategory {
        $category = $this->find($id);

        if ($category->is_active) {
            if (!$this->model->update(
                $category->id,
                ['is_active' => false]
            )) {
                throw new BusinessException(
                    'Não foi possível inativar a categoria financeira.'
                );
            }
        }

        return $this->find($category->id);
    }

    private function ensureUnique(
        string $name,
        string $type,
        ?int $ignoreId = null
    ): void {
        $builder = $this->model
            ->where('name', $name)
            ->where('type', $type);

        if ($ignoreId !== null) {
            $builder->where(
                'id !=',
                $ignoreId
            );
        }

        if ($builder->first() !== null) {
            throw new BusinessException(
                'Já existe uma categoria financeira com este nome e tipo.'
            );
        }
    }

    private function validateName(string $name): void
    {
        if ($name === '') {
            throw new BusinessException(
                'O nome é obrigatório.'
            );
        }

        if (mb_strlen($name) > 100) {
            throw new BusinessException(
                'O nome deve possuir no máximo 100 caracteres.'
            );
        }
    }

    private function validateType(string $type): void
    {
        if (!in_array(
            $type,
            ['INCOME', 'EXPENSE'],
            true
        )) {
            throw new BusinessException(
                'O tipo deve ser INCOME ou EXPENSE.'
            );
        }
    }
}