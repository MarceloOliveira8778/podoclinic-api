<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Exceptions\ResourceNotFoundException;
use CodeIgniter\Database\BaseConnection;
use Config\Database;

class SaleCancellationService
{
    private BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Valida os dados necessários para cancelar uma venda.
     *
     * Este método não modifica o banco de dados.
     */
    public function validateCancellation(
        int $saleId,
        int $userId,
        string $cancellationType,
        string $reason
    ): array {
        if ($saleId <= 0) {
            throw new BusinessException(
                'Informe uma venda válida.'
            );
        }

        if ($userId <= 0) {
            throw new BusinessException(
                'Informe um usuário válido.'
            );
        }

        if (!in_array(
            $cancellationType,
            ['ENTRY_ERROR', 'CUSTOMER_REFUND'],
            true
        )) {
            throw new BusinessException(
                'O tipo de cancelamento deve ser ENTRY_ERROR ou CUSTOMER_REFUND.'
            );
        }

        $reason = trim($reason);

        $reasonLength = mb_strlen($reason, 'UTF-8');

        if ($reasonLength < 10 || $reasonLength > 500) {
            throw new BusinessException(
                'A justificativa deve conter entre 10 e 500 caracteres.'
            );
        }

        $user = $this->db
            ->table('user')
            ->where('id', $userId)
            ->where('is_active', 1)
            ->get()
            ->getRowArray();

        if ($user === null) {
            throw new BusinessException(
                'O usuário responsável pelo cancelamento não está ativo ou não existe.'
            );
        }

        $sale = $this->db
            ->table('sale')
            ->where('id', $saleId)
            ->get()
            ->getRowArray();

        if ($sale === null) {
            throw new ResourceNotFoundException(
                'Venda não encontrada.'
            );
        }

        if ($sale['status'] !== 'COMPLETED') {
            throw new BusinessException(
                'Esta venda já foi cancelada.'
            );
        }

        $financialMovements = $this->db
            ->table('financial_movement')
            ->where('sale_id', $saleId)
            ->where('type', 'INCOME')
            ->get()
            ->getResultArray();

        if (count($financialMovements) !== 1) {
            throw new BusinessException(
                'A venda não possui um lançamento financeiro original único e identificado. O cancelamento automático não é permitido.'
            );
        }

        $financialMovement = $financialMovements[0];

        if ($financialMovement['reversed_at'] !== null) {
            throw new BusinessException(
                'O lançamento financeiro desta venda já foi anulado.'
            );
        }

        if (
            bccomp(
                (string) $financialMovement['amount'],
                (string) $sale['total_amount'],
                2
            ) !== 0
        ) {
            throw new BusinessException(
                'O valor do lançamento financeiro não corresponde ao valor da venda.'
            );
        }

        return [
            'sale' => $sale,
            'financialMovement' => $financialMovement,
            'userId' => $userId,
            'cancellationType' => $cancellationType,
            'reason' => $reason,
        ];
    }

