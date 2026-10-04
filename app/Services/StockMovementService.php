<?php

namespace App\Services;

use App\Entities\StockMovement;
use App\Exceptions\BusinessException;
use App\Exceptions\ResourceNotFoundException;
use App\Models\StockMovementModel;
use DateTimeImmutable;
use RuntimeException;
use Throwable;

class StockMovementService
{
    public function createManualMovement(
        array $data,
        int $authenticatedUserId,
        string $type
    ): StockMovement {
        $productId = (int) (
            $data['productId'] ?? 0
        );

        $reason = strtoupper(
            trim((string) ($data['reason'] ?? ''))
        );

        $quantity = (int) (
            $data['quantity'] ?? 0
        );

        $movementDate = trim(
            (string) ($data['movementDate'] ?? '')
        );

        $notes = isset($data['notes'])
            ? trim((string) $data['notes'])
            : null;

        if ($notes === '') {
            $notes = null;
        }

        $this->validateInput(
            $productId,
            $type,
            $reason,
            $quantity,
            $movementDate,
            $notes
        );

        $db = db_connect();

        $db->transBegin();

        try {
            /*
             * SELECT ... FOR UPDATE
             *
             * Impede duas requisições concorrentes de calcularem
             * o estoque utilizando simultaneamente o mesmo saldo.
             */
            $product = $db
                ->query(
                    'SELECT
                        id,
                        name,
                        type,
                        current_stock,
                        is_active
                     FROM product
                     WHERE id = ?
                     FOR UPDATE',
                    [$productId]
                )
                ->getRowArray();

            if ($product === null) {
                throw new ResourceNotFoundException(
                    'Produto não encontrado.'
                );
            }

            if (!(bool) $product['is_active']) {
                throw new BusinessException(
                    'Não é possível movimentar o estoque de um produto inativo.'
                );
            }

            $this->validateReason(
                $type,
                $reason,
                (string) $product['type']
            );

            $currentStock = (int) $product['current_stock'];

            if ($type === 'IN') {
                $newStock =
                    $currentStock + $quantity;
            } else {
                if ($quantity > $currentStock) {
                    throw new BusinessException(
                        'Estoque insuficiente para realizar a saída.'
                    );
                }

                $newStock =
                    $currentStock - $quantity;
            }

            $updated = $db
                ->table('product')
                ->where('id', $productId)
                ->update([
                    'current_stock' => $newStock,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            if (!$updated) {
                throw new RuntimeException(
                    'Não foi possível atualizar o estoque do produto.'
                );
            }

            $movementModel =
                new StockMovementModel();

            $movementId = $movementModel->insert([
                'product_id' =>
                    $productId,

                'created_by_user_id' =>
                    $authenticatedUserId,

                'type' =>
                    $type,

                'reason' =>
                    $reason,

                'quantity' =>
                    $quantity,

                'movement_date' =>
                    $movementDate,

                'notes' =>
                    $notes,
            ], true);

            if ($movementId === false) {
                throw new RuntimeException(
                    'Não foi possível registrar a movimentação de estoque.'
                );
            }

            if ($db->transStatus() === false) {
                throw new RuntimeException(
                    'Não foi possível concluir a movimentação de estoque.'
                );
            }

            $db->transCommit();

            $movement =
                $movementModel->find((int) $movementId);

            if ($movement === null) {
                throw new RuntimeException(
                    'Movimentação registrada, mas não foi possível recuperá-la.'
                );
            }

            return $movement;
        } catch (Throwable $exception) {
            $db->transRollback();
            
            throw $exception;
        }
    }

    private function validateInput(
        int $productId,
        string $type,
        string $reason,
        int $quantity,
        string $movementDate,
        ?string $notes
    ): void {
        if ($productId <= 0) {
            throw new BusinessException(
                'O produto é obrigatório.'
            );
        }

        if (!in_array(
            $type,
            ['IN', 'OUT'],
            true
        )) {
            throw new BusinessException(
                'O tipo deve ser IN ou OUT.'
            );
        }

        if (!in_array(
            $reason,
            [
                'CONSUMPTION',
                'ADJUSTMENT',
            ],
            true
        )) {
            throw new BusinessException(
                'A movimentação manual deve utilizar CONSUMPTION ou ADJUSTMENT.'
            );
        }

        if ($quantity <= 0) {
            throw new BusinessException(
                'A quantidade deve ser maior que zero.'
            );
        }

        if (
            $reason === 'ADJUSTMENT'
            && $notes === null
        ) {
            throw new BusinessException(
                'Informe o motivo do ajuste de estoque.'
            );
        }

        if (
            $notes !== null
            && mb_strlen($notes) > 500
        ) {
            throw new BusinessException(
                'As observações devem possuir no máximo 500 caracteres.'
            );
        }

        $this->validateMovementDate(
            $movementDate
        );
    }

    private function validateReason(
        string $type,
        string $reason,
        string $productType
    ): void {
        if ($reason === 'CONSUMPTION') {
            if ($type !== 'OUT') {
                throw new BusinessException(
                    'Movimentações de consumo devem ser do tipo OUT.'
                );
            }

            if ($productType !== 'CONSUMPTION') {
                throw new BusinessException(
                    'Somente produtos de consumo podem possuir saída por consumo.'
                );
            }

            return;
        }

        /*
         * ADJUSTMENT pode ser IN ou OUT e pode ser utilizado
         * para ambos os tipos de produto.
         */
        if ($reason === 'ADJUSTMENT') {
            return;
        }

        throw new BusinessException(
            'Motivo de movimentação inválido.'
        );
    }

    private function validateMovementDate(
        string $movementDate
    ): void {
        if ($movementDate === '') {
            throw new BusinessException(
                'A data da movimentação é obrigatória.'
            );
        }

        $date =
            DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $movementDate
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
            || $date->format('Y-m-d') !== $movementDate
        ) {
            throw new BusinessException(
                'Informe uma data válida no formato AAAA-MM-DD.'
            );
        }

        $today =
            new DateTimeImmutable('today');

        if ($date > $today) {
            throw new BusinessException(
                'A data da movimentação não pode estar no futuro.'
            );
        }
    }
}