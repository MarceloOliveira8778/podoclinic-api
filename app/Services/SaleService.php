<?php

namespace App\Services;

use App\Entities\Sale;
use App\Exceptions\BusinessException;
use App\Exceptions\ResourceNotFoundException;
use App\Models\CustomerReminderModel;
use App\Models\FinancialMovementModel;
use App\Models\SaleModel;
use App\Models\StockMovementModel;
use DateTimeImmutable;
use Throwable;

class SaleService
{
    public function create(
        array $data,
        int $authenticatedUserId
    ): Sale {
        $saleData = $this->validateAndPrepare(
            $data,
            $authenticatedUserId
        );

        $db = db_connect();

        $db->transBegin();

        try {
            /*
             * Cliente
             */
            $customer = $db
                ->query(
                    '
                    SELECT
                        id,
                        name,
                        is_active
                    FROM customer
                    WHERE id = ?
                    FOR UPDATE
                    ',
                    [
                        $saleData['customer_id'],
                    ]
                )
                ->getRowArray();

            if ($customer === null) {
                throw new ResourceNotFoundException(
                    'Cliente não encontrado.'
                );
            }

            if (!(bool) $customer['is_active']) {
                throw new BusinessException(
                    'Não é possível registrar uma venda para um cliente inativo.'
                );
            }

            /*
             * Produto
             *
             * FOR UPDATE é especialmente importante aqui,
             * pois vamos verificar e reduzir o estoque.
             */
            $product = $db
                ->query(
                    '
                    SELECT
                        id,
                        name,
                        type,
                        current_stock,
                        average_duration_days,
                        is_active
                    FROM product
                    WHERE id = ?
                    FOR UPDATE
                    ',
                    [
                        $saleData['product_id'],
                    ]
                )
                ->getRowArray();

            if ($product === null) {
                throw new ResourceNotFoundException(
                    'Produto não encontrado.'
                );
            }

            if (!(bool) $product['is_active']) {
                throw new BusinessException(
                    'Não é possível vender um produto inativo.'
                );
            }

            if ($product['type'] !== 'RESALE') {
                throw new BusinessException(
                    'Somente produtos do tipo RESALE podem ser vendidos.'
                );
            }

            $averageDurationDays =
                (int) $product['average_duration_days'];

            if ($averageDurationDays <= 0) {
                throw new BusinessException(
                    'O produto deve possuir uma duração média válida para ser vendido.'
                );
            }

            $currentStock =
                (int) $product['current_stock'];

            if ($saleData['quantity'] > $currentStock) {
                throw new BusinessException(
                    'Estoque insuficiente para realizar a venda.'
                );
            }

            /*
             * Categoria financeira
             */
            $financialCategory = $db
                ->query(
                    '
                    SELECT
                        id,
                        name,
                        type,
                        is_active
                    FROM financial_category
                    WHERE id = ?
                    FOR UPDATE
                    ',
                    [
                        $saleData[
                            'financial_category_id'
                        ],
                    ]
                )
                ->getRowArray();

            if ($financialCategory === null) {
                throw new ResourceNotFoundException(
                    'Categoria financeira não encontrada.'
                );
            }

            if (!(bool) $financialCategory['is_active']) {
                throw new BusinessException(
                    'A categoria financeira está inativa.'
                );
            }

            if ($financialCategory['type'] !== 'INCOME') {
                throw new BusinessException(
                    'A categoria financeira da venda deve ser do tipo INCOME.'
                );
            }

            /*
             * Snapshot obtido exclusivamente do banco.
             * Qualquer valor enviado pelo frontend é ignorado.
             */
            $saleData[
                'average_duration_days_snapshot'
            ] = $averageDurationDays;

            /*
             * 1. Registra a venda.
             */
            $saleModel = new SaleModel();

            $saleModel->insert($saleData);

            $saleId =
                (int) $saleModel->getInsertID();

            /*
             * 2. Reduz o estoque.
             */
            $newStock =
                $currentStock
                - $saleData['quantity'];

            $updated = $db
                ->table('product')
                ->where(
                    'id',
                    $saleData['product_id']
                )
                ->update([
                    'current_stock' => $newStock,
                    'updated_at' =>
                        date('Y-m-d H:i:s'),
                ]);

            if (!$updated) {
                throw new BusinessException(
                    'Não foi possível atualizar o estoque do produto.'
                );
            }

            /*
             * 3. Histórico do estoque.
             */
            $stockMovementModel =
                new StockMovementModel();

            $stockMovementModel->insert([
                'product_id' =>
                    $saleData['product_id'],

                'created_by_user_id' =>
                    $authenticatedUserId,

                'type' =>
                    'OUT',

                'reason' =>
                    'SALE',

                'quantity' =>
                    $saleData['quantity'],

                'movement_date' =>
                    $saleData['sale_date'],

                'notes' =>
                    $saleData['notes'],
            ]);

            /*
             * 4. Entrada financeira.
             */
            $financialMovementModel =
                new FinancialMovementModel();

            $financialMovementModel->insert([
                'financial_category_id' =>
                    $saleData[
                        'financial_category_id'
                    ],

                'created_by_user_id' =>
                    $authenticatedUserId,

                'type' =>
                    'INCOME',

                'description' =>
                    'Venda de '
                    . $product['name'],

                'amount' =>
                    $saleData['total_amount'],

                'payment_method' =>
                    $saleData['payment_method'],

                'movement_date' =>
                    $saleData['sale_date'],
            ]);

            /*
            * 5. Calcula a data estimada de reposição.
            *
            * Regra:
            * average_duration_days_snapshot × quantity
            */
            $replacementDays =
                $averageDurationDays
                * $saleData['quantity'];

            $estimatedReplacementDate =
                (new DateTimeImmutable(
                    $saleData['sale_date']
                ))
                    ->modify(
                        '+' . $replacementDays . ' days'
                    )
                    ->format('Y-m-d');

            /*
            * 6. Cria o lembrete de reposição.
            */
            $customerReminderModel =
                new CustomerReminderModel();

            $customerReminderModel->insert([
                'sale_id' =>
                    $saleId,

                'customer_id' =>
                    $saleData['customer_id'],

                'product_id' =>
                    $saleData['product_id'],

                'average_duration_days_snapshot' =>
                    $averageDurationDays,

                'estimated_replacement_date' =>
                    $estimatedReplacementDate,

                'status' =>
                    'PENDING',

                'contacted_at' =>
                    null,
            ]);

            if ($db->transStatus() === false) {
                throw new BusinessException(
                    'Não foi possível registrar a venda.'
                );
            }

            $db->transCommit();

            $sale = $saleModel->find($saleId);

            if (!$sale instanceof Sale) {
                throw new ResourceNotFoundException(
                    'Venda não encontrada após o cadastro.'
                );
            }

            return $sale;
        } catch (Throwable $exception) {
            $db->transRollback();

            throw $exception;
        }
    }

