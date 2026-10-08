<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Entities\Sale;
use App\Exceptions\BusinessException;
use App\Exceptions\ResourceNotFoundException;
use App\Services\SaleService;
use CodeIgniter\API\ResponseTrait;
use Config\Services;
use Throwable;

class SaleController extends BaseController
{
    use ResponseTrait;

    public function index()
    {
        try {
            $filters = [
                'startDate'     => $this->request->getGet('startDate'),
                'endDate'       => $this->request->getGet('endDate'),
                'customerId'    => $this->request->getGet('customerId'),
                'productId'     => $this->request->getGet('productId'),
                'paymentMethod' => $this->request->getGet('paymentMethod'),
            ];

            $service = new SaleService();

            $sales = $service->getSales($filters);

            $response = array_map(
                static function (array $sale): array {
                    return [
                        'id' => (int) $sale['id'],

                        'customer' => [
                            'id'    => (int) $sale['customer_id'],
                            'name'  => $sale['customer_name'],
                            'phone' => $sale['customer_phone'],
                        ],

                        'product' => [
                            'id'   => (int) $sale['product_id'],
                            'name' => $sale['product_name'],
                        ],

                        'financialCategoryId' =>
                            (int) $sale['financial_category_id'],

                        'createdByUser' => [
                            'id'   => (int) $sale['created_by_user_id'],
                            'name' => $sale['created_by_user_name'],
                        ],

                        'quantity' => (int) $sale['quantity'],

                        'totalAmount' => $sale['total_amount'],

                        'paymentMethod' => $sale['payment_method'],

                        'saleDate' => $sale['sale_date'],

                        'averageDurationDaysSnapshot' =>
                            (int) $sale['average_duration_days_snapshot'],

                        'notes' => $sale['notes'],

                        'createdAt' => $sale['created_at'],

                        'updatedAt' => $sale['updated_at'],
                    ];
                },
                $sales
            );

            return $this->respond($response);

        } catch (BusinessException $exception) {
            return $this->failValidationErrors(
                $exception->getMessage()
            );

        } catch (Throwable $exception) {
            log_message(
                'error',
                'Erro ao consultar histórico de vendas: {message}',
                [
                    'message' => $exception->getMessage(),
                ]
            );

            return $this->failServerError(
                'Não foi possível consultar o histórico de vendas.'
            );
        }
    }

    public function create()
    {
        try {
            $data = $this->request->getJSON(true);

            if (!is_array($data)) {
                return $this->failValidationErrors(
                    'Informe os dados da venda.'
                );
            }

            $authenticatedUserId =
                Services::authenticatedUser()
                    ->getUserId();

            $service = new SaleService();

            $sale = $service->create(
                $data,
                $authenticatedUserId
            );

            return $this->respondCreated(
                $this->formatSale($sale)
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
                'Erro ao registrar venda: {message}',
                [
                    'message' =>
                        $exception->getMessage(),
                ]
            );

            return $this->failServerError(
                'Não foi possível registrar a venda.'
            );
        }
    }

    private function formatSale(
        Sale $sale
    ): array {
        return [
            'id' =>
                $sale->id,

            'customerId' =>
                $sale->customer_id,

            'productId' =>
                $sale->product_id,

            'financialCategoryId' =>
                $sale->financial_category_id,

            'createdByUserId' =>
                $sale->created_by_user_id,

            'quantity' =>
                $sale->quantity,

            'totalAmount' =>
                $sale->total_amount,

            'paymentMethod' =>
                $sale->payment_method,

            'saleDate' =>
                $sale->sale_date,

            'averageDurationDaysSnapshot' =>
                $sale->average_duration_days_snapshot,

            'notes' =>
                $sale->notes,

            'createdAt' =>
                $sale->created_at,

            'updatedAt' =>
                $sale->updated_at,
        ];
    }
}