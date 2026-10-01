<?php

namespace App\Services;

use App\Entities\FinancialMovement;
use App\Exceptions\BusinessException;
use App\Exceptions\ResourceNotFoundException;
use App\Models\FinancialCategoryModel;
use App\Models\FinancialMovementModel;
use DateTimeImmutable;

class FinancialMovementService
{
    private FinancialMovementModel $movementModel;

    private FinancialCategoryModel $categoryModel;

    public function __construct()
    {
        $this->movementModel =
            new FinancialMovementModel();

        $this->categoryModel =
            new FinancialCategoryModel();
    }

    public function create(
        array $data,
        int $authenticatedUserId
    ): FinancialMovement {
        $categoryId = (int) (
            $data['financialCategoryId'] ?? 0
        );

        $type = strtoupper(
            trim((string) ($data['type'] ?? ''))
        );

        $description = trim(
            (string) ($data['description'] ?? '')
        );

        $amount = $data['amount'] ?? null;

        $paymentMethod = isset($data['paymentMethod'])
            ? strtoupper(trim((string) $data['paymentMethod']))
            : null;

        $movementDate = trim(
            (string) ($data['movementDate'] ?? '')
        );

        $this->validateCategoryId($categoryId);
        $this->validateType($type);
        $this->validateDescription($description);
        $this->validateAmount($amount);
        $this->validateMovementDate($movementDate);

        $category = $this->categoryModel->find(
            $categoryId
        );

        if ($category === null) {
            throw new ResourceNotFoundException(
                'Categoria financeira não encontrada.'
            );
        }

        if (!$category->is_active) {
            throw new BusinessException(
                'A categoria financeira informada está inativa.'
            );
        }

        if ($category->type !== $type) {
            throw new BusinessException(
                'O tipo do lançamento deve ser o mesmo tipo da categoria financeira.'
            );
        }

        $paymentMethod = $this->validatePaymentMethod(
            $type,
            $paymentMethod
        );

        $movementId = $this->movementModel->insert([
            'financial_category_id' => $categoryId,
            'created_by_user_id'    => $authenticatedUserId,
            'type'                  => $type,
            'description'           => $description,
            'amount'                => $this->normalizeAmount($amount),
            'payment_method'        => $paymentMethod,
            'movement_date'         => $movementDate,
        ], true);

        if ($movementId === false) {
            throw new BusinessException(
                'Não foi possível registrar o lançamento financeiro.'
            );
        }

        return $this->movementModel->find(
            (int) $movementId
        );
    }

    public function find(int $id): FinancialMovement
    {
        $movement = $this->movementModel->find($id);

        if ($movement === null) {
            throw new ResourceNotFoundException(
                'Lançamento financeiro não encontrado.'
            );
        }

        return $movement;
    }

    private function validateCategoryId(int $categoryId): void
    {
        if ($categoryId <= 0) {
            throw new BusinessException(
                'A categoria financeira é obrigatória.'
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

    private function validateDescription(
        string $description
    ): void {
        if ($description === '') {
            throw new BusinessException(
                'A descrição é obrigatória.'
            );
        }

        if (mb_strlen($description) > 255) {
            throw new BusinessException(
                'A descrição deve possuir no máximo 255 caracteres.'
            );
        }
    }

    private function validateAmount(mixed $amount): void
    {
        if (
            $amount === null
            || $amount === ''
            || !is_numeric($amount)
        ) {
            throw new BusinessException(
                'Informe um valor válido.'
            );
        }

        if ((float) $amount <= 0) {
            throw new BusinessException(
                'O valor deve ser maior que zero.'
            );
        }

        if ((float) $amount > 99999999.99) {
            throw new BusinessException(
                'O valor informado excede o limite permitido.'
            );
        }
    }

    private function normalizeAmount(
        mixed $amount
    ): string {
        return number_format(
            (float) $amount,
            2,
            '.',
            ''
        );
    }

    private function validatePaymentMethod(
        string $type,
        ?string $paymentMethod
    ): ?string {
        if ($type === 'EXPENSE') {
            return null;
        }

        if (
            $paymentMethod === null
            || !in_array(
                $paymentMethod,
                ['CASH', 'PIX'],
                true
            )
        ) {
            throw new BusinessException(
                'Para receitas, a forma de pagamento deve ser CASH ou PIX.'
            );
        }

        return $paymentMethod;
    }

    private function validateMovementDate(
        string $movementDate
    ): void {
        if ($movementDate === '') {
            throw new BusinessException(
                'A data do lançamento é obrigatória.'
            );
        }

        $date = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $movementDate
        );

        $errors = DateTimeImmutable::getLastErrors();

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

        $today = new DateTimeImmutable('today');

        if ($date > $today) {
            throw new BusinessException(
                'A data do lançamento não pode estar no futuro.'
            );
        }
    }
}