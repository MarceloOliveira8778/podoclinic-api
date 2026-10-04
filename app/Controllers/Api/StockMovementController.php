<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Entities\StockMovement;
use App\Exceptions\BusinessException;
use App\Exceptions\ResourceNotFoundException;
use App\Services\StockMovementService;
use App\Services\StockMovementReportService;
use CodeIgniter\API\ResponseTrait;
use Config\Services;
use Throwable;

class StockMovementController extends BaseController
{
    use ResponseTrait;

    public function index()
    {
        try {
            $service =
                new StockMovementReportService();

            $movements =
                $service->getMovements([
                    'productId' =>
                        $this->request->getGet(
                            'productId'
                        ),

                    'type' =>
                        $this->request->getGet(
                            'type'
                        ),

                    'reason' =>
                        $this->request->getGet(
                            'reason'
                        ),

                    'startDate' =>
                        $this->request->getGet(
                            'startDate'
                        ),

                    'endDate' =>
                        $this->request->getGet(
                            'endDate'
                        ),
                ]);

            return $this->respond(
                $movements
            );
        } catch (
            BusinessException $exception
        ) {
            return $this->failValidationErrors(
                $exception->getMessage()
            );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Erro ao consultar movimentações de estoque: {message}',
                [
                    'message' =>
                        $exception->getMessage(),
                ]
            );

            return $this->failServerError(
                'Não foi possível consultar as movimentações de estoque.'
            );
        }
    }

    public function entry()
    {
        return $this->createMovement('IN');
    }

    public function exit()
    {
        return $this->createMovement('OUT');
    }

    private function formatMovement(
        StockMovement $movement
    ): array {
        return [
            'id' =>
                $movement->id,

            'productId' =>
                $movement->product_id,

            'createdByUserId' =>
                $movement->created_by_user_id,

            'type' =>
                $movement->type,

            'reason' =>
                $movement->reason,

            'quantity' =>
                $movement->quantity,

            'movementDate' =>
                $movement->movement_date,

            'notes' =>
                $movement->notes,

            'createdAt' =>
                $movement->created_at,

            'updatedAt' =>
                $movement->updated_at,
        ];
    }

    private function createMovement(string $type)
    {
        $data = $this->request->getJSON(true);

        if (!is_array($data)) {
            return $this->failValidationErrors(
                'Dados da requisição inválidos.'
            );
        }

        $userId = Services::authenticatedUser()
            ->getUserId();

        if ($userId === null) {
            return $this->failUnauthorized(
                'Autenticação necessária.'
            );
        }

        try {
            $service = new StockMovementService();

            $movement = $service->createManualMovement(
                $data,
                $userId,
                $type
            );

            return $this->respondCreated(
                $this->formatMovement($movement)
            );
        } catch (
            ResourceNotFoundException $exception
        ) {
            return $this->failNotFound(
                $exception->getMessage()
            );
        } catch (
            BusinessException $exception
        ) {
            return $this->failValidationErrors(
                $exception->getMessage()
            );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Erro ao movimentar estoque: {message}',
                [
                    'message' =>
                        $exception->getMessage(),
                ]
            );

            return $this->failServerError(
                'Não foi possível realizar a movimentação de estoque.'
            );
        }
    }
}