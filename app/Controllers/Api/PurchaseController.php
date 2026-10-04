<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Entities\Purchase;
use App\Exceptions\BusinessException;
use App\Exceptions\ResourceNotFoundException;
use App\Services\PurchaseService;
use App\Services\PurchaseReportService;
use Config\Services;
use CodeIgniter\API\ResponseTrait;
use Throwable;

class PurchaseController extends BaseController
{
    use ResponseTrait;

    public function create()
    {
        try {
            $data = $this->request->getJSON(true);

            if (!is_array($data)) {
                return $this->failValidationErrors(
                    'Informe os dados da compra.'
                );
            }

            $authenticatedUserId =
                Services::authenticatedUser()
                    ->getUserId();

            $service = new PurchaseService();

            $purchase = $service->create(
                $data,
                $authenticatedUserId
            );

            return $this->respondCreated(
                $this->formatPurchase($purchase)
            );
        } catch (ResourceNotFoundException $exception) {
            return $this->failNotFound(
                $exception->getMessage()
            );
        } catch (BusinessException $exception) {
            return $this->failValidationErrors(
                $exception->getMessage()
            );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Erro ao registrar compra: {message}',
                [
                    'message' => $exception->getMessage(),
                ]
            );

            return $this->failServerError(
                'Não foi possível registrar a compra.'
            );
        }
    }

    private function formatPurchase(
        Purchase $purchase
    ): array {
        return [
            'id' =>
                $purchase->id,

            'productId' =>
                $purchase->product_id,

            'financialCategoryId' =>
                $purchase->financial_category_id,

            'createdByUserId' =>
                $purchase->created_by_user_id,

            'quantity' =>
                $purchase->quantity,

            'totalAmount' =>
                $purchase->total_amount,

            'purchaseDate' =>
                $purchase->purchase_date,

            'notes' =>
                $purchase->notes,

            'createdAt' =>
                $purchase->created_at,

            'updatedAt' =>
                $purchase->updated_at,
        ];
    }

    public function index()
    {
    try {
        $service = new PurchaseReportService();

        $purchases = $service->getPurchases([
            'productId' =>
                $this->request->getGet('productId'),

            'financialCategoryId' =>
                $this->request->getGet(
                    'financialCategoryId'
                ),

            'createdByUserId' =>
                $this->request->getGet(
                    'createdByUserId'
                ),

            'startDate' =>
                $this->request->getGet('startDate'),

            'endDate' =>
                $this->request->getGet('endDate'),
        ]);

        return $this->respond($purchases);
    } catch (BusinessException $exception) {
        return $this->failValidationErrors(
            $exception->getMessage()
        );
    } catch (Throwable $exception) {
        log_message(
            'error',
            'Erro ao consultar compras: {message}',
            [
                'message' =>
                    $exception->getMessage(),
            ]
        );

        return $this->failServerError(
            'Não foi possível consultar as compras.'
        );
    }
    }
}