    public function cancel(
        int $saleId,
        int $userId,
        string $cancellationType,
        string $reason
    ): array {
        // Validações que não dependem do estado do banco.
        if ($saleId <= 0 || $userId <= 0) {
            throw new BusinessException(
                'Informe uma venda e um usuário válidos.'
            );
        }

        if (!in_array(
            $cancellationType,
            ['ENTRY_ERROR', 'CUSTOMER_REFUND'],
            true
        )) {
            throw new BusinessException(
                'Tipo de cancelamento inválido.'
            );
        }

        $reason = trim($reason);

        if (
            mb_strlen($reason, 'UTF-8') < 10
            || mb_strlen($reason, 'UTF-8') > 500
        ) {
            throw new BusinessException(
                'A justificativa deve conter entre 10 e 500 caracteres.'
            );
        }

        // Inicia a transação antes de modificar qualquer registro.
        if ($this->db->transBegin() === false) {
            throw new \RuntimeException(
                'Não foi possível iniciar a transação de cancelamento da venda.'
            );
        }
        //melhoria defensiva. Se o commit falhar por perda de conexão, o estado real da transação ainda poderá exigir conferência no banco.
        $transactionOpen = true; 

        try {
            // Bloqueia a venda para impedir cancelamentos simultâneos.
            $sale = $this->db->query(
                'SELECT *
                FROM sale
                WHERE id = ?
                FOR UPDATE',
                [$saleId]
            )->getRowArray();

            if ($sale === null) {
                throw new ResourceNotFoundException(
                    'Venda não encontrada.'
                );
            }

            if ($sale['status'] !== 'COMPLETED') {
                throw new BusinessException(
                    'Esta venda já foi cancelada.'
                );
            }

            // Verifica o usuário responsável.
            $user = $this->db->query(
                'SELECT id
                FROM user
                WHERE id = ?
                AND is_active = 1
                FOR UPDATE',
                [$userId]
            )->getRowArray();

            if ($user === null) {
                throw new BusinessException(
                    'Usuário responsável inválido ou inativo.'
                );
            }

            // Bloqueia o produto para proteger a atualização do estoque.
            $product = $this->db->query(
                'SELECT *
                FROM product
                WHERE id = ?
                FOR UPDATE',
                [$sale['product_id']]
            )->getRowArray();

            if ($product === null) {
                throw new BusinessException(
                    'Produto da venda não encontrado.'
                );
            }

            // Bloqueia o recebimento financeiro original.
            $financialMovements = $this->db->query(
                'SELECT *
                FROM financial_movement
                WHERE sale_id = ?
                AND type = ?
                FOR UPDATE',
                [$saleId, 'INCOME']
            )->getResultArray();

            if (count($financialMovements) !== 1) {
                throw new BusinessException(
                    'Não foi possível identificar um recebimento financeiro único para esta venda.'
                );
            }

            $financialMovement = $financialMovements[0];

            if ($financialMovement['reversed_at'] !== null) {
                throw new BusinessException(
                    'O recebimento desta venda já foi anulado.'
                );
            }

            if (
                bccomp(
                    (string) $financialMovement['amount'],
                    (string) $sale['total_amount'],
                    2
                ) !== 0
            ) {
                throw new BusinessException(
                    'O valor recebido não corresponde ao valor da venda.'
                );
            }

            // Valida a quantidade registrada na venda.
            $quantity = filter_var(
                $sale['quantity'],
                FILTER_VALIDATE_INT
            );

            if ($quantity === false || $quantity <= 0) {
                throw new BusinessException(
                    'A quantidade da venda é inválida.'
                );
            }

            // Valida o estoque atual.
            $currentStock = filter_var(
                $product['current_stock'],
                FILTER_VALIDATE_INT
            );

            if ($currentStock === false || $currentStock < 0) {
                throw new BusinessException(
                    'O estoque atual do produto é inválido.'
                );
            }

            // Evita ultrapassar o limite de INT UNSIGNED.
            if ($currentStock > 4294967295 - $quantity) {
                throw new BusinessException(
                    'A devolução ultrapassaria o limite permitido para o estoque.'
                );
            }

            $newStock = $currentStock + $quantity;

            // Atualiza o estoque do produto já bloqueado na transação.
            $updated = $this->db
                ->table('product')
                ->where('id', $product['id'])
                ->update([
                    'current_stock' => $newStock,
                    'updated_at'    => date('Y-m-d H:i:s'),
                ]);

            if (
                $updated === false
                || $this->db->affectedRows() !== 1
            ) {
                throw new \RuntimeException(
                    'Não foi possível devolver os produtos ao estoque.'
                );
            }

            $stockNotesPrefix = 'Cancelamento da venda #' . $saleId . ': ';

            $stockNotes = $stockNotesPrefix . mb_substr(
                $reason,
                0,
                500 - mb_strlen($stockNotesPrefix, 'UTF-8'),
                'UTF-8'
            );

            // Registra a movimentação de devolução.
            $inserted = $this->db
                ->table('stock_movement')
                ->insert([
                    'product_id'         => $product['id'],
                    'created_by_user_id' => $userId,
                    'type'               => 'IN',
                    'reason'             => 'SALE_CANCELLATION',
                    'quantity'           => $quantity,
                    'movement_date'      => date('Y-m-d'),
                    'notes' => $stockNotes,
                    'created_at'         => date('Y-m-d H:i:s'),
                    'updated_at'         => date('Y-m-d H:i:s'),
                ]);

            if ($inserted === false) {
                throw new \RuntimeException(
                    'Não foi possível registrar a devolução no estoque.'
                );
            }

            // Tratamento financeiro para erro de lançamento.
            if ($cancellationType === 'ENTRY_ERROR') {
                $now = date('Y-m-d H:i:s');

                $updated = $this->db
                    ->table('financial_movement')
                    ->where('id', $financialMovement['id'])
                    ->where('reversed_at', null)
                    ->update([
                        'reversed_at'         => $now,
                        'reversed_by_user_id' => $userId,
                        'updated_at'          => $now,
                    ]);

                if (
                    $updated === false
                    || $this->db->affectedRows() !== 1
                ) {
                    throw new \RuntimeException(
                        'Não foi possível anular o recebimento financeiro.'
                    );
                }
            }

            // Tratamento financeiro para devolução efetiva ao cliente.
            if ($cancellationType === 'CUSTOMER_REFUND') {

                // Localiza a categoria financeira específica para devoluções.
                $refundCategory = $this->db
                    ->table('financial_category')
                    ->where('name', 'Devolução de vendas')
                    ->where('type', 'EXPENSE')
                    ->where('is_active', 1)
                    ->get()
                    ->getRowArray();

                if ($refundCategory === null) {
                    throw new BusinessException(
                        'A categoria financeira Devolução de vendas não foi encontrada ou está inativa.'
                    );
                }

                // Garante que o método de pagamento seja válido.
                $paymentMethod = (string) $sale['payment_method'];

                if (!in_array($paymentMethod, ['CASH', 'PIX'], true)) {
                    throw new BusinessException(
                        'O método de pagamento da venda é inválido.'
                    );
                }

                // Evita registrar outra devolução para a mesma venda.
                $existingRefund = $this->db
                    ->table('financial_movement')
                    ->where('sale_id', $saleId)
                    ->where('type', 'EXPENSE')
                    ->countAllResults();

                if ($existingRefund > 0) {
                    throw new BusinessException(
                        'Já existe uma movimentação de despesa vinculada a esta venda. Verifique antes de continuar.'
                    );
                }

                $now = date('Y-m-d H:i:s');

                $refundDescriptionPrefix = 'Devolução da venda #' . $saleId . ': ';

                $refundDescription = $refundDescriptionPrefix . mb_substr(
                    $reason,
                    0,
                    255 - mb_strlen($refundDescriptionPrefix, 'UTF-8'),
                    'UTF-8'
                );

                // Registra a saída efetiva do dinheiro.
                $inserted = $this->db
                    ->table('financial_movement')
                    ->insert([
                        'financial_category_id' => $refundCategory['id'],
                        'created_by_user_id'    => $userId,
                        'sale_id'               => $saleId,
                        'type'                  => 'EXPENSE',
                        'description'           => $refundDescription,
                        'amount'                => $sale['total_amount'],
                        'payment_method'        => $paymentMethod,
                        'movement_date'         => date('Y-m-d'),
                        'created_at'            => $now,
                        'updated_at'            => $now,
                    ]);

                if ($inserted === false) {
                    throw new \RuntimeException(
                        'Não foi possível registrar a devolução financeira ao cliente.'
                    );
                }
            }

            // Localiza e bloqueia o lembrete associado à venda.
            $reminders = $this->db->query(
                'SELECT *
                FROM customer_reminder
                WHERE sale_id = ?
                FOR UPDATE',
                [$saleId]
            )->getResultArray();

            // Pela regra atual, cada venda possui um único lembrete.
            if (count($reminders) > 1) {
                throw new BusinessException(
                    'Foram encontrados múltiplos lembretes para esta venda.'
                );
            }

            if (count($reminders) === 1) {
                $reminder = $reminders[0];

                if (!in_array(
                    $reminder['status'],
                    ['PENDING', 'CONTACTED', 'CANCELLED'],
                    true
                )) {
                    throw new BusinessException(
                        'O lembrete possui um status inválido.'
                    );
                }

                if ($reminder['status'] !== 'CANCELLED') {
                    $updated = $this->db
                        ->table('customer_reminder')
                        ->where('id', $reminder['id'])
                        ->update([
                            'status'     => 'CANCELLED',
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);

                    if (
                        $updated === false
                        || $this->db->affectedRows() !== 1
                    ) {
                        throw new \RuntimeException(
                            'Não foi possível cancelar o lembrete de reposição.'
                        );
                    }
                }
            }            

            // Registra o cancelamento da venda.
            $now = date('Y-m-d H:i:s');

            $updated = $this->db
                ->table('sale')
                ->where('id', $saleId)
                ->where('status', 'COMPLETED')
                ->update([
                    'status'               => 'CANCELLED',
                    'cancellation_type'    => $cancellationType,
                    'cancellation_reason'  => $reason,
                    'cancelled_at'         => $now,
                    'cancelled_by_user_id' => $userId,
                    'updated_at'           => $now,
                ]);

            if ($updated === false || $this->db->affectedRows() !== 1) {
                throw new \RuntimeException(
                    'Não foi possível registrar o cancelamento da venda.'
                );
            }

            // Verifica se alguma operação SQL falhou.
            if ($this->db->transStatus() === false) {
                throw new \RuntimeException(
                    'Falha ao processar o cancelamento da venda.'
                );
            }

            // Confirma todas as operações de uma única vez.
            if ($this->db->transCommit() === false) {
                throw new \RuntimeException(
                    'Não foi possível confirmar o cancelamento da venda.'
                );
            }

            $transactionOpen = false;

            return [
                'saleId'           => (int) $sale['id'],
                'status'           => 'CANCELLED',
                'cancellationType' => $cancellationType,
                'reason'           => $reason,
                'cancelledAt'      => $now,
                'cancelledByUserId' => $userId,
            ];

        } catch (\Throwable $exception) {
            if ($transactionOpen) {
                $this->db->transRollback();
            }

            throw $exception;
        }
    }
}