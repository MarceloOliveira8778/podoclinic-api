<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use CodeIgniter\Database\BaseBuilder;
use DateTimeImmutable;

class StockMovementReportService
{
    public function getMovements(array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);

        $builder = db_connect()
            ->table('stock_movement sm');

        $builder->select([
            'sm.id',
            'sm.product_id',
            'p.name AS product_name',
            'sm.created_by_user_id',
            'u.name AS created_by_name',
            'sm.type',
            'sm.reason',
            'sm.quantity',
            'sm.movement_date',
            'sm.notes',
            'sm.created_at',
            'sm.updated_at',
        ]);

        $builder->join(
            'product p',
            'p.id = sm.product_id'
        );

        $builder->join(
            'user u',
            'u.id = sm.created_by_user_id'
        );

        $this->applyFilters(
            $builder,
            $filters
        );

        $builder->orderBy(
            'sm.movement_date',
            'DESC'
        );

        $builder->orderBy(
            'sm.id',
            'DESC'
        );

        $rows = $builder
            ->get()
            ->getResultArray();

        return array_map(
            fn (array $row): array =>
                $this->formatMovement($row),
            $rows
        );
    }

    private function normalizeFilters(
        array $filters
    ): array {
        $productId = null;

        if (
            isset($filters['productId'])
            && $filters['productId'] !== ''
        ) {
            if (
                filter_var(
                    $filters['productId'],
                    FILTER_VALIDATE_INT,
                    [
                        'options' => [
                            'min_range' => 1,
                        ],
                    ]
                ) === false
            ) {
                throw new BusinessException(
                    'O produto informado é inválido.'
                );
            }

            $productId =
                (int) $filters['productId'];
        }

        $type = strtoupper(
            trim(
                (string) (
                    $filters['type'] ?? ''
                )
            )
        );

        if (
            $type !== ''
            && !in_array(
                $type,
                ['IN', 'OUT'],
                true
            )
        ) {
            throw new BusinessException(
                'O tipo deve ser IN ou OUT.'
            );
        }

        $reason = strtoupper(
            trim(
                (string) (
                    $filters['reason'] ?? ''
                )
            )
        );

        if (
            $reason !== ''
            && !in_array(
                $reason,
                [
                    'PURCHASE',
                    'CONSUMPTION',
                    'SALE',
                    'ADJUSTMENT',
                ],
                true
            )
        ) {
            throw new BusinessException(
                'O motivo da movimentação é inválido.'
            );
        }

        $startDate = trim(
            (string) (
                $filters['startDate'] ?? ''
            )
        );

        $endDate = trim(
            (string) (
                $filters['endDate'] ?? ''
            )
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
            'type'      => $type,
            'reason'    => $reason,
            'startDate' => $startDate,
            'endDate'   => $endDate,
        ];
    }

    private function applyFilters(
        BaseBuilder $builder,
        array $filters
    ): void {
        if ($filters['productId'] !== null) {
            $builder->where(
                'sm.product_id',
                $filters['productId']
            );
        }

        if ($filters['type'] !== '') {
            $builder->where(
                'sm.type',
                $filters['type']
            );
        }

        if ($filters['reason'] !== '') {
            $builder->where(
                'sm.reason',
                $filters['reason']
            );
        }

        if ($filters['startDate'] !== '') {
            $builder->where(
                'sm.movement_date >=',
                $filters['startDate']
            );
        }

        if ($filters['endDate'] !== '') {
            $builder->where(
                'sm.movement_date <=',
                $filters['endDate']
            );
        }
    }

    private function validateDate(
        string $date,
        string $field
    ): void {
        $parsed =
            DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $date
            );

        $errors =
            DateTimeImmutable::getLastErrors();

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

    private function formatMovement(
        array $row
    ): array {
        return [
            'id' =>
                (int) $row['id'],

            'product' => [
                'id' =>
                    (int) $row['product_id'],

                'name' =>
                    $row['product_name'],
            ],

            'createdByUser' => [
                'id' =>
                    (int) $row['created_by_user_id'],

                'name' =>
                    $row['created_by_name'],
            ],

            'type' =>
                $row['type'],

            'reason' =>
                $row['reason'],

            'quantity' =>
                (int) $row['quantity'],

            'movementDate' =>
                $row['movement_date'],

            'notes' =>
                $row['notes'],

            'createdAt' =>
                $row['created_at'],

            'updatedAt' =>
                $row['updated_at'],
        ];
    }
}