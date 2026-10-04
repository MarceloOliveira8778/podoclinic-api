<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use CodeIgniter\Database\BaseBuilder;
use DateTimeImmutable;

class PurchaseReportService
{
    public function getPurchases(array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);

        $builder = db_connect()
            ->table('purchase pu');

        $builder->select([
            'pu.id',
            'pu.product_id',
            'p.name AS product_name',
            'pu.financial_category_id',
            'fc.name AS financial_category_name',
            'pu.created_by_user_id',
            'u.name AS created_by_name',
            'pu.quantity',
            'pu.total_amount',
            'pu.purchase_date',
            'pu.notes',
            'pu.created_at',
            'pu.updated_at',
        ]);

        $builder->join(
            'product p',
            'p.id = pu.product_id'
        );

        $builder->join(
            'financial_category fc',
            'fc.id = pu.financial_category_id'
        );

        $builder->join(
            'user u',
            'u.id = pu.created_by_user_id'
        );

        $this->applyFilters(
            $builder,
            $filters
        );

        $builder->orderBy(
            'pu.purchase_date',
            'DESC'
        );

        $builder->orderBy(
            'pu.id',
            'DESC'
        );

        $rows = $builder
            ->get()
            ->getResultArray();

        return array_map(
            fn (array $row): array =>
                $this->formatPurchase($row),
            $rows
        );
    }

    private function normalizeFilters(array $filters): array
    {
        $productId = $this->normalizeOptionalId(
            $filters['productId'] ?? null,
            'O produto informado é inválido.'
        );

        $financialCategoryId = $this->normalizeOptionalId(
            $filters['financialCategoryId'] ?? null,
            'A categoria financeira informada é inválida.'
        );

        $createdByUserId = $this->normalizeOptionalId(
            $filters['createdByUserId'] ?? null,
            'O usuário informado é inválido.'
        );

        $startDate = trim(
            (string) ($filters['startDate'] ?? '')
        );

        $endDate = trim(
            (string) ($filters['endDate'] ?? '')
        );

        if ($startDate !== '') {
            $this->validateDate(
                $startDate,
                'data inicial'
            );
        }

        if ($endDate !== '') {
            $this->validateDate(
                $endDate,
                'data final'
            );
        }

        if (
            $startDate !== ''
            && $endDate !== ''
            && $startDate > $endDate
        ) {
            throw new BusinessException(
                'A data inicial não pode ser maior que a data final.'
            );
        }

        return [
            'productId' => $productId,
            'financialCategoryId' => $financialCategoryId,
            'createdByUserId' => $createdByUserId,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];
    }

    private function normalizeOptionalId(
        mixed $value,
        string $errorMessage
    ): ?int {
        if ($value === null || $value === '') {
            return null;
        }

        $validated = filter_var(
            $value,
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 1,
                ],
            ]
        );

        if ($validated === false) {
            throw new BusinessException(
                $errorMessage
            );
        }

        return (int) $validated;
    }

    private function applyFilters(
        BaseBuilder $builder,
        array $filters
    ): void {
        if ($filters['productId'] !== null) {
            $builder->where(
                'pu.product_id',
                $filters['productId']
            );
        }

        if ($filters['financialCategoryId'] !== null) {
            $builder->where(
                'pu.financial_category_id',
                $filters['financialCategoryId']
            );
        }

        if ($filters['createdByUserId'] !== null) {
            $builder->where(
                'pu.created_by_user_id',
                $filters['createdByUserId']
            );
        }

        if ($filters['startDate'] !== '') {
            $builder->where(
                'pu.purchase_date >=',
                $filters['startDate']
            );
        }

        if ($filters['endDate'] !== '') {
            $builder->where(
                'pu.purchase_date <=',
                $filters['endDate']
            );
        }
    }

    private function validateDate(
        string $date,
        string $field
    ): void {
        $parsed = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $date
        );

        $errors = DateTimeImmutable::getLastErrors();

        if (
            $parsed === false
            || (
                is_array($errors)
                && (
                    $errors['warning_count'] > 0
                    || $errors['error_count'] > 0
                )
            )
            || $parsed->format('Y-m-d') !== $date
        ) {
            throw new BusinessException(
                sprintf(
                    'Informe uma %s válida no formato AAAA-MM-DD.',
                    $field
                )
            );
        }
    }

    private function formatPurchase(array $row): array
    {
        return [
            'id' => (int) $row['id'],

            'product' => [
                'id' => (int) $row['product_id'],
                'name' => $row['product_name'],
            ],

            'financialCategory' => [
                'id' => (int) $row['financial_category_id'],
                'name' => $row['financial_category_name'],
            ],

            'createdByUser' => [
                'id' => (int) $row['created_by_user_id'],
                'name' => $row['created_by_name'],
            ],

            'quantity' => (int) $row['quantity'],

            'totalAmount' => $row['total_amount'],

            'purchaseDate' => $row['purchase_date'],

            'notes' => $row['notes'],

            'createdAt' => $row['created_at'],

            'updatedAt' => $row['updated_at'],
        ];
    }
}