    private function validateAndPrepare(
        array $data,
        int $authenticatedUserId
    ): array {
        $customerId =
            (int) ($data['customerId'] ?? 0);

        $productId =
            (int) ($data['productId'] ?? 0);

        $financialCategoryId =
            (int) (
                $data['financialCategoryId']
                ?? 0
            );

        $quantity =
            (int) ($data['quantity'] ?? 0);

        $totalAmount =
            trim(
                (string) (
                    $data['totalAmount']
                    ?? ''
                )
            );

        $paymentMethod =
            strtoupper(
                trim(
                    (string) (
                        $data['paymentMethod']
                        ?? ''
                    )
                )
            );

        $saleDate =
            trim(
                (string) (
                    $data['saleDate']
                    ?? ''
                )
            );

        $notes = isset($data['notes'])
            ? trim((string) $data['notes'])
            : null;

        if ($customerId <= 0) {
            throw new BusinessException(
                'Informe um cliente válido.'
            );
        }

        if ($productId <= 0) {
            throw new BusinessException(
                'Informe um produto válido.'
            );
        }

        if ($financialCategoryId <= 0) {
            throw new BusinessException(
                'Informe uma categoria financeira válida.'
            );
        }

        if ($quantity <= 0) {
            throw new BusinessException(
                'A quantidade deve ser maior que zero.'
            );
        }

        $this->validateAmount($totalAmount);

        if (
            !in_array(
                $paymentMethod,
                ['CASH', 'PIX'],
                true
            )
        ) {
            throw new BusinessException(
                'A forma de pagamento deve ser CASH ou PIX.'
            );
        }

        $this->validateSaleDate($saleDate);

        if (
            $notes !== null
            && mb_strlen($notes) > 500
        ) {
            throw new BusinessException(
                'As observações devem possuir no máximo 500 caracteres.'
            );
        }

        if ($notes === '') {
            $notes = null;
        }

        return [
            'customer_id' =>
                $customerId,

            'product_id' =>
                $productId,

            'financial_category_id' =>
                $financialCategoryId,

            'created_by_user_id' =>
                $authenticatedUserId,

            'quantity' =>
                $quantity,

            'total_amount' =>
                $totalAmount,

            'payment_method' =>
                $paymentMethod,

            'sale_date' =>
                $saleDate,

            'notes' =>
                $notes,
        ];
    }

    private function validateAmount(
        string $amount
    ): void {
        if (
            !preg_match(
                '/^\d+(\.\d{1,2})?$/',
                $amount
            )
        ) {
            throw new BusinessException(
                'Informe um valor total válido com no máximo duas casas decimais.'
            );
        }

        if (
            bccomp(
                $amount,
                '0',
                2
            ) <= 0
        ) {
            throw new BusinessException(
                'O valor total deve ser maior que zero.'
            );
        }
    }

    private function validateSaleDate(
        string $saleDate
    ): void {
        $date =
            DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $saleDate
            );

        $errors =
            DateTimeImmutable::getLastErrors();

        if (
            $date === false
            || (
                is_array($errors)
                && (
                    $errors['warning_count'] > 0
                    || $errors['error_count'] > 0
                )
            )
            || $date->format('Y-m-d')
                !== $saleDate
        ) {
            throw new BusinessException(
                'Informe uma data de venda válida no formato AAAA-MM-DD.'
            );
        }

        $today =
            new DateTimeImmutable('today');

        if ($date > $today) {
            throw new BusinessException(
                'A data da venda não pode estar no futuro.'
            );
        }
    }
}