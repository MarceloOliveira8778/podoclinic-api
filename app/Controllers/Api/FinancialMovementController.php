<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Entities\FinancialMovement;
use App\Exceptions\BusinessException;
use App\Exceptions\ResourceNotFoundException;
use App\Models\FinancialMovementModel;
use App\Services\FinancialMovementService;
use App\Services\FinancialReportService;
use CodeIgniter\API\ResponseTrait;
use Config\Services;

class FinancialMovementController extends BaseController
{
    use ResponseTrait;

    public function index()
    {
        try {
            $service = new FinancialReportService();

            $movements = $service->getMovements([
                'startDate' =>
                    $this->request->getGet('startDate'),

                'endDate' =>
                    $this->request->getGet('endDate'),

                'type' =>
                    $this->request->getGet('type'),

                'financialCategoryId' =>
                    $this->request->getGet(
                        'financialCategoryId'
                    ),
            ]);

            return $this->respond($movements);
        } catch (BusinessException $exception) {
            return $this->failValidationErrors(
                $exception->getMessage()
            );
        }
    }

    public function show(int $id)
    {
        try {
            $service =
                new FinancialMovementService();

            return $this->respond(
                $this->formatMovement(
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

        $authenticatedUser =
            Services::authenticatedUser();

        $userId = $authenticatedUser->getUserId();

        if ($userId === null) {
            return $this->failUnauthorized(
                'Autenticação necessária.'
            );
        }

        try {
            $service =
                new FinancialMovementService();

            $movement = $service->create(
                $data,
                $userId
            );

            return $this->respondCreated(
                $this->formatMovement($movement)
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

    private function formatMovement(
        FinancialMovement $movement
    ): array {
        return [
            'id' => $movement->id,

            'financialCategoryId' =>
                $movement->financial_category_id,

            'createdByUserId' =>
                $movement->created_by_user_id,

            'type' =>
                $movement->type,

            'description' =>
                $movement->description,

            'amount' =>
                $movement->amount,

            'paymentMethod' =>
                $movement->payment_method,

            'movementDate' =>
                $movement->movement_date,

            'createdAt' =>
                $movement->created_at,

            'updatedAt' =>
                $movement->updated_at,
        ];
    }

    public function summary()
    {
        try {
            $service = new FinancialReportService();

            $summary = $service->getSummary([
                'startDate' =>
                    $this->request->getGet('startDate'),

                'endDate' =>
                    $this->request->getGet('endDate'),

                'type' =>
                    $this->request->getGet('type'),

                'financialCategoryId' =>
                    $this->request->getGet(
                        'financialCategoryId'
                    ),
            ]);

            return $this->respond($summary);
        } catch (BusinessException $exception) {
            return $this->failValidationErrors(
                $exception->getMessage()
            );
        }
    }
}