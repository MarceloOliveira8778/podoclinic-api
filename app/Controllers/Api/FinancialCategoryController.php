<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Entities\FinancialCategory;
use App\Exceptions\BusinessException;
use App\Exceptions\ResourceNotFoundException;
use App\Models\FinancialCategoryModel;
use App\Services\FinancialCategoryService;
use CodeIgniter\API\ResponseTrait;

class FinancialCategoryController extends BaseController
{
    use ResponseTrait;

    public function index()
    {
        $model = new FinancialCategoryModel();

        $type = strtoupper(
            trim((string) $this->request->getGet('type'))
        );

        $builder = $model->orderBy(
            'name',
            'ASC'
        );

        if ($type !== '') {
            if (!in_array(
                $type,
                ['INCOME', 'EXPENSE'],
                true
            )) {
                return $this->failValidationErrors(
                    'O tipo deve ser INCOME ou EXPENSE.'
                );
            }

            $builder->where('type', $type);
        }

        $categories = $builder->findAll();

        return $this->respond(
            array_map(
                fn (FinancialCategory $category) =>
                    $this->formatCategory($category),
                $categories
            )
        );
    }

    public function show(int $id)
    {
        try {
            $service =
                new FinancialCategoryService();

            return $this->respond(
                $this->formatCategory(
                    $service->find($id)
                )
            );
        } catch (ResourceNotFoundException $exception) {
            return $this->failNotFound(
                $exception->getMessage()
            );
        }
    }

    public function create()
    {
        $data = $this->request->getJSON(true);

        if (!is_array($data)) {
            return $this->failValidationErrors(
                'Dados da requisição inválidos.'
            );
        }

        try {
            $service =
                new FinancialCategoryService();

            return $this->respondCreated(
                $this->formatCategory(
                    $service->create($data)
                )
            );
        } catch (BusinessException $exception) {
            return $this->failValidationErrors(
                $exception->getMessage()
            );
        }
    }

    public function update(int $id)
    {
        $data = $this->request->getJSON(true);

        if (!is_array($data)) {
            return $this->failValidationErrors(
                'Dados da requisição inválidos.'
            );
        }

        try {
            $service =
                new FinancialCategoryService();

            return $this->respond(
                $this->formatCategory(
                    $service->update($id, $data)
                )
            );
        } catch (ResourceNotFoundException $exception) {
            return $this->failNotFound(
                $exception->getMessage()
            );
        } catch (BusinessException $exception) {
            return $this->failValidationErrors(
                $exception->getMessage()
            );
        }
    }

    public function activate(int $id)
    {
        return $this->changeStatus(
            $id,
            true
        );
    }

    public function deactivate(int $id)
    {
        return $this->changeStatus(
            $id,
            false
        );
    }

    private function changeStatus(
        int $id,
        bool $active
    ) {
        try {
            $service =
                new FinancialCategoryService();

            $category = $active
                ? $service->activate($id)
                : $service->deactivate($id);

            return $this->respond(
                $this->formatCategory($category)
            );
        } catch (ResourceNotFoundException $exception) {
            return $this->failNotFound(
                $exception->getMessage()
            );
        } catch (BusinessException $exception) {
            return $this->failValidationErrors(
                $exception->getMessage()
            );
        }
    }

    private function formatCategory(
        FinancialCategory $category
    ): array {
        return [
            'id'        => $category->id,
            'name'      => $category->name,
            'type'      => $category->type,
            'isActive'  => $category->is_active,
            'createdAt' => $category->created_at,
            'updatedAt' => $category->updated_at,
        ];
    }
}