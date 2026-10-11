<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use CodeIgniter\Database\BaseBuilder;
use DateTimeImmutable;

class FinancialReportService
{
    public function getMovements(array $filters): array
    {
        $filters = $this->normalizeFilters($filters);

        $builder = db_connect()
            ->table('financial_movement fm')
            ->select([
                'fm.id',
                'fm.financial_category_id',
                'fc.name AS category_name',
                'fm.created_by_user_id',
                'u.name AS created_by_name',
                'fm.type',
                'fm.description',
                'fm.amount',
                'fm.payment_method',
                'fm.movement_date',
                'fm.created_at',
                'fm.updated_at',
            ])
            ->join(
                'financial_category fc',
                'fc.id = fm.financial_category_id'
            )
            ->join(
                'user u',
                'u.id = fm.created_by_user_id'
            );

        $this->applyFilters(
            $builder,
            $filters
        );

        $rows = $builder
            ->orderBy('fm.movement_date', 'DESC')
            ->orderBy('fm.id', 'DESC')
            ->get()
            ->getResultArray();

        return array_map(
            fn (array $row): array => [
                'id' => (int) $row['id'],

                'financialCategory' => [
                    'id' => (int) $row['financial_category_id'],
                    'name' => $row['category_name'],
                ],

                'createdByUser' => [
                    'id' => (int) $row['created_by_user_id'],
                    'name' => $row['created_by_name'],
                ],

                'type' => $row['type'],
                'description' => $row['description'],
                'amount' => $row['amount'],
                'paymentMethod' => $row['payment_method'],
                'movementDate' => $row['movement_date'],
                'createdAt' => $row['created_at'],
                'updatedAt' => $row['updated_at'],
            ],
            $rows
        );
    }

    public function getSummary(array $filters): array
    {
        $filters = $this->normalizeFilters($filters);

        $builder = db_connect()
            ->table('financial_movement fm')
            ->select("
                COALESCE(
                    SUM(
                        CASE
                            WHEN fm.type = 'INCOME'
                            THEN fm.amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS total_income,
                COALESCE(
                    SUM(
                        CASE
                            WHEN fm.type = 'EXPENSE'
                            THEN fm.amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS total_expense
            ")
            ->where('fm.reversed_at', null);

        $this->applyFilters(
            $builder,
            $filters
        );

        $result = $builder
            ->get()
            ->getRowArray();

        $totalIncome = (string) (
            $result['total_income'] ?? '0.00'
        );

        $totalExpense = (string) (
            $result['total_expense'] ?? '0.00'
        );

        $balance = bcsub(
            $totalIncome,
            $totalExpense,
            2
        );

        $totalIncome = bcadd(
            (string) ($result['total_income'] ?? '0'),
            '0',
            2
        );

        $totalExpense = bcadd(
            (string) ($result['total_expense'] ?? '0'),
            '0',
            2
        );

        $balance = bcsub(
            $totalIncome,
            $totalExpense,
            2
        );

        return [
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'balance' => $balance,
        ];
    }

    private function normalizeFilters(array $filters): array
    {
        $startDate = trim(
            (string) ($filters['startDate'] ?? '')
        );

        $endDate = trim(
            (string) ($filters['endDate'] ?? '')
        );

        $type = strtoupper(
            trim((string) ($filters['type'] ?? ''))
        );

        $financialCategoryId = isset(
            $filters['financialCategoryId']
        )
            ? (int) $filters['financialCategoryId']
            : null;

        if ($startDate !== '') {
            $this->validateDate(
                $startDate,
                'A data inicial'
            );
        }

        if ($endDate !== '') {
            $this->validateDate(
                $endDate,
                'A data final'
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

        if (
            $type !== ''
            && !in_array(
                $type,
                ['INCOME', 'EXPENSE'],
                true
            )
        ) {
            throw new BusinessException(
                'O tipo deve ser INCOME ou EXPENSE.'
            );
        }

        if (
            $financialCategoryId !== null
            && $financialCategoryId <= 0
        ) {
            throw new BusinessException(
                'Informe uma categoria financeira válida.'
            );
        }

        return [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'type' => $type,
            'financialCategoryId' =>
                $financialCategoryId,
        ];
    }

    private function applyFilters(
        BaseBuilder $builder,
        array $filters
    ): void {
        if ($filters['startDate'] !== '') {
            $builder->where(
                'fm.movement_date >=',
                $filters['startDate']
            );
        }

        if ($filters['endDate'] !== '') {
            $builder->where(
                'fm.movement_date <=',
                $filters['endDate']
            );
        }

        if ($filters['type'] !== '') {
            $builder->where(
                'fm.type',
                $filters['type']
            );
        }

        if ($filters['financialCategoryId'] !== null) {
            $builder->where(
                'fm.financial_category_id',
                $filters['financialCategoryId']
            );
        }
    }

    private function validateDate(
        string $date,
        string $field
    ): void {
        $parsedDate =
            DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $date
            );

        $errors = DateTimeImmutable::getLastErrors();

        if (
            $parsedDate === false
            || (
                is_array($errors)
                && (
                    $errors['warning_count'] > 0
                    || $errors['error_count'] > 0
                )
            )
            || $parsedDate->format('Y-m-d') !== $date
        ) {
            throw new BusinessException(
                "{$field} deve possuir uma data válida no formato AAAA-MM-DD."
            );
        }
    }
}