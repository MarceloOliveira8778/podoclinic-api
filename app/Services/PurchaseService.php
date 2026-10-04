<?php

namespace App\Services;

use App\Entities\Purchase;
use App\Exceptions\BusinessException;
use App\Exceptions\ResourceNotFoundException;
use App\Models\FinancialMovementModel;
use App\Models\PurchaseModel;
use App\Models\StockMovementModel;
use DateTimeImmutable;
use Throwable;

class PurchaseService
{
    public function create(
        array $data,
        int $authenticatedUserId
    ): Purchase {
        $purchaseData = $this->validateAndPrepare(
            $data,
            $authenticatedUserId
        );

        $db = db_connect();

        $db->transBegin();

        try {
            /*
             * Bloqueamos o produto durante a operação.
             *
             * Isso evita que duas operações simultâneas
             * leiam o mesmo estoque e sobrescrevam
             * uma à outra.
             */
            $product = $db
                ->query(
                    '
                    SELECT
                        id,
                        name,
                        type,
                        current_stock,
                        is_active
                    FROM product
                    WHERE id = ?
                    FOR UPDATE
                    ',
                    [
                        $purchaseData['product_id'],
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
                    'Não é possível registrar uma compra para um produto inativo.'
                );
            }

            /*
             * A categoria também é verificada dentro
             * da transação para evitar que seu estado
             * seja alterado durante a operação.
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
                        $purchaseData[
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

            if ($financialCategory['type'] !== 'EXPENSE') {
                throw new BusinessException(
                    'A categoria financeira da compra deve ser do tipo EXPENSE.'
                );
            }

            /*
             * 1. Registra a compra.
             */
            $purchaseModel = new PurchaseModel();

            $purchaseModel->insert(
                $purchaseData
            );

            $purchaseId =
                (int) $purchaseModel->getInsertID();

            /*
             * 2. Atualiza o estoque.
             */
            $currentStock =
                (int) $product['current_stock'];

            $newStock =
                $currentStock
                + $purchaseData['quantity'];

            $updated = $db
                ->table('product')
                ->where(
                    'id',
                    $purchaseData['product_id']
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
             * 3. Registra a entrada no histórico
             *    de estoque.
             */
            $stockMovementModel =
                new StockMovementModel();

            $stockMovementModel->insert([
                'product_id' =>
                    $purchaseData['product_id'],

                'created_by_user_id' =>
                    $authenticatedUserId,

                'type' =>
                    'IN',

                'reason' =>
                    'PURCHASE',

                'quantity' =>
                    $purchaseData['quantity'],

                'movement_date' =>
                    $purchaseData['purchase_date'],

                'notes' =>
                    $purchaseData['notes'],
            ]);

            /*
             * 4. Registra a saída financeira.
             */
            $financialMovementModel =
                new FinancialMovementModel();

            $financialMovementModel->insert([
                'financial_category_id' =>
                    $purchaseData[
                        'financial_category_id'
                    ],

                'created_by_user_id' =>
                    $authenticatedUserId,

                'type' =>
                    'EXPENSE',

                'description' =>
                    'Compra de '
                    . $product['name'],

                'amount' =>
                    $purchaseData['total_amount'],

                'payment_method' =>
                    null,

                'movement_date' =>
                    $purchaseData['purchase_date'],
            ]);

            /*
             * Qualquer falha ocorrida na transação
             * impede o commit.
             */
            if ($db->transStatus() === false) {
                throw new BusinessException(
                    'Não foi possível registrar a compra.'
                );
            }

            $db->transCommit();

            $purchase =
                $purchaseModel->find($purchaseId);

            if (!$purchase instanceof Purchase) {
                throw new ResourceNotFoundException(
                    'Compra não encontrada após o cadastro.'
                );
            }

            return $purchase;
        } catch (Throwable $exception) {
            $db->transRollback();

            throw $exception;
        }
    }

    private function validateAndPrepare(
        array $data,
        int $authenticatedUserId
    ): array {
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

        $purchaseDate =
            trim(
                (string) (
                    $data['purchaseDate']
                    ?? ''
                )
            );

        $notes = isset($data['notes'])
            ? trim((string) $data['notes'])
            : null;

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

        $this->validateAmount(
            $totalAmount
        );

        $this->validatePurchaseDate(
            $purchaseDate
        );

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

            'purchase_date' =>
                $purchaseDate,

            'notes' =>
                $notes,
        ];
    }

    private function validateAmount(
        string $amount
    ): void {
        /*
         * Aceitamos:
         *
         * 10
         * 10.5
         * 10.50
         *
         * Não aceitamos:
         *
         * 0
         * -10
         * 10,50
         * 10.999
         */
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

    private function validatePurchaseDate(
        string $purchaseDate
    ): void {
        $date =
            DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $purchaseDate
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
                !== $purchaseDate
        ) {
            throw new BusinessException(
                'Informe uma data de compra válida no formato AAAA-MM-DD.'
            );
        }

        $today =
            new DateTimeImmutable('today');

        if ($date > $today) {
            throw new BusinessException(
                'A data da compra não pode estar no futuro.'
            );
        }
    }
}